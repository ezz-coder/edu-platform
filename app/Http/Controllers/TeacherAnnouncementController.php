<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\StudyGroup;
use App\Utilities\NotificationHelper;
use Illuminate\Http\Request;

/**
 * Sprint 68 — Announcements.
 *
 * Rules (per specification):
 *  - The teacher creates announcements for his own groups.
 *  - Only targeted (enrolled) students see the announcement on their dashboard.
 *  - Targeted students receive a notification via the existing notification system.
 *  - The teacher can edit/delete his own announcements only.
 */
class TeacherAnnouncementController extends Controller
{
    public function index()
    {
        $announcements = Announcement::where('teacher_id', auth()->id())
            ->with('groups')
            ->latest()
            ->get();

        return view('teacher.announcements.index', compact('announcements'));
    }

    public function create()
    {
        $groups = StudyGroup::where('teacher_id', auth()->id())->orderBy('title')->get();

        return view('teacher.announcements.create', compact('groups'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $announcement = Announcement::create([
            'teacher_id' => auth()->id(),
            'title' => $data['title'],
            'body' => $data['body'],
            'published_at' => now(),
        ]);
        $announcement->groups()->sync($data['group_ids']);

        NotificationHelper::notifyStudents(
            auth()->id(),
            NotificationHelper::groupStudentIds($data['group_ids']),
            'announcement',
            'إعلان جديد',
            $data['title'],
            route('student.dashboard')
        );

        return redirect()->route('teacher.announcements.index')
            ->with('success', 'تم إنشاء الإعلان وإرساله للمستهدفين');
    }

    public function edit(Announcement $announcement)
    {
        abort_unless($announcement->teacher_id === auth()->id(), 403);

        $groups = StudyGroup::where('teacher_id', auth()->id())->orderBy('title')->get();

        return view('teacher.announcements.edit', [
            'announcement' => $announcement,
            'groups' => $groups,
        ]);
    }

    public function update(Request $request, Announcement $announcement)
    {
        abort_unless($announcement->teacher_id === auth()->id(), 403);

        $data = $this->validated($request);

        $announcement->update([
            'title' => $data['title'],
            'body' => $data['body'],
        ]);
        $announcement->groups()->sync($data['group_ids']);

        return redirect()->route('teacher.announcements.index')
            ->with('success', 'تم تحديث الإعلان');
    }

    public function destroy(Announcement $announcement)
    {
        abort_unless($announcement->teacher_id === auth()->id(), 403);

        $announcement->delete();

        return redirect()->route('teacher.announcements.index')
            ->with('success', 'تم حذف الإعلان');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:5000'],
            'group_ids' => ['required', 'array', 'min:1'],
            'group_ids.*' => [
                'integer',
                // Groups must belong to this teacher.
                fn (string $attribute, int $value, \Closure $fail) =>
                    StudyGroup::where('id', $value)->where('teacher_id', auth()->id())->exists()
                        || $fail('المجموعة غير موجودة ضمن مجموعاتك.'),
            ],
        ]);
    }
}