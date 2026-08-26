<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Sprint 68 — Parent read-only access to his children's data.
 *
 * Allowed: attendance summary, grades (finished exam attempts), report data,
 * basic student info. Forbidden: teacher/assistant data, other students,
 * any write action.
 */
class ParentDashboardController extends Controller
{
    public function index()
    {
        $parent = auth('parent')->user();

        $students = User::where('role', 'student')
            ->where('parent_mobile', $parent->mobile)
            ->with(['enrollments.group'])
            ->orderBy('name')
            ->get();

        return view('parent.dashboard', compact('students'));
    }

    public function showStudent(User $student)
    {
        $parent = auth('parent')->user();
        abort_unless($parent->ownsStudent($student), 403);

        $enrollment = $student->enrollments()->with('group')->latest('id')->first();

        // Attendance summary (same calculation as the student account page).
        $attendance = collect();
        if ($enrollment) {
            $attendance = DB::table('attendance_records')
                ->join('attendance_sessions', 'attendance_sessions.id', '=', 'attendance_records.attendance_session_id')
                ->where('attendance_sessions.study_group_id', $enrollment->group_id)
                ->where('attendance_records.student_id', $student->id)
                ->selectRaw('status, count(*) as c')
                ->groupBy('status')
                ->pluck('c', 'status');
        }

        // Finished exam attempts with grades.
        $attempts = DB::table('exam_attempts')
            ->join('exams', 'exams.id', '=', 'exam_attempts.exam_id')
            ->where('exam_attempts.student_id', $student->id)
            ->where('exam_attempts.status', 'finished')
            ->select('exams.title', 'exam_attempts.score', 'exam_attempts.total_marks', 'exam_attempts.finished_at')
            ->orderByDesc('exam_attempts.finished_at')
            ->limit(20)
            ->get();

        return view('parent.student', [
            'student' => $student,
            'enrollment' => $enrollment,
            'attendance' => $attendance,
            'attempts' => $attempts,
        ]);
    }
}