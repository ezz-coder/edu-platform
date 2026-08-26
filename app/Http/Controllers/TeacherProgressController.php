<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\ExamAttempt;
use App\Models\GroupEnrollment;
use App\Models\StudyGroup;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Sprint 67 — Student Progress Tracking.
 *
 * Uses ONLY existing data, exactly as stored:
 *  - Exam attempts (finished) → score / total_marks from the Grades module
 *  - Attendance records → present / absent counts
 *
 * No invented formulas beyond the score percentage already used by the
 * Grades module. All data is scoped to the teacher's own groups.
 */
class TeacherProgressController extends Controller
{
    public function index(Request $request): View
    {
        $teacherId = auth()->id();

        $groups = StudyGroup::where('teacher_id', $teacherId)->orderBy('title')->get();

        // Group selection (required when teacher has groups).
        $selectedGroupId = $request->filled('group_id') ? (int) $request->input('group_id') : null;
        if ($selectedGroupId && ! $groups->contains('id', $selectedGroupId)) {
            abort(403);
        }
        if (! $selectedGroupId && $groups->isNotEmpty()) {
            $selectedGroupId = $groups->first()->id;
        }

        $students = collect();
        $selectedStudent = null;
        $studentAttempts = collect();
        $studentAttendance = null;

        if ($selectedGroupId) {
            $group = $groups->firstWhere('id', $selectedGroupId);

            // Enrolled students of this group only.
            $enrollments = GroupEnrollment::where('group_id', $group->id)
                ->where('status', 'enrolled')
                ->with('student')
                ->orderBy('id')
                ->get();

            foreach ($enrollments as $enrollment) {
                $attempts = ExamAttempt::where('student_id', $enrollment->student_id)
                    ->where('status', 'finished')
                    ->whereHas('exam.targetGroups', fn ($q) => $q->where('group_id', $group->id))
                    ->get();

                $totalScore = $attempts->sum('score');
                $totalMarks = $attempts->sum('total_marks');

                $present = AttendanceRecord::where('student_id', $enrollment->student_id)
                    ->where('group_id', $group->id)
                    ->whereIn('attendance_type', ['regular'])
                    ->count();
                $absent = AttendanceRecord::where('student_id', $enrollment->student_id)
                    ->where('group_id', $group->id)
                    ->where('attendance_type', 'absent')
                    ->count();

                $students->push([
                    'enrollment' => $enrollment,
                    'exams_taken' => $attempts->count(),
                    'avg_percent' => $totalMarks > 0 ? round(($totalScore / $totalMarks) * 100, 1) : null,
                    'present' => $present,
                    'absent' => $absent,
                ]);
            }

            // Optional single-student detail view.
            if ($request->filled('student_id')) {
                $selectedStudent = $students->first(fn ($s) => $s['enrollment']->student_id == $request->input('student_id'));
                if ($selectedStudent) {
                    $studentAttempts = ExamAttempt::where('student_id', $selectedStudent['enrollment']->student_id)
                        ->where('status', 'finished')
                        ->whereHas('exam.targetGroups', fn ($q) => $q->where('group_id', $group->id))
                        ->with('exam')
                        ->orderByDesc('finished_at')
                        ->get();

                    $studentAttendance = [
                        'present' => $selectedStudent['present'],
                        'absent' => $selectedStudent['absent'],
                    ];
                }
            }
        }

        return view('teacher.progress.index', [
            'header' => 'تتبع تقدم الطلاب',
            'groups' => $groups,
            'selectedGroupId' => $selectedGroupId,
            'students' => $students,
            'selectedStudent' => $selectedStudent,
            'studentAttempts' => $studentAttempts,
            'studentAttendance' => $studentAttendance,
        ]);
    }
}