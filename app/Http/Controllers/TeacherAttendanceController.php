<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\GroupEnrollment;
use App\Models\StudyGroup;
use App\Models\TeacherActivity;
use App\Models\User;
use App\Utilities\NotificationHelper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TeacherAttendanceController extends Controller
{
    /**
     * List all attendance sessions for the teacher (newest first).
     */
    public function index(): View
    {
        $sessions = AttendanceSession::where('teacher_id', auth()->id())
            ->with('group')
            ->latest('started_at')
            ->get();

        return view('teacher.attendance.index', [
            'header' => 'جلسات الحضور',
            'sessions' => $sessions,
        ]);
    }

    /**
     * Show the "start new session" form (group picker only).
     */
    public function create(): View
    {
        $groups = StudyGroup::where('teacher_id', auth()->id())
            ->where('status', 'active')
            ->orderBy('title')
            ->get();

        return view('teacher.attendance.create', [
            'header' => 'بدء حصة حضور',
            'groups' => $groups,
        ]);
    }

    /**
     * Create (or reuse) an attendance session for a group.
     * Rule: only one OPEN session per group. If one exists, reuse it.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'group_id' => ['required', 'exists:study_groups,id'],
            'lesson_number' => ['required', 'integer', 'min:1'],
        ]);

        $group = StudyGroup::where('id', $validated['group_id'])
            ->where('teacher_id', auth()->id())
            ->firstOrFail();

        // Reuse an already-open session for this group.
        $open = AttendanceSession::where('study_group_id', $group->id)
            ->where('teacher_id', auth()->id())
            ->where('status', 'open')
            ->first();

        if ($open) {
            return redirect()->route('teacher.attendance.show', $open)
                ->with('info', 'توجد حصة مفتوحة لهذه المجموعة.');
        }

        $session = AttendanceSession::create([
            'study_group_id' => $group->id,
            'teacher_id' => auth()->id(),
            'lesson_number' => $validated['lesson_number'],
            'academic_term_id' => $group->academic_term_id,
            'attendance_date' => now()->toDateString(),
            'started_at' => now(),
            'status' => 'open',
        ]);

        return redirect()->route('teacher.attendance.show', $session);
    }

    /**
     * Show a single session + scan UI + recorded list.
     */
    public function show(AttendanceSession $session): View
    {
        $this->authorizeSession($session);

        $group = $session->group;
        $total = $group->currentStudents();

        // Build the full roster (enrolled students) so the teacher can edit
        // attendance after closing, even for students marked absent.
        $enrollments = GroupEnrollment::where('group_id', $group->id)
            ->where('status', 'enrolled')
            ->with('student')
            ->orderBy('created_at')
            ->get();

        $recordsByStudent = $session->records()
            ->with('student')
            ->get()
            ->keyBy('student_id');

        $recorded = $recordsByStudent->count();
        $remaining = max(0, $total - $recorded);

        // Last scanned student (for the mobile success banner).
        $lastRecord = $session->records()
            ->with('student')
            ->latest('scanned_at')
            ->first();

        return view('teacher.attendance.show', [
            'header' => 'جلسة حضور - ' . $group->title,
            'session' => $session,
            'group' => $group,
            'enrollments' => $enrollments,
            'recordsByStudent' => $recordsByStudent,
            'total' => $total,
            'recorded' => $recorded,
            'remaining' => $remaining,
            'lastRecord' => $lastRecord,
        ]);
    }

    /**
     * Record attendance from a scanned student (regular, while session open).
     * Receives student_id / student_code / qr_token from the QR reading done
     * client-side. The record is persisted immediately and survives refresh.
     */
    public function scan(Request $request, AttendanceSession $session): RedirectResponse
    {
        $this->authorizeSession($session);

        if (! $session->isOpen()) {
            return back()->with('error', 'الحصة مغلقة. افتح صفحة التعديل لتصحيح الحضور.');
        }

        $validated = $request->validate([
            'student_id' => ['nullable', 'integer'],
            'student_code' => ['nullable', 'string'],
            'qr_token' => ['nullable', 'string'],
        ]);

        return $this->recordAttendance($session, $validated, 'present');
    }

    /**
     * Shared validation + persistence for a scanned student.
     *  1. student must exist
     *  2. student's enrollment must belong to THIS group
     *  3. qr_token must match the enrollment (if provided)
     *  4. not already recorded in this session
     *  5. saved immediately
     */
    private function recordAttendance(AttendanceSession $session, array $input, string $type): RedirectResponse
    {
        $group = $session->group;

        // 1. Resolve the student. Prefer the permanent QR token scanned from
        //    the student's card (unguessable, uniquely identifies the enrollment).
        if (! empty($input['qr_token'])) {
            $enrollment = GroupEnrollment::where('group_id', $group->id)
                ->where('status', 'enrolled')
                ->where('qr_token', $input['qr_token'])
                ->first();
            $student = $enrollment?->student;
        } elseif (! empty($input['student_id'])) {
            $student = User::where('role', 'student')
                ->where('id', $input['student_id'])
                ->first();
        } else {
            $student = User::where('role', 'student')
                ->where('student_code', $input['student_code'])
                ->first();
        }

        if (! $student) {
            return back()->with('error', 'كود الطالب غير صحيح.');
        }

        // 2. Student must be enrolled in THIS group.
        $enrollment = GroupEnrollment::where('student_id', $student->id)
            ->where('group_id', $group->id)
            ->where('status', 'enrolled')
            ->first();

        if (! $enrollment) {
            return back()->with('error', 'هذا الطالب غير مسجل في هذه المجموعة. لم يُسجَّل الحضور.');
        }

        // 3. Validate the QR token when provided (permanent per-enrollment code).
        if (! empty($input['qr_token']) && $enrollment->qr_token !== null) {
            if (! hash_equals((string) $enrollment->qr_token, (string) $input['qr_token'])) {
                return back()->with('error', 'كود الطالب غير صحيح.');
            }
        }

        // 4. Prevent duplicate attendance in the same session.
        $exists = AttendanceRecord::where('attendance_session_id', $session->id)
            ->where('student_id', $student->id)
            ->exists();

        if ($exists) {
            return back()->with('error', 'تم تسجيل حضور هذا الطالب مسبقاً.');
        }

        // 5. Persist immediately.
        AttendanceRecord::create([
            'attendance_session_id' => $session->id,
            'student_id' => $student->id,
            'group_id' => $group->id,
            'attendance_type' => $type,
            'scanned_at' => now(),
        ]);

        $msg = $type === 'late' ? 'تم تسجيل الحضور المتأخر.' : 'تم تسجيل الحضور.';
        return back()->with('success', $msg);
    }

    /**
     * Close the session: every enrolled student without a record becomes
     * "absent", then the session is marked closed.
     */
    public function close(Request $request, AttendanceSession $session): RedirectResponse
    {
        $this->authorizeSession($session);

        if ($session->isOpen()) {
            $group = $session->group;

            $enrolledIds = GroupEnrollment::where('group_id', $group->id)
                ->where('status', 'enrolled')
                ->pluck('student_id');

            $recordedIds = AttendanceRecord::where('attendance_session_id', $session->id)
                ->pluck('student_id');

            $absentIds = $enrolledIds->diff($recordedIds);

            foreach ($absentIds as $studentId) {
                AttendanceRecord::create([
                    'attendance_session_id' => $session->id,
                    'student_id' => $studentId,
                    'group_id' => $group->id,
                    'attendance_type' => 'absent',
                    'scanned_at' => now(),
                ]);
            }

            $session->update([
                'status' => 'closed',
                'closed_at' => now(),
            ]);

            // Smart teacher notification: ONE per closed session (source of truth = the page).
            NotificationHelper::notifyTeacher(
                auth()->id(),
                'attendance',
                'تم إغلاق حصة الحضور',
                "تم إغلاق حصة حضور مجموعة {$group->title}.",
                route('teacher.attendance.show', $session)
            );

            // Each enrolled student gets ONE attendance-updated notification (no duplicates).
            $studentIds = NotificationHelper::groupStudentIds([$group->id]);
            NotificationHelper::notifyStudents(
                auth()->id(),
                $studentIds,
                'attendance',
                'تم تحديث الحضور',
                "تم تسجيل حضور مجموعة {$group->title}. راجع سجل حضورك.",
                route('student.dashboard')
            );

            // Apply the consecutive-absence rule after closing.
            $this->applyAbsenceRule($session, $absentIds);
        }

        return redirect()->route('teacher.attendance.show', $session)
            ->with('success', 'تم إنهاء الحصة وتسجيل الغياب.');
    }

    /**
     * Manual correction of a single student's status (present <-> absent).
     * Works on an already-closed session.
     */
    public function updateRecord(Request $request, AttendanceSession $session): RedirectResponse
    {
        $this->authorizeSession($session);

        if ($session->isOpen()) {
            return back()->with('error', 'أغلق الحصة أولاً قبل تصحيح الحضور يدوياً.');
        }

        $validated = $request->validate([
            'student_id' => ['required', 'integer', 'exists:users,id'],
            'status' => ['required', 'in:present,absent'],
        ]);

        // The student must belong to this group.
        $enrollment = GroupEnrollment::where('student_id', $validated['student_id'])
            ->where('group_id', $session->group_id)
            ->where('status', 'enrolled')
            ->first();

        if (! $enrollment) {
            return back()->with('error', 'هذا الطالب غير مسجل في هذه المجموعة.');
        }

        $record = AttendanceRecord::firstOrNew([
            'attendance_session_id' => $session->id,
            'student_id' => $validated['student_id'],
            'group_id' => $session->group_id,
        ]);

        // Re-apply the absence rule whenever a student is switched to absent.
        if ($validated['status'] === 'absent') {
            $record->attendance_type = 'absent';
            $record->scanned_at = $record->scanned_at ?? now();
            $record->save();
            $this->applyAbsenceRule($session, collect([$validated['student_id']]));
        } else {
            $record->attendance_type = 'late';
            $record->scanned_at = $record->scanned_at ?? now();
            $record->save();

            // A present mark clears any removal caused by absence.
            if (in_array($enrollment->status, ['removed', 'inactive_due_to_absence'])) {
                $enrollment->update(['status' => 'enrolled', 'inactivated_at' => null]);
            }
        }

        return back()->with('success', 'تم تحديث حالة الحضور.');
    }

    /**
     * Consecutive-absence rule:
     * If a student has TWO consecutive absences in the same group
     * (ending with this session), the enrollment becomes "removed".
     *
     * The teacher can restore the student manually afterwards.
     */
    private function applyAbsenceRule(AttendanceSession $session, \Illuminate\Support\Collection $absentStudentIds): void
    {
        if ($absentStudentIds->isEmpty()) {
            return;
        }

        foreach ($absentStudentIds as $studentId) {
            $enrollment = GroupEnrollment::where('student_id', $studentId)
                ->where('group_id', $session->group_id)
                ->where('status', 'enrolled')
                ->first();

            if (! $enrollment) {
                continue;
            }

            // Gather this student's full attendance across the group's sessions,
            // ordered by session date descending (most recent first). A 'late'
            // record counts as present, so only 'absent' breaks the streak.
            $history = AttendanceRecord::where('student_id', $studentId)
                ->where('group_id', $session->group_id)
                ->whereHas('session', fn ($q) => $q->where('teacher_id', auth()->id()))
                ->with('session')
                ->get()
                ->sortByDesc(fn ($r) => $r->session->attendance_date->timestamp)
                ->values();

            // Count consecutive absences starting from the most recent one.
            $consecutive = 0;
            foreach ($history as $record) {
                if ($record->attendance_type === 'absent') {
                    $consecutive++;
                } else {
                    break;
                }
            }

            if ($consecutive >= 2) {
                $enrollment->update(['status' => 'inactive_due_to_absence', 'requested_group_id' => null]);

                TeacherActivity::log(
                    auth()->id(),
                    'student_removed_absence',
                    'إزالة بسبب الغياب',
                    "تم إزالة الطالب {$enrollment->student->name} من مجموعة {$session->group->title} لغيابه مرتين متتاليتين."
                );
            }
        }
    }

    public function reopen(AttendanceSession $session): RedirectResponse
    {
        $this->authorizeSession($session);

        if ($session->status !== 'closed') {
            return back()->with('error', 'هذه الجلسة ليست مغلقة.');
        }

        $session->update([
            'status' => 'open',
            'closed_at' => null,
        ]);

        TeacherActivity::log(
            auth()->id(),
            'attendance_session_reopened',
            'إعادة فتح جلسة حضور',
            "تمت إعادة فتح جلسة حضور مجموعة {$session->group->title} (درس رقم {$session->lesson_number})"
        );

        return redirect()->route('teacher.attendance.show', $session)
            ->with('success', 'تمت إعادة فتح الجلسة. يمكنك تسجيل الحضور وتعديله الآن.');
    }

    private function authorizeSession(AttendanceSession $session): void
    {
        if ($session->teacher_id !== auth()->id()) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الجلسة');
        }
    }
}