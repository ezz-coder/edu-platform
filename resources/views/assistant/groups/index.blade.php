<x-dashboard-layout title="مجموعات المدرس">
    <div class="space-y-4">
        <h2 class="text-lg font-bold text-gray-900">مجموعات المدرس</h2>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse ($groups as $group)
                <div class="bg-white rounded-lg shadow-sm p-5 border border-gray-200">
                    <div class="flex items-start justify-between mb-2">
                        <h3 class="text-lg font-semibold text-gray-900">{{ $group->title }}</h3>
                        <span class="px-2 py-1 text-xs font-semibold rounded-full
                            @if ($group->status === 'active') bg-green-100 text-green-800
                            @else bg-gray-100 text-gray-800 @endif">
                            {{ $group->status === 'active' ? 'نشط' : 'غير نشط' }}
                        </span>
                    </div>
                    <p class="text-sm text-gray-500">{{ $group->grade_level }} — {{ $group->day }} ({{ $group->start_time->format('H:i') }} إلى {{ $group->end_time->format('H:i') }})</p>
                    <p class="text-sm text-gray-600 mt-2">عدد الطلاب: {{ $group->enrollments_count }}</p>
                </div>
            @empty
                <div class="col-span-full bg-white rounded-lg shadow-sm p-8 text-center text-gray-500">لا توجد مجموعات.</div>
            @endforelse
        </div>
    </div>
</x-dashboard-layout>