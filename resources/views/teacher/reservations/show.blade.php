<x-dashboard-layout title="{{ $reservation->title }}">
    <div class="max-w-4xl mx-auto space-y-4">
        <div class="flex items-center justify-between">
            <h1 class="text-lg font-bold text-gray-900">{{ $reservation->title }}</h1>
            <a href="{{ route('teacher.reservations.index') }}" class="text-sm text-blue-600 hover:underline">العودة</a>
        </div>

        <!-- Reservation Details -->
        <div class="bg-white rounded-lg shadow-sm p-6 space-y-2 text-sm">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div>
                    <p class="text-gray-500">النوع</p>
                    <p class="font-medium">{{ $reservation->typeLabel() }}</p>
                </div>
                <div>
                    <p class="text-gray-500">الحالة</p>
                    <p class="font-medium">
                        @if ($reservation->status === 'draft')
                            <span class="text-gray-500">مسودة</span>
                        @elseif ($reservation->status === 'open')
                            <span class="text-green-700">مفتوح</span>
                        @elseif ($reservation->status === 'closed')
                            <span class="text-orange-600">مغلق</span>
                        @elseif ($reservation->status === 'archived')
                            <span class="text-blue-600">مؤرشف</span>
                        @endif
                    </p>
                </div>
                <div>
                    <p class="text-gray-500">الكمية</p>
                    <p class="font-medium">{{ $reservation->quantityDisplay() }}</p>
                </div>
                <div>
                    <p class="text-gray-500">المجموعات</p>
                    <p class="font-medium">{{ $reservation->targetGroups->pluck('title')->join('، ') }}</p>
                </div>
            </div>
            <div class="pt-2">
                <p class="text-gray-500">الوصف</p>
                <p class="text-gray-900">{{ $reservation->description ?? '—' }}</p>
            </div>
            <div class="pt-2">
                <p class="text-gray-500">الفترة</p>
                <p class="text-gray-900">
                    من: {{ $reservation->open_date?->format('Y-m-d') ?? '—' }}
                    إلى: {{ $reservation->close_date?->format('Y-m-d') ?? '—' }}
                </p>
            </div>
        </div>

        <!-- Statistics -->
        <div class="grid grid-cols-3 gap-4">
            <div class="bg-white rounded-lg shadow-sm p-4 text-center">
                <p class="text-2xl font-bold text-gray-900">{{ $reservation->reserved_count }}</p>
                <p class="text-sm text-gray-500">المحجوز</p>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-4 text-center">
                <p class="text-2xl font-bold text-green-700">{{ $reservation->deliveredCount() }}</p>
                <p class="text-sm text-gray-500">تم التسليم</p>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-4 text-center">
                <p class="text-2xl font-bold text-orange-600">{{ $reservation->notDeliveredCount() }}</p>
                <p class="text-sm text-gray-500">انتظار التسليم</p>
            </div>
        </div>

        <!-- Reserved Students -->
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <h2 class="text-md font-bold text-gray-900 p-4 border-b">الطلاب المحجوزون</h2>
            <table class="w-full text-sm text-right">
                <thead class="bg-gray-50 text-gray-600">
                    <tr>
                        <th class="px-4 py-3">الطالب</th>
                        <th class="px-4 py-3">المجموعة</th>
                        <th class="px-4 py-3">تاريخ الحجز</th>
                        <th class="px-4 py-3">حالة التسليم</th>
                        <th class="px-4 py-3">إجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($items as $item)
                        <tr>
                            <td class="px-4 py-3">{{ $item->student->name }}</td>
                            <td class="px-4 py-3">{{ $item->group?->title ?? '—' }}</td>
                            <td class="px-4 py-3">{{ $item->reserved_at?->format('Y-m-d H:i') ?? '—' }}</td>
                            <td class="px-4 py-3">
                                @if($item->isDelivered())
                                    <span class="text-green-700">تم التسليم</span>
                                    <span class="text-gray-400 text-xs d-block">{{ $item->delivered_at?->format('Y-m-d') }}</span>
                                @else
                                    <span class="text-orange-600">انتظار</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if(! $reservation->isArchived())
                                    @if($item->isPending())
                                        <form method="POST" action="{{ route('teacher.reservations.items.deliver', [$reservation, $item]) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="text-green-600 hover:underline text-xs">تأكيد تسليم</button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('teacher.reservations.items.undo-deliver', [$reservation, $item]) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="text-orange-600 hover:underline text-xs">إلغاء تسليم</button>
                                        </form>
                                    @endif
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-6 text-center text-gray-500">لا يوجد طلاب محجوزون بعد.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $items->links() }}
    </div>
</x-dashboard-layout>