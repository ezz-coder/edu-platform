<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    /**
     * List notifications for the current user (teacher, assistant or student).
     * Unread first, then newest. Paginated — never load everything.
     */
    public function index(Request $request): View
    {
        $query = $this->baseQuery();

        $notifications = $query
            ->with(['student', 'group', 'assistant'])
            ->orderByRaw('read_at IS NULL DESC')
            ->latest()
            ->paginate(15);

        $unreadCount = (clone $query)->whereNull('read_at')->count();
        $readCount = (clone $query)->whereNotNull('read_at')->count();

        $isAssistant = auth('assistant')->check();
        $readAllRoute = $isAssistant ? 'assistant.notifications.read.all' : 'notifications.read.all';
        $openRoute = $isAssistant ? 'assistant.notifications.open' : 'notifications.open';

        return view('notifications.index', [
            'header' => 'الإشعارات',
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
            'readCount' => $readCount,
            'readAllRoute' => $readAllRoute,
            'openRoute' => $openRoute,
        ]);
    }

    /**
     * Open a notification: mark it read and redirect to its destination.
     * No extra confirmation. Falls back to the center if no link is stored.
     */
    public function open(Notification $notification): RedirectResponse
    {
        $this->authorizeNotification($notification);

        if (! $notification->isRead()) {
            $notification->update(['read_at' => now()]);
        }

        if ($notification->link) {
            return redirect($notification->link);
        }

        return redirect()->route('notifications.index');
    }

    /**
     * Mark a single notification as read (dropdown "مقروء" action).
     */
    public function markRead(Notification $notification): RedirectResponse
    {
        $this->authorizeNotification($notification);
        $notification->update(['read_at' => now()]);

        if (request()->has('back') && request()->get('back') === 'drop') {
            return back();
        }

        return back();
    }

    /**
     * Mark all notifications as read for the current user.
     */
    public function markAllRead(Request $request): RedirectResponse
    {
        $this->baseQuery()->whereNull('read_at')->update(['read_at' => now()]);

        return back()->with('success', 'تم تعليم جميع الإشعارات كمقروءة.');
    }

    /**
     * Build the ownership-scoped query for the current authenticated entity.
     */
    private function baseQuery()
    {
        // Assistants authenticate via a dedicated guard, not the web guard,
        // so the default auth()->user() would be null for them.
        if (auth('assistant')->check()) {
            return Notification::where('assistant_id', auth('assistant')->id());
        }

        $user = auth()->user();

        if ($user?->role === 'teacher') {
            // Teacher inbox: only teacher-owned rows. Rows with student_id
            // belong to the student inbox (they also store teacher_id for scoping).
            return Notification::where('teacher_id', $user->id)
                ->whereNull('assistant_id')
                ->whereNull('student_id');
        }

        if ($user?->role === 'student') {
            return Notification::where('student_id', $user->id);
        }

        abort(403);
    }

    private function authorizeNotification(Notification $notification): void
    {
        if (auth('assistant')->check()) {
            if ($notification->assistant_id !== auth('assistant')->id()) {
                abort(403);
            }
            return;
        }

        $user = auth()->user();

        if ($user?->role === 'teacher') {
            if (
                $notification->teacher_id !== $user->id
                || $notification->assistant_id !== null
                || $notification->student_id !== null
            ) {
                abort(403);
            }
            return;
        }

        if ($user?->role === 'student') {
            if ($notification->student_id !== $user->id) {
                abort(403);
            }
            return;
        }

        if (auth('assistant')->check()) {
            if ($notification->assistant_id !== auth('assistant')->id()) {
                abort(403);
            }
            return;
        }

        abort(403);
    }
}