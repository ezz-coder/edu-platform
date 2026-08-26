<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentAccountController extends Controller
{
    /**
     * Show the account selection page for the authenticated student.
     * Lists every student account sharing the logged-in user's mobile.
     */
    public function selection(): View
    {
        $user = auth()->user();

        $accounts = User::where('role', 'student')
            ->where('mobile', $user->mobile)
            ->with(['enrollments' => fn ($q) => $q->where('status', 'enrolled')->with('group')])
            ->orderBy('name')
            ->get();

        return view('student.accounts', [
            'header' => 'اختيار الحساب',
            'accounts' => $accounts,
        ]);
    }

    /**
     * Switch the active account by storing its id in the session.
     */
    public function switch(Request $request): RedirectResponse
    {
        $request->validate([
            'account_id' => ['required', 'exists:users,id'],
        ]);

        $account = User::where('id', $request->account_id)
            ->where('role', 'student')
            ->where('mobile', auth()->user()->mobile)
            ->firstOrFail();

        session(['active_student_id' => $account->id]);

        return redirect()->route('student.resources.home');
    }

    /**
     * PART 5: "My Account" page (replaces dashboard).
     */
    public function show(): View
    {
        $account = auth()->user()->activeStudentAccount();
        $enrollment = $account->enrollments()->with(['group', 'group.teacher', 'term'])
            ->whereIn('status', ['enrolled', 'pending_transfer', 'inactive_due_to_absence'])
            ->orWhere(fn ($q) => $q->where('student_id', $account->id)->where('status', 'inactive_due_to_teacher'))
            ->latest()
            ->first();

        $attendance = [];
        if ($enrollment) {
            // Bugfix (Sprint 65): attendance_records has no `status` column;
            // the real column is `attendance_type` (regular | late | absent).
            $raw = \DB::table('attendance_records')
                ->join('attendance_sessions', 'attendance_records.attendance_session_id', '=', 'attendance_sessions.id')
                ->where('attendance_sessions.study_group_id', $enrollment->group_id)
                ->where('attendance_records.student_id', $account->id)
                ->selectRaw('attendance_records.attendance_type as status, count(*) as c')
                ->groupBy('attendance_records.attendance_type')
                ->pluck('c', 'status')
                ->toArray();

            $attendance = [
                'present' => ($raw['regular'] ?? 0) + ($raw['present'] ?? 0),
                'late'    => $raw['late'] ?? 0,
                'absent'  => $raw['absent'] ?? 0,
            ];
        }

        $examAttempts = \App\Models\ExamAttempt::where('student_id', $account->id)
            ->with('exam')
            ->where('status', 'finished')
            ->latest()
            ->limit(5)
            ->get();

        $reservations = \App\Models\Reservation::whereHas('items', function ($q) use ($account) {
                $q->where('student_id', $account->id);
            })
            ->with('items')
            ->latest()
            ->limit(5)
            ->get();

        return view('student.account', [
            'header' => 'حسابي',
            'account' => $account,
            'enrollment' => $enrollment,
            'attendance' => $attendance,
            'examAttempts' => $examAttempts,
            'reservations' => $reservations,
        ]);
    }

    /**
     * PART 6: Clean printable PDF-style report for sharing with a parent.
     */
    public function report(): \Illuminate\View\View
    {
        $account = auth()->user()->activeStudentAccount();
        $enrollment = $account->enrollments()->with(['group', 'group.teacher', 'term'])->where('status', '!=', 'removed')->first();

        $attendance = [];
        if ($enrollment) {
            // Bugfix (Sprint 65): same attendance_type fix as the account page.
            $raw = \DB::table('attendance_records')
                ->join('attendance_sessions', 'attendance_records.attendance_session_id', '=', 'attendance_sessions.id')
                ->where('attendance_sessions.study_group_id', $enrollment->group_id)
                ->where('attendance_records.student_id', $account->id)
                ->selectRaw('attendance_records.attendance_type as status, count(*) as c')
                ->groupBy('attendance_records.attendance_type')
                ->pluck('c', 'status')
                ->toArray();

            $attendance = [
                'present' => ($raw['regular'] ?? 0) + ($raw['present'] ?? 0),
                'late'    => $raw['late'] ?? 0,
                'absent'  => $raw['absent'] ?? 0,
            ];
        }

        $examAttempts = \App\Models\ExamAttempt::where('student_id', $account->id)
            ->with('exam')
            ->where('status', 'finished')
            ->latest()
            ->limit(20)
            ->get();

        $reservations = \App\Models\Reservation::whereHas('items', function ($q) use ($account) {
                $q->where('student_id', $account->id);
            })
            ->with('items')
            ->latest()
            ->limit(20)
            ->get();

        return view('student.report', [
            'account' => $account,
            'enrollment' => $enrollment,
            'attendance' => $attendance,
            'examAttempts' => $examAttempts,
            'reservations' => $reservations,
        ]);
    }

    /**
     * Sprint 65: Separate Grades page.
     * Shows only the active account's finished exam attempts (latest first),
     * same privacy rules as "حسابي" / report (limit 20 like the report).
     */
    public function grades(): View
    {
        $account = auth()->user()->activeStudentAccount();

        $examAttempts = \App\Models\ExamAttempt::where('student_id', $account->id)
            ->with('exam')
            ->where('status', 'finished')
            ->orderByDesc('finished_at')
            ->limit(20)
            ->get();

        return view('student.grades', [
            'header' => 'درجاتي',
            'account' => $account,
            'examAttempts' => $examAttempts,
        ]);
    }

    /**
     * Sprint 65: Separate Attendance page.
     * Shows only the active account's attendance records for his active
     * enrollment group: summary counts + detailed session history.
     */
    public function attendance(): View
    {
        $account = auth()->user()->activeStudentAccount();
        $enrollment = $account->enrollments()->with('group')
            ->whereIn('status', ['enrolled', 'pending_transfer', 'inactive_due_to_absence'])
            ->orWhere(fn ($q) => $q->where('student_id', $account->id)->where('status', 'inactive_due_to_teacher'))
            ->latest()
            ->first();

        $summary = [];
        $records = collect();

        if ($enrollment) {
            $base = \DB::table('attendance_records')
                ->join('attendance_sessions', 'attendance_records.attendance_session_id', '=', 'attendance_sessions.id')
                ->where('attendance_sessions.study_group_id', $enrollment->group_id)
                ->where('attendance_records.student_id', $account->id);

            $raw = (clone $base)
                ->selectRaw('attendance_records.attendance_type as status, count(*) as c')
                ->groupBy('attendance_records.attendance_type')
                ->pluck('c', 'status')
                ->toArray();

            // Map DB values (regular|late|absent) to display keys.
            $summary = [
                'present' => ($raw['regular'] ?? 0) + ($raw['present'] ?? 0),
                'late'    => $raw['late'] ?? 0,
                'absent'  => $raw['absent'] ?? 0,
            ];

            $records = (clone $base)
                ->select(
                    'attendance_records.attendance_type',
                    'attendance_records.scanned_at',
                    'attendance_sessions.attendance_date',
                    'attendance_sessions.lesson_number'
                )
                ->orderByDesc('attendance_sessions.attendance_date')
                ->get()
                ->map(function ($r) {
                    $r->status = match ($r->attendance_type) {
                        'absent' => 'absent',
                        'late' => 'late',
                        default => 'present',
                    };
                    return $r;
                });
        }

        $total = array_sum($summary);
        // Summary keys: present | late | absent.
        $presentCount = ($summary['present'] ?? 0) + ($summary['late'] ?? 0);
        $percentage = $total > 0 ? round(($presentCount / $total) * 100) : null;

        return view('student.attendance', [
            'header' => 'حضوري',
            'account' => $account,
            'enrollment' => $enrollment,
            'summary' => $summary,
            'records' => $records,
            'total' => $total,
            'percentage' => $percentage,
        ]);
    }

    /**
     * Sprint 65: Student Settings page.
     * Reuses existing profile/password logic and adds mobile change.
     */
    public function settings(): View
    {
        $account = auth()->user()->activeStudentAccount();

        return view('student.settings', [
            'header' => 'الإعدادات',
            'account' => $account,
        ]);
    }

    /**
     * Sprint 65: Change the student's mobile number from his own account.
     *
     * The mobile is the multi-account grouping key, so the new number is
     * applied to ALL student accounts sharing the current mobile — this keeps
     * account switching (active_student_id resolution) working correctly.
     * Identity fields (card_id, student_code, qr_token) are never touched.
     */
    public function updateMobile(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'mobile' => ['required', 'string', 'max:20', 'regex:/^[0-9+\s\-]+$/'],
        ], [
            'mobile.regex' => 'رقم الهاتف غير صحيح.',
            'mobile.unique' => 'رقم الهاتف مستخدم بالفعل بحساب آخر.',
        ]);

        $currentMobile = auth()->user()->mobile;

        // Prevent duplicates: the new mobile must not belong to another
        // account family (accounts outside the current multi-account group).
        $duplicate = User::where('mobile', $validated['mobile'])
            ->where('mobile', '!=', $currentMobile)
            ->exists();
        if ($duplicate) {
            return back()->withErrors(['mobile' => 'رقم الهاتف مستخدم بالفعل بحساب آخر.'])->withInput();
        }

        User::where('role', 'student')
            ->where('mobile', $currentMobile)
            ->update(['mobile' => $validated['mobile']]);

        // Keep the session resolver consistent with the new mobile.
        session(['active_student_id' => auth()->user()->activeStudentAccount()?->id]);

        return redirect()->route('student.settings')->with('success', 'تم تحديث رقم الهاتف بنجاح.');
    }
}
