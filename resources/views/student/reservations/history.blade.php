<x-dashboard-layout title="سجل الحجوزات">
    <div class="max-w-2xl mx-auto">
        <div class="flex items-center justify-between mb-4">
            <h1 class="text-lg font-bold text-gray-900">سجل الحجوزات</h1>
            <a href="{{ route('student.reservations.index') }}" class="text-sm text-blue-600 hover:underline">الحجوزات الحالية</a>
        </div>

        <div class="space-y-4">
            @forelse ($history as $item)
                <div class="bg-white rounded-lg shadow-sm p-4">
                    <div class="flex items-start justify-between mb-2">
                        <h2 class="font-bold text-gray-900">{{ $item->reservation->title }}</h2>
                        <span class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded">{{ $item->reservation->typeLabel() }}</span>
                    </div>

                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="text-gray-500">المدرس</p>
                            <p class="font-medium">{{ $item->reservation->teacher->name }}</p>
                        </div>
                        <div>
                            <p class="text-gray-500">تاريخ الحجز</p>
                            <p class="font-medium">{{ $item->reserved_at?->format('Y-m-d') ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-gray-500">حالة التسليم</p>
                            @if($item->isDelivered())
                                <span class="text-green-700 font-medium">تم التسليم</span>
                            @else
                                <span class="text-orange-600 font-medium">انتظار التسليم</span>
                            @endif
                        </div>
                        <div>
                            <p class="text-gray-500">الحالة الحالية</p>
                            @if($item->reservation->status === 'archived')
                                <span class="text-blue-600 font-medium">مؤرشف</span>
                            @elseif($item->reservation->status === 'closed')
                                <span class="text-orange-600 font-medium">مغلق</span>
                            @else
                                <span class="text-gray-500 font-medium">—</span>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="bg-white rounded-lg shadow-sm p-6 text-center">
                    <p class="text-gray-600">لا يوجد سجل حجوزات.</p>
                </div>
            @endforelse
        </div>

        {{ $history->links() }}
    </div>
</x-dashboard-layout>