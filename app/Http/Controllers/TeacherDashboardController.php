<?php

namespace App\Http\Controllers;

use App\Models\AcademicTerm;
use App\Models\AttendanceSession;
use App\Models\Exam;
use App\Models\GroupEnrollment;
use App\Models\StudyGroup;
use App\Models\TeacherActivity;
use App\Models\TermRollback;
use Illuminate\View\View;

class TeacherDashboardController extends Controller
{
    public function index(): View
    {
        $teacherId = auth()->id();

        // Eager load groups with their enrollments to avoid N+1.
        $groups = StudyGroup::where('teacher_id', $teacherId)
            ->with(['term'])
            ->withCount(['students as current_students_count'])
            ->latest()
            ->get();

        $activeGroups = $groups->where('status', 'active');
        $inactiveGroups = $groups->where('status', 'inactive');

        $totalSeats = $groups->sum('max_students');
        $remainingSeats = $groups->sum(fn ($g) => $g->remainingSeats());

        $studentsCount = GroupEnrollment::where('status', 'enrolled')
            ->whereHas('group', fn ($q) => $q->where('teacher_id', $teacherId))
            ->distinct('student_id')
            ->count('student_id');

        $pendingTransfers = GroupEnrollment::where('status', 'pending_transfer')
            ->whereHas('group', fn ($q) => $q->where('teacher_id', $teacherId))
            ->count();

        // Today's groups ordered by start time.
        $today = now()->locale('ar')->dayName;
        $todaysGroups = $groups
            ->where('status', 'active')
            ->where('day', $today)
            ->sortBy('start_time');

        $activities = TeacherActivity::where('teacher_id', $teacherId)
            ->latest('created_at')
            ->limit(15)
            ->get();

        $activeTerm = AcademicTerm::where('teacher_id', $teacherId)
            ->where('status', 'active')
            ->first();

        $rollback = TermRollback::where('teacher_id', $teacherId)
            ->latest('executed_at')
            ->first();

        $examsCount = Exam::where('teacher_id', $teacherId)->count();
        $attendanceCount = AttendanceSession::where('teacher_id', $teacherId)->count();

        return view('teacher.dashboard', [
            'header' => 'مساحة مجموعاتي',
            'groups' => $groups,
            'activeGroupsCount' => $activeGroups->count(),
            'inactiveGroupsCount' => $inactiveGroups->count(),
            'totalSeats' => $totalSeats,
            'remainingSeats' => $remainingSeats,
            'studentsCount' => $studentsCount,
            'pendingTransfers' => $pendingTransfers,
            'todaysGroups' => $todaysGroups,
            'activities' => $activities,
            'activeTerm' => $activeTerm,
            'rollback' => $rollback,
            'stats' => [
                'groups' => $groups->count(),
                'enrollments' => $studentsCount,
                'exams' => $examsCount,
                'attendance_sessions' => $attendanceCount,
            ],
        ]);
    }
}
