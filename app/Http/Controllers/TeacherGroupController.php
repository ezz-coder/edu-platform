<?php

namespace App\Http\Controllers;

use App\Models\AcademicTerm;
use App\Models\StudyGroup;
use App\Models\TeacherActivity;
use App\Utilities\NotificationHelper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TeacherGroupController extends Controller
{
    public function index(): View
    {
        $groups = StudyGroup::where('teacher_id', auth()->id())->latest()->get();

        return view('teacher.groups.index', [
            'header' => 'مجموعاتي',
            'groups' => $groups,
        ]);
    }

    public function create(): View
    {
        return view('teacher.groups.create', [
            'header' => 'إنشاء مجموعة جديدة',
        ]);
    }

    public function show(StudyGroup $group): View
    {
        $this->authorizeGroup($group);

        $group->load(['teacher']);

        $enrollments = $group->enrollments()
            ->with('student')
            ->latest()
            ->paginate(20);

        $otherGroups = StudyGroup::where('teacher_id', auth()->id())
            ->where('id', '!=', $group->id)
            ->orderBy('title')
            ->get();

        $activities = TeacherActivity::where('teacher_id', auth()->id())
            ->where('type', 'like', 'group_%')
            ->where(function ($q) use ($group) {
                $q->where('title', 'like', "%{$group->title}%")
                  ->orWhere('description', 'like', "%{$group->title}%");
            })
            ->latest('created_at')
            ->limit(15)
            ->get();

        // Attendance history for this group (newest first).
        $attendanceSessions = \App\Models\AttendanceSession::where('study_group_id', $group->id)
            ->where('teacher_id', auth()->id())
            ->latest('attendance_date')
            ->get();

        return view('teacher.groups.show', [
            'header' => $group->title,
            'group' => $group,
            'enrollments' => $enrollments,
            'otherGroups' => $otherGroups,
            'activities' => $activities,
            'attendanceSessions' => $attendanceSessions,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'day' => 'required|string',
            'start_time' => 'required',
            'end_time' => 'required',
            'gender_type' => 'required|in:boys,girls,mixed',
            'grade_level' => 'required|string',
            'max_students' => 'required|integer|min:1',
        ]);

        $activeTerm = AcademicTerm::where('teacher_id', auth()->id())
            ->where('status', 'active')
            ->first();

        $group = StudyGroup::create([
            ...$validated,
            'teacher_id' => auth()->id(),
            'academic_term_id' => $activeTerm?->id,
        ]);

        TeacherActivity::log(
            auth()->id(),
            'group_created',
            'إنشاء مجموعة',
            "تم إنشاء مجموعة {$group->title}"
        );

        return redirect()->route('teacher.groups.index')->with('success', 'تم إنشاء المجموعة بنجاح');
    }

    public function edit(StudyGroup $group): View
    {
        $this->authorizeGroup($group);

        return view('teacher.groups.edit', [
            'header' => 'تعديل المجموعة',
            'group' => $group,
        ]);
    }

    public function update(Request $request, StudyGroup $group): RedirectResponse
    {
        $this->authorizeGroup($group);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'day' => 'required|string',
            'start_time' => 'required',
            'end_time' => 'required',
            'gender_type' => 'required|in:boys,girls,mixed',
            'grade_level' => 'required|string',
            'max_students' => 'required|integer|min:1',
            'status' => 'required|in:active,inactive',
        ]);

        // Prevent lowering capacity below current enrollment.
        if ($validated['max_students'] < $group->currentStudents()) {
            return back()->withErrors(['max_students' => 'لا يمكن أن يكون الحد الأقصى أقل من عدد الطلاب الحالي'])->withInput();
        }

        $wasActive = $group->status === 'active';
        $scheduleChanged = $group->day !== $validated['day']
            || $group->start_time != $validated['start_time']
            || $group->end_time != $validated['end_time'];
        $group->update($validated);

        TeacherActivity::log(
            auth()->id(),
            'group_updated',
            'تعديل مجموعة',
            "تم تعديل مجموعة {$group->title}"
        );

        // If the schedule changed, notify the group's students (one each, no duplicates).
        if ($scheduleChanged) {
            $studentIds = NotificationHelper::groupStudentIds([$group->id]);
            NotificationHelper::notifyStudents(
                auth()->id(),
                $studentIds,
                'group',
                'تم تغيير موعد المجموعة',
                "تغير موعد مجموعة {$group->title}. الرجاء المراجعة.",
                route('student.groups.index')
            );

            // Teacher gets one clean confirmation (smart, never spammy).
            NotificationHelper::notifyTeacher(
                auth()->id(),
                'group',
                'تم تغيير الموعد بنجاح',
                "تم تحديث موعد مجموعة {$group->title} بنجاح.",
                route('teacher.groups.show', $group)
            );
        }

        if ($wasActive && $group->status === 'inactive') {
            TeacherActivity::log(
                auth()->id(),
                'group_deactivated',
                'إيقاف مجموعة',
                "تم إيقاف مجموعة {$group->title}"
            );
        } elseif (! $wasActive && $group->status === 'active') {
            TeacherActivity::log(
                auth()->id(),
                'group_activated',
                'تفعيل مجموعة',
                "تم تفعيل مجموعة {$group->title}"
            );
        }

        return redirect()->route('teacher.groups.index')->with('success', 'تم تحديث المجموعة بنجاح');
    }

    public function archive(StudyGroup $group): RedirectResponse
    {
        $this->authorizeGroup($group);

        $group->update(['status' => 'archived']);

        TeacherActivity::log(
            auth()->id(),
            'group_archived',
            'أرشفة مجموعة',
            "تم أرشفة مجموعة {$group->title}"
        );

        return redirect()->route('teacher.groups.index')->with('success', 'تم أرشفة المجموعة. لن تظهر في القوائم النشطة.');
    }

    public function restore(StudyGroup $group): RedirectResponse
    {
        $this->authorizeGroup($group);

        $group->update(['status' => 'active']);

        TeacherActivity::log(
            auth()->id(),
            'group_restored',
            'استرجاع مجموعة',
            "تم استرجاع مجموعة {$group->title}"
        );

        return redirect()->route('teacher.groups.index')->with('success', 'تم استرجاع المجموعة بنجاح');
    }

    public function destroy(StudyGroup $group): RedirectResponse
    {
        $this->authorizeGroup($group);

        $title = $group->title;
        $group->delete();

        TeacherActivity::log(
            auth()->id(),
            'group_deleted',
            'حذف مجموعة',
            "تم حذف مجموعة {$title}"
        );

        return redirect()->route('teacher.groups.index')->with('success', 'تم حذف المجموعة بنجاح');
    }

    private function authorizeGroup(StudyGroup $group): void
    {
        if ($group->teacher_id !== auth()->id()) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه المجموعة');
        }
    }
}