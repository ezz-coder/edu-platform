<x-dashboard-layout title="الحجوزات">
    <div class="max-w-2xl mx-auto space-y-6">
        <x-page-header title="الحجوزات" description="الحجوزات المتاحة لمجموعتك" />

        @if ($unreadReservationCount > 0)
            <div class="card p-4">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 bg-warning-500 rounded-full animate-pulse"></span>
                    <span class="text-sm text-text-secondary">لديك إشعارات جديدة ({{ $unreadReservationCount }})</span>
                </div>
            </div>
        @endif

        <div class="space-y-4">
            @forelse ($reservations as $reservation)
                <div class="card-hover p-4">
                    <div class="flex items-start justify-between mb-2">
                        <h2 class="font-bold text-text-primary">{{ $reservation->title }}</h2>
                        <span class="badge badge-neutral">{{ $reservation->typeLabel() }}</span>
                    </div>
                    <p class="text-sm text-text-secondary mb-3">{{ Str::limit($reservation->description, 100) }}</p>
                    <div class="flex flex-wrap text-xs text-text-muted gap-4 mb-3">
                        <span>المدرس: {{ $reservation->teacher->name }}</span>
                        <span>الإغلاق: {{ $reservation->close_date?->format('Y-m-d') ?? 'بلا نهائي' }}</span>
                        <span>
                            @if($reservation->isUnlimited())
                                الكمية: غير محدودة
                            @else
                                المتبقي: {{ $reservation->remaining() }}
                            @endif
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs">
                            @if($reservation->isOpen())
                                <span class="text-success-600 font-medium">متاح الآن</span>
                            @elseif($reservation->isClosed())
                                <span class="text-warning-600 font-medium">مغلق</span>
                            @else
                                <span class="text-text-muted font-medium">غير متاح</span>
                            @endif
                        </span>
                        <a href="{{ route('student.reservations.show', $reservation) }}" class="btn-primary btn-sm">عرض الحجز</a>
                    </div>
                </div>
            @empty
                <x-empty-state icon="bi-calendar-x" title="لا توجد حجوزات متاحة" message="لا توجد حجوزات متاحة حالياً لمجموعتك." />
            @endforelse
        </div>

        <div class="pagination">
            {{ $reservations->links() }}
        </div>
    </div>
</x-dashboard-layout>