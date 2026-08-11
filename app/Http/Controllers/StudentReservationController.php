<?php

namespace App\Http\Controllers;

use App\Models\GroupEnrollment;
use App\Models\Notification;
use App\Models\Reservation;
use App\Models\ReservationItem;
use App\Models\StudyGroup;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentReservationController extends Controller
{
    /**
     * List reservations open for the student's active group.
     */
    public function index(): View
    {
        $account = auth()->user()->activeStudentAccount();
        $group = $this->activeGroup($account);

        $reservations = collect();
        if ($group) {
            $reservations = Reservation::where('teacher_id', $group->teacher_id)
                ->where('status', 'open')
                ->whereHas('targetGroups', fn ($q) => $q->where('group_id', $group->id))
                ->with(['items' => fn ($q) => $q->where('student_id', $account->id)])
                ->latest()
                ->paginate(15);
        }

        // Orange dot: count unread reservation notifications for this student.
        $unreadReservationCount = 0;
        if ($account) {
            $unreadReservationCount = Notification::where('student_id', $account->id)
                ->where('type', 'reservation')
                ->whereNull('read_at')
                ->count();
        }

        return view('student.reservations.index', [
            'header' => 'الحجوزات',
            'account' => $account,
            'group' => $group,
            'reservations' => $reservations,
            'unreadReservationCount' => $unreadReservationCount,
            'types' => Reservation::types(),
        ]);
    }

    /**
     * Show student's reservation history (all reservations they've made).
     */
    public function history(): View
    {
        $account = auth()->user()->activeStudentAccount();

        $history = collect();
        if ($account) {
            $history = ReservationItem::where('student_id', $account->id)
                ->with(['reservation', 'group'])
                ->latest('reserved_at')
                ->paginate(20);
        }

        return view('student.reservations.history', [
            'header' => 'سجل الحجوزات',
            'history' => $history,
        ]);
    }

    public function show(Reservation $reservation): View
    {
        $account = auth()->user()->activeStudentAccount();
        $group = $this->activeGroup($account);

        if (! $group || ! $reservation->targetGroups()->where('group_id', $group->id)->exists()) {
            abort(403);
        }

        $already = ReservationItem::where('reservation_id', $reservation->id)
            ->where('student_id', $account->id)
            ->first();

        return view('student.reservations.show', [
            'header' => $reservation->title,
            'reservation' => $reservation,
            'group' => $group,
            'already' => $already,
        ]);
    }

    /**
      * Student reserves one unit.
      */
    public function reserve(Request $request, Reservation $reservation): RedirectResponse
    {
        $account = auth()->user()->activeStudentAccount();
        $group = $this->activeGroup($account);

        // Check if student is active in the group
        if (! $account || ! $group) {
            abort(403, 'حساب الطالب غير مفعل أو غير مسجل في مجموعة.');
        }

        if (! $reservation->targetGroups()->where('group_id', $group->id)->exists()) {
            abort(403);
        }

        if (! $reservation->isOpen()) {
            return back()->with('error', 'الحجز مغلق أو خارج الموعد.');
        }

        // For limited reservations, check quantity
        if (! $reservation->isUnlimited() && $reservation->remaining() <= 0) {
            return back()->with('error', 'نفدت الكمية المتاحة.');
        }

        // Check if student is inactive (removed or due to absence)
        $enrollment = GroupEnrollment::where('student_id', $account->id)
            ->where('group_id', $group->id)
            ->first();

        if ($enrollment && $enrollment->isInactive()) {
            abort(403, 'حساب الطالب غير مفعل. لا يمكنك الحجز.');
        }

        $exists = ReservationItem::where('reservation_id', $reservation->id)
            ->where('student_id', $account->id)
            ->exists();

        if ($exists) {
            return back()->with('error', 'لقد قمت بالحجز مسبقاً.');
        }

        ReservationItem::create([
            'reservation_id' => $reservation->id,
            'student_id' => $account->id,
            'group_id' => $group->id,
            'reserved_at' => now(),
            'delivery_status' => 'pending',
        ]);

        // Auto-close if quantity is now zero (for limited reservations)
        if (! $reservation->isUnlimited() && $reservation->remaining() <= 0) {
            $reservation->update(['status' => 'closed']);
        }

        return back()->with('success', 'تم الحجز بنجاح.');
    }

    private function activeGroup(?User $account): ?StudyGroup
    {
        if (! $account) {
            return null;
        }

        $enrollment = GroupEnrollment::where('student_id', $account->id)
            ->where('status', 'enrolled')
            ->with('group')
            ->first();

        return $enrollment?->group;
    }
}