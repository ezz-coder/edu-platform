<x-dashboard-layout :title="'مركز الإشعارات'">
    <div class="max-w-3xl mx-auto space-y-6">
        <div class="flex items-center justify-between flex-wrap gap-2">
            <h2 class="page-title">مركز الإشعارات</h2>
            @if ($unreadCount > 0)
                <form method="POST" action="{{ route($readAllRoute) }}" class="inline">
                    @csrf
                    <button type="submit" class="btn-secondary btn-sm">
                        تعليم الكل كمقروء ({{ $unreadCount }})
                    </button>
                </form>
            @endif
        </div>

        @if ($notifications->isEmpty())
            <x-empty-state
                icon="bi-bell-slash"
                title="لا توجد إشعارات حالياً"
                message="ستظهر الإشعارات المهمة هنا." />
        @else
            @php
                $unread = $notifications->whereNull('read_at');
                $read = $notifications->whereNotNull('read_at');
            @endphp

            @if ($unread->isNotEmpty())
                <div class="text-xs font-semibold text-text-muted uppercase px-1 mb-2">غير المقروءة</div>
                <div class="space-y-3">
                    @foreach ($unread as $n)
                        <a href="{{ route($openRoute, $n) }}"
                           class="card-hover flex items-center gap-3 p-4 text-decoration-none"
                           style="border-inline-start: 4px solid {{ $n->hex() }};">
                            <span class="rounded-full d-flex align-items-center justify-content-center flex-shrink-0"
                                  style="width: 44px; height: 44px; background-color: {{ $n->hex() }}1a; color: {{ $n->hex() }};">
                                <i class="bi {{ $n->icon() }} fs-5"></i>
                            </span>
                            <div class="flex-grow-1 min-w-0">
                                <div class="flex items-start justify-between gap-2">
                                    <p class="mb-0 font-semibold text-text-primary">{{ $n->title }}</p>
                                    <span class="badge badge-primary flex-shrink-0">جديد</span>
                                </div>
                                @if ($n->message)
                                    <p class="mb-0 text-sm text-text-secondary mt-1">{{ $n->message }}</p>
                                @endif
                                <p class="mb-0 text-xs text-text-muted mt-2">{{ $n->relativeTime() }}</p>
                            </div>
                            <span class="btn btn-sm flex-shrink-0 {{ $n->color() === 'black' ? 'btn-dark' : '' }}"
                                  style="{{ $n->color() === 'black' ? '' : 'background-color: ' . $n->hex() . '1a; color: ' . $n->hex() . ';' }}">
                                {{ $n->actionLabel() }} <i class="bi bi-chevron-left"></i>
                            </span>
                        </a>
                    @endforeach
                </div>
            @endif

            @if ($read->isNotEmpty())
                <div class="text-xs font-semibold text-text-muted uppercase px-1 pt-4 mb-2">المقروءة</div>
                <div class="space-y-3">
                    @foreach ($read as $n)
                        <a href="{{ route($openRoute, $n) }}"
                           class="card-hover flex items-center gap-3 p-4 text-decoration-none opacity-75"
                           style="border-inline-start: 4px solid {{ $n->hex() }};">
                            <span class="rounded-full d-flex align-items-center justify-content-center flex-shrink-0"
                                  style="width: 44px; height: 44px; background-color: {{ $n->hex() }}1a; color: {{ $n->hex() }};">
                                <i class="bi {{ $n->icon() }} fs-5"></i>
                            </span>
                            <div class="flex-grow-1 min-w-0">
                                <p class="mb-0 font-semibold text-text-primary">{{ $n->title }}</p>
                                @if ($n->message)
                                    <p class="mb-0 text-sm text-text-secondary mt-1">{{ $n->message }}</p>
                                @endif
                                <p class="mb-0 text-xs text-text-muted mt-2">{{ $n->relativeTime() }}</p>
                            </div>
                            <span class="btn btn-sm flex-shrink-0 {{ $n->color() === 'black' ? 'btn-dark' : '' }}"
                                  style="{{ $n->color() === 'black' ? '' : 'background-color: ' . $n->hex() . '1a; color: ' . $n->hex() . ';' }}">
                                {{ $n->actionLabel() }} <i class="bi bi-chevron-left"></i>
                            </span>
                        </a>
                    @endforeach
                </div>
            @endif

            <div class="pagination">
                {{ $notifications->links() }}
            </div>
        @endif
    </div>
</x-dashboard-layout>