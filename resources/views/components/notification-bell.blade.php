@php
    // Resolve the current authenticated entity across all three guards.
    $guard = 'web';
    $user = auth()->user();
    $assistant = null;

    if ($user && $user->role === 'teacher') {
        $ownerColumn = 'teacher_id';
        $ownerId = $user->id;
        $baseQuery = \App\Models\Notification::where('teacher_id', $user->id)
            ->whereNull('assistant_id')
            ->whereNull('student_id');
        $openRoute = 'notifications.open';
        $readRoute = 'notifications.read';
        $allRoute = 'notifications.read.all';
        $indexRoute = 'notifications.index';
    } elseif ($user && $user->role === 'student') {
        $ownerColumn = 'student_id';
        $ownerId = $user->id;
        $baseQuery = \App\Models\Notification::where('student_id', $user->id);
        $openRoute = 'notifications.open';
        $readRoute = 'notifications.read';
        $allRoute = 'notifications.read.all';
        $indexRoute = 'notifications.index';
    } elseif (auth('assistant')->check()) {
        $assistant = auth('assistant')->user();
        $ownerColumn = 'assistant_id';
        $ownerId = $assistant->id;
        $baseQuery = \App\Models\Notification::where('assistant_id', $assistant->id);
        $openRoute = 'assistant.notifications.open';
        $readRoute = 'assistant.notifications.read';
        $allRoute = 'assistant.notifications.read.all';
        $indexRoute = 'assistant.notifications.index';
    } else {
        return;
    }

    $unread = (clone $baseQuery)->whereNull('read_at');
    $unreadCount = $unread->count();

    // Determine the dot color:
    //  - newest unread notification's type color
    //  - RED when more than one distinct unread type exists
    $dotColor = null;
    if ($unreadCount > 0) {
        $types = $unread->distinct('type')->pluck('type');
        if ($types->count() > 1) {
            $dotColor = config('notifications.multi_color');
        } else {
            $newest = $unread->latest()->first();
            $dotColor = $newest->hex();
        }
    }

    // Unread first (limited), then the rest (read) to fill the dropdown.
    $unreadItems = (clone $baseQuery)
        ->whereNull('read_at')
        ->with(['student', 'group', 'assistant'])
        ->latest()
        ->limit(6)
        ->get();

    $readItems = collect();
    if ($unreadItems->count() < 8) {
        $readItems = (clone $baseQuery)
            ->whereNotNull('read_at')
            ->with(['student', 'group', 'assistant'])
            ->latest()
            ->limit(8 - $unreadItems->count())
            ->get();
    }
@endphp

<div class="dropdown relative">
    <button type="button" class="btn-icon text-text-secondary hover:bg-gray-100 focus-ring transition-colors duration-150" data-bs-toggle="dropdown" aria-expanded="false" aria-label="الإشعارات">
        <i class="bi bi-bell text-xl"></i>
        @if ($unreadCount > 0)
            <span class="absolute top-1 left-1 w-2.5 h-2.5 rounded-full border-2 border-bg-card" style="background-color: {{ $dotColor }};"></span>
        @endif
    </button>

    <div class="dropdown-menu dropdown-menu-end p-0 shadow-dropdown card fade-in" style="width: 22rem; max-height: 30rem; overflow-y: auto;">
        <div class="flex items-center justify-between px-4 py-3 border-b border-border-light sticky-top bg-bg-card">
            <span class="font-semibold text-text-primary">الإشعارات</span>
            <div class="flex items-center gap-3">
                @if ($unreadCount > 0)
                    <form method="POST" action="{{ route($allRoute) }}" class="inline">
                        @csrf
                        <button type="submit" class="text-sm text-text-secondary hover:text-primary-600 transition-colors duration-150">تعليم الكل كمقروء</button>
                    </form>
                @endif
                <a href="{{ route($indexRoute) }}" class="text-sm text-primary-600 hover:text-primary-700 transition-colors duration-150 font-medium">عرض الكل</a>
            </div>
        </div>

        @if ($unreadItems->isEmpty() && $readItems->isEmpty())
            <div class="px-4 py-8 text-center text-sm text-text-muted">لا توجد إشعارات حالياً</div>
        @else
            {{-- Unread section --}}
            @if ($unreadItems->isNotEmpty())
                <div class="px-4 pt-3 pb-1 text-xs font-semibold text-text-muted uppercase">غير المقروءة</div>
                @foreach ($unreadItems as $n)
                    <a href="{{ route($openRoute, $n) }}" class="flex gap-3 px-4 py-3 border-b border-border-light text-decoration-none bg-primary-50/30 hover:bg-gray-50 transition-colors duration-150 items-stretch">
                        <span class="rounded-full flex items-center justify-center flex-shrink-0 self-center" style="width: 36px; height: 36px; background-color: {{ $n->hex() }}1a; color: {{ $n->hex() }};">
                            <i class="bi {{ $n->icon() }}"></i>
                        </span>
                        <div class="flex-grow-1 min-w-0">
                            <div class="flex items-start justify-between gap-2">
                                <p class="mb-0 text-sm font-medium text-text-primary truncate">{{ $n->title }}</p>
                                <span class="flex-shrink-0 mt-1" style="width:8px;height:8px;border-radius:50%;background-color:{{ $n->hex() }};"></span>
                            </div>
                            @if ($n->message)
                                <p class="mb-0 text-sm text-text-secondary mt-1 truncate">{{ $n->message }}</p>
                            @endif
                            <p class="mb-0 text-xs text-text-muted mt-1">{{ $n->relativeTime() }}</p>
                        </div>
                        <span class="btn btn-sm btn-link flex-shrink-0 self-center p-0 {{ $n->color() === 'black' ? 'text-white' : '' }}" style="color: {{ $n->hex() }};">
                            {{ $n->actionLabel() }} <i class="bi bi-chevron-left"></i>
                        </span>
                    </a>
                @endforeach
            @endif

            {{-- Read section --}}
            @if ($readItems->isNotEmpty())
                <div class="px-4 pt-3 pb-1 text-xs font-semibold text-text-muted uppercase">المقروءة</div>
                @foreach ($readItems as $n)
                    <a href="{{ route($openRoute, $n) }}" class="flex gap-3 px-4 py-3 border-b border-border-light text-decoration-none hover:bg-gray-50 transition-colors duration-150 items-stretch opacity-75">
                        <span class="rounded-full flex items-center justify-center flex-shrink-0 self-center" style="width: 36px; height: 36px; background-color: {{ $n->hex() }}1a; color: {{ $n->hex() }};">
                            <i class="bi {{ $n->icon() }}"></i>
                        </span>
                        <div class="flex-grow-1 min-w-0">
                            <p class="mb-0 text-sm font-medium text-text-primary truncate">{{ $n->title }}</p>
                            @if ($n->message)
                                <p class="mb-0 text-sm text-text-secondary mt-1 truncate">{{ $n->message }}</p>
                            @endif
                            <p class="mb-0 text-xs text-text-muted mt-1">{{ $n->relativeTime() }}</p>
                        </div>
                        <span class="btn btn-sm btn-link flex-shrink-0 self-center p-0" style="color: {{ $n->hex() }};">
                            {{ $n->actionLabel() }} <i class="bi bi-chevron-left"></i>
                        </span>
                    </a>
                @endforeach
            @endif
        @endif
    </div>
</div>