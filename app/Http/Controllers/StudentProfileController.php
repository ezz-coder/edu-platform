<?php

namespace App\Http\Controllers;

use App\Models\GroupEnrollment;
use App\Models\StudyGroup;
use App\Models\TeacherActivity;
use Illuminate\View\View;

class StudentProfileController extends Controller
{
    /**
     * Show a student profile for the teacher.
     * Foundation page: no attendance/exams/grades/resources yet.
     * Only students enrolled in the teacher's groups are accessible.
     */
    public function show(GroupEnrollment $enrollment): View
    {
        $this->authorizeEnrollment($enrollment);

        $student = $enrollment->student;
        $group = $enrollment->group;

        $enrollment->load([
            'histories' => fn ($q) => $q->with(['fromGroup', 'toGroup'])->latest('changed_at'),
        ]);

        // Other groups owned by the same teacher for the Move dialog.
        $otherGroups = StudyGroup::where('teacher_id', auth()->id())
            ->where('id', '!=', $group->id)
            ->where('status', 'active')
            ->orderBy('title')
            ->get();

        // Activity timeline for this student (latest first, recent only).
        $activities = TeacherActivity::where('teacher_id', auth()->id())
            ->where(function ($q) use ($student, $group) {
                $q->where('description', 'like', "%{$student->name}%")
                  ->orWhere('description', 'like', "%{$group->title}%");
            })
            ->latest('created_at')
            ->limit(15)
            ->get();

        return view('teacher.students.show', [
            'header' => $student->name,
            'enrollment' => $enrollment,
            'student' => $student,
            'group' => $group,
            'otherGroups' => $otherGroups,
            'activities' => $activities,
        ]);
    }

    private function authorizeEnrollment(GroupEnrollment $enrollment): void
    {
        if ($enrollment->group->teacher_id !== auth()->id()) {
            abort(403, 'غير مصرح لك بالوصول إلى هذا الطالب');
        }
    }
}