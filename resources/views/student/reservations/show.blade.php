<x-dashboard-layout title="{{ $reservation->title }}">
    <div class="max-w-2xl mx-auto space-y-4">
        <div class="flex items-center justify-between">
            <h1 class="text-lg font-bold text-gray-900">{{ $reservation->title }}</h1>
            <a href="{{ route('student.reservations.index') }}" class="text-sm text-blue-600 hover:underline">العودة</a>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6 space-y-4">
            <div class="flex items-center justify-between">
                <span class="text-sm text-gray-500">نوع الحجز: {{ $reservation->typeLabel() }}</span>
                @if($reservation->isOpen())
                    <span class="text-green-700 text-sm">متاح</span>
                @elseif($reservation->isClosed())
                    <span class="text-orange-600 text-sm">مغلق</span>
                @else
                    <span class="text-gray-500 text-sm">غير متاح</span>
                @endif
            </div>

            <p class="text-gray-700">{{ $reservation->description ?: 'لا يوجد وصف.' }}</p>

            <div class="grid grid-cols-2 gap-4 text-sm">
                <div>
                    <p class="text-gray-500">المدرس</p>
                    <p class="font-medium">{{ $reservation->teacher->name }}</p>
                </div>
                <div>
                    <p class="text-gray-500">تاريخ الإغلاق</p>
                    <p class="font-medium">{{ $reservation->close_date?->format('Y-m-d') ?? 'بلا نهائي' }}</p>
                </div>
                <div>
                    <p class="text-gray-500">الكمية</p>
                    <p class="font-medium">{{ $reservation->quantityDisplay() }}</p>
                </div>
                <div>
                    <p class="text-gray-500">حالة الحجز</p>
                    @if($already)
                        @if($already->isDelivered())
                            <span class="text-green-700 font-medium">تم التسليم</span>
                        @else
                            <span class="text-orange-600 font-medium">في انتظار التسليم</span>
                        @endif
                    @else
                        <span class="text-gray-500 font-medium">لم تُحجز بعد</span>
                    @endif
                </div>
            </div>

            @if($already)
                <div class="mt-4 pt-4 border-t">
                    <p class="text-green-700 font-medium text-center">تم الحجز مسبقاً</p>
                </div>
            @elseif($reservation->isOpen())
                <form method="POST" action="{{ route('student.reservations.reserve', $reservation) }}" class="mt-4">
                    @csrf
                    <button type="submit" class="w-full py-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition text-lg font-medium">
                        <i class="bi bi-calendar-plus"></i> حجز
                    </button>
                </form>
            @else
                <div class="mt-4 pt-4 border-t">
                    <p class="text-gray-500 text-center">هذا الحجز غير متاح حالياً.</p>
                </div>
            @endif
        </div>
    </div>
</x-dashboard-layout>