<?php

namespace App\Http\Controllers;

use App\Models\AcademicTerm;
use App\Models\AttendanceSession;
use App\Models\GroupEnrollment;
use App\Models\Notification;
use App\Models\StudyGroup;
use App\Models\TermRollback;
use App\Utilities\NotificationHelper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TeacherTermController extends Controller
{
    /**
     * End Term wizard - step 1: choose groups + mode.
     */
    public function endForm(): View
    {
        $teacherId = auth()->id();

        $activeTerm = AcademicTerm::where('teacher_id', $teacherId)
            ->where('status', 'active')
            ->firstOrFail();

        $groups = StudyGroup::where('teacher_id', $teacherId)
            ->withCount(['students as current_students_count'])
            ->orderBy('title')
            ->get();

        return view('teacher.term.end', [
            'header' => 'إنهاء الترم',
            'term' => $activeTerm,
            'groups' => $groups,
        ]);
    }

    /**
     * End Term wizard - step 2: show summary, require final confirmation.
     */
    public function confirm(Request $request): View
    {
        $validated = $request->validate([
            'mode' => ['required', 'in:keep_groups,new_groups'],
            'group_ids' => ['nullable', 'array'],
            'group_ids.*' => ['integer', 'exists:study_groups,id'],
        ]);

        $teacherId = auth()->id();
        $activeTerm = AcademicTerm::where('teacher_id', $teacherId)
            ->where('status', 'active')
            ->firstOrFail();

        $selectedIds = $validated['mode'] === 'keep_groups'
            ? ($validated['group_ids'] ?? [])
            : [];

        $selectedGroups = StudyGroup::where('teacher_id', $teacherId)
            ->whereIn('id', $selectedIds)
            ->withCount(['students as current_students_count'])
            ->get();

        $allGroups = StudyGroup::where('teacher_id', $teacherId)->count();
        $studentsCount = GroupEnrollment::where('status', 'enrolled')
            ->whereHas('group', fn ($q) => $q->where('teacher_id', $teacherId))
            ->distinct('student_id')->count('student_id');
        $sessionsCount = AttendanceSession::where('teacher_id', $teacherId)->count();
        $recordsCount = AttendanceSession::where('teacher_id', $teacherId)
            ->withCount('records')->get()->sum('records_count');

        return view('teacher.term.confirm', [
            'header' => 'تأكيد إنهاء الترم',
            'term' => $activeTerm,
            'mode' => $validated['mode'],
            'selectedGroups' => $selectedGroups,
            'allGroups' => $allGroups,
            'studentsCount' => $studentsCount,
            'sessionsCount' => $sessionsCount,
            'recordsCount' => $recordsCount,
        ]);
    }

    /**
     * Execute the end-term: archive current term, create new active term,
     * optionally move selected groups to the new term.
     */
    public function execute(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'mode' => ['required', 'in:keep_groups,new_groups'],
            'group_ids' => ['nullable', 'array'],
            'group_ids.*' => ['integer', 'exists:study_groups,id'],
        ]);

        $teacherId = auth()->id();
        $activeTerm = AcademicTerm::where('teacher_id', $teacherId)
            ->where('status', 'active')
            ->firstOrFail();

        $selectedIds = $validated['mode'] === 'keep_groups'
            ? ($validated['group_ids'] ?? [])
            : [];

        // Archive the current term (read-only history).
        $activeTerm->update(['status' => 'archived']);

        // Create the next active term.
        $newTerm = AcademicTerm::create([
            'teacher_id' => $teacherId,
            'title' => 'الترم ' . ($activeTerm->term_number + 1),
            'year' => $activeTerm->year,
            'term_number' => $activeTerm->term_number + 1,
            'status' => 'active',
        ]);

        // Mode A: move selected groups into the new term so they remain usable.
        $movedIds = [];
        if ($validated['mode'] === 'keep_groups') {
            $groups = StudyGroup::where('teacher_id', $teacherId)
                ->whereIn('id', $selectedIds)
                ->get();
            foreach ($groups as $group) {
                $group->update(['academic_term_id' => $newTerm->id]);
                $movedIds[] = $group->id;
            }
        }

        // Students get ONE notification (term archived) — no teacher spam.
        $studentIds = NotificationHelper::groupStudentIds(
            StudyGroup::where('teacher_id', $teacherId)->pluck('id')->all()
        );
        NotificationHelper::notifyStudents(
            $teacherId,
            $studentIds,
            'term',
            'تم أرشفة الترم',
            "تم أرشفة {$activeTerm->title} وبدأ {$newTerm->title}.",
            route('student.dashboard')
        );

        // Students also get a clear "term started" notification.
        NotificationHelper::notifyStudents(
            $teacherId,
            $studentIds,
            'term',
            'بدأ ترم جديد',
            "بدأ الآن {$newTerm->title}. المواد والأنشطة الجديدة متاحة لك.",
            route('student.dashboard')
        );

        NotificationHelper::notifyTeacher(
            $teacherId,
            'term',
            'تم أرشفة الترم',
            "تم أرشفة {$activeTerm->title} وإنشاء {$newTerm->title}.",
            route('teacher.dashboard')
        );

        NotificationHelper::notifyTeacher(
            $teacherId,
            'term',
            'بدأ ترم جديد',
            "بدأ الآن {$newTerm->title}.",
            route('teacher.dashboard')
        );

        // Store a simple rollback record (undo window).
        TermRollback::create([
            'teacher_id' => $teacherId,
            'old_term_id' => $activeTerm->id,
            'new_term_id' => $newTerm->id,
            'mode' => $validated['mode'],
            'moved_group_ids' => $movedIds,
            'executed_at' => now(),
        ]);

        return redirect()->route('teacher.dashboard')
            ->with('success', 'تم إنهاء الترم وأرشفته. يمكنك التراجع لفترة قصيرة من لوحة التحكم.');
    }

    /**
     * Undo the last end-term execution, only if no new work began.
     */
    public function undo(): RedirectResponse
    {
        $teacherId = auth()->id();

        $rollback = TermRollback::where('teacher_id', $teacherId)
            ->latest('executed_at')
            ->first();

        if (! $rollback) {
            return back()->with('error', 'لا توجد عملية إنهاء ترم قابلة للتراجع.');
        }

        // Undo is only allowed if no new work started after execution.
        $after = $rollback->executed_at;
        $newAttendance = AttendanceSession::where('teacher_id', $teacherId)
            ->where('created_at', '>', $after)->exists();
        $newEnrollment = GroupEnrollment::whereHas('group', fn ($q) => $q->where('teacher_id', $teacherId))
            ->where('created_at', '>', $after)->exists();
        $newGroup = StudyGroup::where('teacher_id', $teacherId)
            ->where('created_at', '>', $after)->exists();

        if ($newAttendance || $newEnrollment || $newGroup) {
            return back()->with('error', 'تم بدء عمل جديد بعد إنهاء الترم، لا يمكن التراجع.');
        }

        // Reverse: reactivate old term, delete new term, move groups back.
        if (! empty($rollback->moved_group_ids)) {
            StudyGroup::whereIn('id', $rollback->moved_group_ids)
                ->update(['academic_term_id' => $rollback->old_term_id]);
        }
        AcademicTerm::where('id', $rollback->new_term_id)->delete();
        $rollback->oldTerm->update(['status' => 'active']);
        $rollback->delete();

        return redirect()->route('teacher.dashboard')
            ->with('success', 'تم التراجع عن إنهاء الترم بنجاح.');
    }
}