<?php

namespace App\Http\Controllers;

use App\Models\ExamAttempt;
use App\Models\GroupEnrollment;
use App\Models\StudyGroup;
use App\Models\TeacherActivity;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Sprint 66 — Teacher Reports.
 *
 * Per the specification (teacher_features.md §13), there is no dedicated
 * reports data model; reports are summaries built from existing data:
 *  - Attendance summary per group (sessions + present/absent/late counts)
 *  - Grades summary per group / per student (finished exam attempts)
 *  - Group & student report tables
 *  - Activity information (TeacherActivity log)
 *
 * Ownership: only the teacher's own groups, students and activities are used.
 */
class TeacherReportController extends Controller
{
    public function index(): View
    {
        $teacherId = auth()->id();

        $groups = StudyGroup::where('teacher_id', $teacherId)->orderBy('title')->get();

        $groupId = request()->input('group_id');
        $dateFrom = request()->input('date_from');
        $dateTo = request()->input('date_to');

        // Validate the selected group belongs to this teacher.
        $selectedGroup = null;
        if ($groupId) {
            $selectedGroup = StudyGroup::where('id', $groupId)
                ->where('teacher_id', $teacherId)
                ->first();
        }

        $attendanceQuery = DB::table('attendance_sessions')
            ->join('study_groups', 'study_groups.id', '=', 'attendance_sessions.study_group_id')
            ->where('study_groups.teacher_id', $teacherId);

        if ($selectedGroup) {
            $attendanceQuery->where('attendance_sessions.study_group_id', $selectedGroup->id);
        }
        if ($dateFrom) {
            $attendanceQuery->whereDate('attendance_sessions.attendance_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $attendanceQuery->whereDate('attendance_sessions.attendance_date', '<=', $dateTo);
        }

        // Attendance summary per session.
        // NOTE: the real column is `attendance_type` (regular | late | absent),
        // matching the existing StudentAccountController calculation.
        $sessions = (clone $attendanceQuery)
            ->selectRaw("attendance_sessions.id, attendance_sessions.lesson_number, attendance_sessions.attendance_date, attendance_sessions.status as session_status, study_groups.title as group_title,
                SUM(CASE WHEN attendance_records.attendance_type IN ('regular', 'present') THEN 1 ELSE 0 END) as present_count,
                SUM(CASE WHEN attendance_records.attendance_type = 'absent' THEN 1 ELSE 0 END) as absent_count,
                SUM(CASE WHEN attendance_records.attendance_type = 'late' THEN 1 ELSE 0 END) as late_count,
                COUNT(attendance_records.id) as total_records")
            ->leftJoin('attendance_records', 'attendance_records.attendance_session_id', '=', 'attendance_sessions.id')
            ->groupBy(
                'attendance_sessions.id', 'attendance_sessions.lesson_number',
                'attendance_sessions.attendance_date', 'attendance_sessions.status',
                'study_groups.title'
            )
            ->orderByDesc('attendance_sessions.attendance_date')
            ->limit(50)
            ->get();

        // Totals across the filtered sessions.
        $attendanceTotals = [
            'sessions' => (clone $attendanceQuery)->count(),
            'present' => (int) $sessions->sum('present_count'),
            'absent' => (int) $sessions->sum('absent_count'),
            'late' => (int) $sessions->sum('late_count'),
        ];

        // Grades summary: finished attempts of the teacher's exams.
        $gradesQuery = ExamAttempt::where('status', 'finished')
            ->whereHas('exam', fn ($q) => $q->where('teacher_id', $teacherId))
            ->when($selectedGroup, function ($q) use ($selectedGroup) {
                $q->whereHas('student.enrollments', fn ($e) => $e->where('group_id', $selectedGroup->id));
            })
            ->when($dateFrom, fn ($q) => $q->whereDate('finished_at', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->whereDate('finished_at', '<=', $dateTo));

        $gradesTotals = [
            'attempts' => (clone $gradesQuery)->count(),
            'avg_score' => round((float) (clone $gradesQuery)->avg('score'), 1),
            'max_score' => (clone $gradesQuery)->max('score'),
        ];

        // Per-student report for the selected group.
        $studentReports = collect();
        if ($selectedGroup) {
            $enrollments = GroupEnrollment::where('group_id', $selectedGroup->id)
                ->with('student')
                ->orderBy('status')
                ->get();

            foreach ($enrollments as $enrollment) {
                $att = DB::table('attendance_records')
                    ->join('attendance_sessions', 'attendance_sessions.id', '=', 'attendance_records.attendance_session_id')
                    ->where('attendance_sessions.study_group_id', $selectedGroup->id)
                    ->where('attendance_records.student_id', $enrollment->student_id)
                    ->selectRaw("SUM(CASE WHEN attendance_type IN ('regular', 'present') THEN 1 ELSE 0 END) as present,
                        SUM(CASE WHEN attendance_type = 'absent' THEN 1 ELSE 0 END) as absent,
                        SUM(CASE WHEN attendance_type = 'late' THEN 1 ELSE 0 END) as late")
                    ->first();

                $gradeAgg = ExamAttempt::where('exam_attempts.student_id', $enrollment->student_id)
                    ->where('exam_attempts.status', 'finished')
                    ->whereHas('exam', fn ($q) => $q->where('teacher_id', $teacherId))
                    ->selectRaw('COUNT(*) as attempts, AVG(score) as avg_score')
                    ->first();

                $studentReports->push([
                    'enrollment' => $enrollment,
                    'present' => (int) ($att->present ?? 0),
                    'absent' => (int) ($att->absent ?? 0),
                    'late' => (int) ($att->late ?? 0),
                    'attempts' => (int) ($gradeAgg->attempts ?? 0),
                    'avg_score' => $gradeAgg->avg_score !== null ? round((float) $gradeAgg->avg_score, 1) : null,
                ]);
            }
        }

        // Recent activity within the date range.
        $activities = TeacherActivity::where('teacher_id', $teacherId)
            ->when($dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->whereDate('created_at', '<=', $dateTo))
            ->latest()
            ->limit(20)
            ->get();

        return view('teacher.reports.index', [
            'header' => 'التقارير',
            'groups' => $groups,
            'selectedGroup' => $selectedGroup,
            'filters' => [
                'group_id' => $groupId,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
            'attendanceTotals' => $attendanceTotals,
            'sessions' => $sessions,
            'gradesTotals' => $gradesTotals,
            'studentReports' => $studentReports,
            'activities' => $activities,
        ]);
    }
}