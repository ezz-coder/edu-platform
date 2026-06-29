<x-dashboard-layout title="المجموعات المتاحة">
    <div class="space-y-6">
        <!-- Header -->
        <div>
            <h1 class="text-2xl font-bold text-gray-900">المجموعات المتاحة</h1>
            <p class="mt-2 text-gray-600">تصفح المجموعات الدراسية المتاحة حالياً</p>
        </div>

        <!-- Groups Grid -->
        @if ($groups->isEmpty())
            <div class="bg-white rounded-lg shadow-sm p-12 text-center">
                <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                </svg>
                <p class="text-gray-500 text-lg">لا توجد مجموعات متاحة حالياً</p>
                <p class="text-gray-400 text-sm mt-2">سيتم إضافة مجموعات قريباً</p>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($groups as $group)
                    <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex-1">
                                <h3 class="text-lg font-semibold text-gray-900">{{ $group->title }}</h3>
                                <p class="text-sm text-gray-500 mt-1">{{ $group->grade_level }}</p>
                            </div>
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                نشط
                            </span>
                        </div>

                        @if ($group->description)
                            <p class="text-gray-600 text-sm mb-4">{{ $group->description }}</p>
                        @endif

                        <div class="space-y-2 text-sm text-gray-600 mb-4">
                            <div class="flex items-center">
                                <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                                {{ $group->teacher->name }}
                            </div>
                            <div class="flex items-center">
                                <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                                {{ $group->day }}
                            </div>
                            <div class="flex items-center">
                                <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                {{ $group->start_time }} - {{ $group->end_time }}
                            </div>
                            <div class="flex items-center">
                                <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                                {{ match($group->gender_type) {
                                    'boys' => 'بنين',
                                    'girls' => 'بنات',
                                    'mixed' => 'مشترك',
                                    default => '',
                                } }}
                            </div>
                        </div>

                        <div class="pt-4 border-t border-gray-200">
                            <p class="text-xs text-gray-400">الانضمام متاح في Sprint 4</p>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-dashboard-layout>
