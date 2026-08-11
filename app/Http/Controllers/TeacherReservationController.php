<?php

namespace App\Http\Controllers;

use App\Models\AcademicTerm;
use App\Models\ActivityLog;
use App\Models\Notification;
use App\Models\Reservation;
use App\Models\ReservationItem;
use App\Models\StudyGroup;
use App\Models\TeacherActivity;
use App\Utilities\NotificationHelper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TeacherReservationController extends Controller
{
    private function activeTermId(): ?int
    {
        $term = AcademicTerm::where('teacher_id', auth()->id())
            ->where('status', 'active')
            ->first();

        return $term?->id;
    }

    /**
     * Log activity for a reservation action.
     */
    private function logActivity(string $action, string $title, ?string $description = null): void
    {
        ActivityLog::log([
            'actor_id' => auth()->id(),
            'actor_type' => 'teacher',
            'actor_type_label' => 'المدرس',
            'action' => $action,
            'category' => 'reservation',
            'description' => $description,
        ]);
    }

    public function index(): View
    {
        $reservations = Reservation::where('teacher_id', auth()->id())
            ->withCount(['items as reserved_count'])
            ->with(['targetGroups' => function ($q) {
                $q->select('study_groups.id', 'study_groups.title');
            }])
            ->withCount(['items as delivered_count' => function ($q) {
                $q->where('delivery_status', 'delivered');
            }])
            ->withCount(['items as not_delivered_count' => function ($q) {
                $q->where('delivery_status', 'pending');
            }])
            ->latest()
            ->paginate(15);

        return view('teacher.reservations.index', [
            'header' => 'الحجوزات',
            'reservations' => $reservations,
        ]);
    }

    public function create(): View
    {
        $groups = StudyGroup::where('teacher_id', auth()->id())->orderBy('title')->get();

        return view('teacher.reservations.create', [
            'header' => 'إنشاء حجز',
            'groups' => $groups,
            'types' => Reservation::types(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['required', 'string', 'in:' . implode(',', array_keys(Reservation::types()))],
            'quantity' => ['nullable', 'integer', 'min:1'],
            'open_date' => ['nullable', 'date'],
            'close_date' => ['nullable', 'date', 'after_or_equal:open_date'],
            'group_ids' => ['required', 'array', 'min:1'],
            'group_ids.*' => ['integer', 'exists:study_groups,id'],
        ]);

        $reservation = Reservation::create([
            'teacher_id' => auth()->id(),
            'academic_term_id' => $this->activeTermId(),
            'title' => $validated['title'],
            'description' => $validated['description'],
            'type' => $validated['type'],
            'quantity' => $validated['quantity'] ?? 0,
            'open_date' => $validated['open_date'] ?? null,
            'close_date' => $validated['close_date'] ?? null,
            'status' => 'draft',
        ]);

        $reservation->targetGroups()->sync($validated['group_ids']);

        TeacherActivity::log(auth()->id(), 'reservation_created', 'إنشاء حجز', "تم إنشاء حجز {$reservation->title}");
        $this->logActivity('reservation_created', 'إنشاء حجز', "تم إنشاء حجز {$reservation->title}");

        return redirect()->route('teacher.reservations.index')->with('success', 'تم إنشاء الحجز بنجاح');
    }

    public function show(Reservation $reservation): View
    {
        $this->authorizeReservation($reservation);

        $items = $reservation->items()
            ->with(['student', 'group'])
            ->latest('reserved_at')
            ->paginate(20);

        return view('teacher.reservations.show', [
            'header' => $reservation->title,
            'reservation' => $reservation,
            'items' => $items,
        ]);
    }

    public function edit(Reservation $reservation): View
    {
        $this->authorizeReservation($reservation);

        if ($reservation->isArchived()) {
            abort(403, 'لا يمكن تعديل حجز مؤرشف.');
        }

        $groups = StudyGroup::where('teacher_id', auth()->id())->orderBy('title')->get();

        return view('teacher.reservations.edit', [
            'header' => 'تعديل حجز',
            'reservation' => $reservation,
            'groups' => $groups,
            'types' => Reservation::types(),
        ]);
    }

    public function update(Request $request, Reservation $reservation): RedirectResponse
    {
        $this->authorizeReservation($reservation);

        if ($reservation->isArchived()) {
            abort(403, 'لا يمكن تعديل حجز مؤرشف.');
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['required', 'string', 'in:' . implode(',', array_keys(Reservation::types()))],
            'quantity' => ['nullable', 'integer', 'min:1'],
            'open_date' => ['nullable', 'date'],
            'close_date' => ['nullable', 'date', 'after_or_equal:open_date'],
            'status' => ['required', 'in:draft,open,closed'],
            'group_ids' => ['required', 'array', 'min:1'],
            'group_ids.*' => ['integer', 'exists:study_groups,id'],
        ]);

        $oldStatus = $reservation->status;

        $reservation->update([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'type' => $validated['type'],
            'quantity' => $validated['quantity'] ?? 0,
            'open_date' => $validated['open_date'],
            'close_date' => $validated['close_date'],
            'status' => $validated['status'],
        ]);

        $reservation->targetGroups()->sync($validated['group_ids']);

        // Log status changes
        if ($oldStatus !== $validated['status']) {
            $statusLabel = match($validated['status']) {
                'open' => 'فتح حجز',
                'closed' => 'إغلاق حجز',
                'draft' => 'تعديل حجز (مسودة)',
                default => 'تحديث حجز',
            };
            TeacherActivity::log(auth()->id(), 'reservation_updated', $statusLabel, "تم {$statusLabel} {$reservation->title}");
            $this->logActivity('reservation_updated', $statusLabel, "تم {$statusLabel} {$reservation->title}");

            // Notify students when opening or reopening a reservation
            if ($validated['status'] === 'open') {
                $studentIds = NotificationHelper::groupStudentIds($validated['group_ids']);
                NotificationHelper::notifyStudents(
                    auth()->id(),
                    $studentIds,
                    'reservation',
                    'حجز جديد متاح',
                    "تم فتح حجز جديد: {$reservation->title}",
                    route('student.reservations.show', $reservation)
                );
            } elseif ($validated['status'] === 'closed') {
                $studentIds = NotificationHelper::groupStudentIds($validated['group_ids']);
                NotificationHelper::notifyStudents(
                    auth()->id(),
                    $studentIds,
                    'reservation',
                    'تم إغلاق الحجز',
                    "تم إغلاق الحجز: {$reservation->title}. لا يمكن الحجز بعد الآن.",
                    route('student.reservations.show', $reservation)
                );
            }
        }

        return redirect()->route('teacher.reservations.index')->with('success', 'تم تحديث الحجز بنجاح');
    }

    public function destroy(Reservation $reservation): RedirectResponse
    {
        $this->authorizeReservation($reservation);

        if ($reservation->isArchived()) {
            abort(403, 'لا يمكن حذف حجز مؤرشف. استخدم الاسترجاع أو الحذف النهائي.');
        }

        $reservation->delete();

        TeacherActivity::log(auth()->id(), 'reservation_deleted', 'حذف حجز', "تم حذف حجز {$reservation->title}");
        $this->logActivity('reservation_deleted', 'حذف حجز', "تم حذف حجز {$reservation->title}");

        return redirect()->route('teacher.reservations.index')->with('success', 'تم حذف الحجز بنجاح');
    }

    public function recycleBin(): View
    {
        $deleted = Reservation::onlyTrashed()
            ->where('teacher_id', auth()->id())
            ->latest()
            ->paginate(20);

        return view('teacher.recycle-bin.index', [
            'header' => 'سلة المحذوفات - الحجوزات',
            'items' => $deleted,
            'type' => 'reservation',
        ]);
    }

    public function restore(Reservation $reservation): RedirectResponse
    {
        $this->authorizeReservation($reservation);

        if ($reservation->trashed()) {
            $reservation->restore();
            TeacherActivity::log(auth()->id(), 'reservation_restored', 'استرجاع حجز', "تم استرجاع حجز {$reservation->title}");
            $this->logActivity('reservation_restored', 'استرجاع حجز', "تم استرجاع حجز {$reservation->title}");
        }

        return back()->with('success', 'تم استرجاع الحجز بنجاح');
    }

    public function forceDelete(Reservation $reservation): RedirectResponse
    {
        $this->authorizeReservation($reservation);

        if ($reservation->trashed()) {
            $reservation->forceDelete();
            TeacherActivity::log(auth()->id(), 'reservation_permanent_deleted', 'حذف نهائي حجز', "تم حذف حجز {$reservation->title} نهائياً");
            $this->logActivity('reservation_permanent_deleted', 'حذف نهائي حجز', "تم حذف حجز {$reservation->title} نهائياً");
        }

        return back()->with('success', 'تم الحذف النهائي بنجاح');
    }

    /**
     * Archive a reservation (read-only history).
     */
    public function archive(Reservation $reservation): RedirectResponse
    {
        $this->authorizeReservation($reservation);

        if ($reservation->isArchived()) {
            return back()->with('error', 'الحجز مؤرشف بالفعل.');
        }

        $reservation->update(['status' => 'archived']);

        TeacherActivity::log(auth()->id(), 'reservation_archived', 'أرشفة حجز', "تم أرشفة حجز {$reservation->title}");
        $this->logActivity('reservation_archived', 'أرشفة حجز', "تم أرشفة حجز {$reservation->title}");

        return back()->with('success', 'تم أرشفة الحجز بنجاح');
    }

    /**
      * Reopen a closed reservation (restore availability).
      */
    public function reopen(Reservation $reservation): RedirectResponse
    {
        $this->authorizeReservation($reservation);

        if (! $reservation->isClosed()) {
            return back()->with('error', 'الحجز ليس مغلقاً.');
        }

        if ($reservation->isArchived()) {
            abort(403, 'لا يمكن إعادة فتح حجز مؤرشف.');
        }

        $reservation->update(['status' => 'open']);

        TeacherActivity::log(auth()->id(), 'reservation_reopened', 'إعادة فتح حجز', "تم إعادة فتح حجز {$reservation->title}");
        $this->logActivity('reservation_reopened', 'إعادة فتح حجز', "تم إعادة فتح حجز {$reservation->title}");

        // Notify students about reopening
        $studentIds = NotificationHelper::groupStudentIds($reservation->targetGroups->pluck('id')->all());
        NotificationHelper::notifyStudents(
            auth()->id(),
            $studentIds,
            'reservation',
            'تم إعادة فتح الحجز',
            "تم إعادة فتح الحجز: {$reservation->title}. يمكنكم الحجز الآن.",
            route('student.reservations.show', $reservation)
        );

        return back()->with('success', 'تم إعادة فتح الحجز بنجاح.');
    }

    /**
      * Mark a reservation item as delivered.
      */
    public function markDelivered(Reservation $reservation, ReservationItem $item): RedirectResponse
    {
        $this->authorizeReservation($reservation);

        if ($reservation->isArchived()) {
            abort(403, 'لا يمكن تعديل حجز مؤرشف.');
        }

        $item->update([
            'delivery_status' => 'delivered',
            'delivered_at' => now(),
        ]);

        TeacherActivity::log(auth()->id(), 'delivery_confirmed', 'تأكيد تسليم', "تم تسليم حجز إلى {$item->student->name} للحجز {$reservation->title}");
        $this->logActivity('delivery_confirmed', 'تأكيد تسليم', "تم تسليم حجز إلى {$item->student->name} للحجز {$reservation->title}");

        return back()->with('success', 'تم تأكيد التسليم.');
    }

    /**
     * Mark a reservation item as not delivered (undo delivery).
     */
    public function undoDelivery(Reservation $reservation, ReservationItem $item): RedirectResponse
    {
        $this->authorizeReservation($reservation);

        if ($reservation->isArchived()) {
            abort(403, 'لا يمكن تعديل حجز مؤرشف.');
        }

        $item->update([
            'delivery_status' => 'pending',
            'delivered_at' => null,
        ]);

        TeacherActivity::log(auth()->id(), 'delivery_cancelled', 'إلغاء تسليم', "تم إلغاء تسليم حجز من {$item->student->name} للحجز {$reservation->title}");
        $this->logActivity('delivery_cancelled', 'إلغاء تسليم', "تم إلغاء تسليم حجز من {$item->student->name} للحجز {$reservation->title}");

        return back()->with('success', 'تم إلغاء التسليم.');
    }

    private function authorizeReservation(Reservation $reservation): void
    {
        if ($reservation->teacher_id !== auth()->id()) {
            abort(403);
        }
    }
}