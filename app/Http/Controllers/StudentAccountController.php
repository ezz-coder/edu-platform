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
            $attendance = \DB::table('attendance_records')
                ->join('attendance_sessions', 'attendance_records.attendance_session_id', '=', 'attendance_sessions.id')
                ->where('attendance_sessions.study_group_id', $enrollment->group_id)
                ->where('attendance_records.student_id', $account->id)
                ->selectRaw('status, count(*) as c')
                ->groupBy('status')
                ->pluck('c', 'status')
                ->toArray();
        }

        $examAttempts = \App\Models\ExamAttempt::where('student_id', $account->id)
            ->with('exam')
            ->where('status', 'finished')
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
            $attendance = \DB::table('attendance_records')
                ->join('attendance_sessions', 'attendance_records.attendance_session_id', '=', 'attendance_sessions.id')
                ->where('attendance_sessions.study_group_id', $enrollment->group_id)
                ->where('attendance_records.student_id', $account->id)
                ->selectRaw('status, count(*) as c')
                ->groupBy('status')
                ->pluck('c', 'status')
                ->toArray();
        }

        $examAttempts = \App\Models\ExamAttempt::where('student_id', $account->id)
            ->with('exam')
            ->where('status', 'finished')
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
}
