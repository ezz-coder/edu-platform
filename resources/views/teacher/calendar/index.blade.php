<x-dashboard-layout title="التقويم">
    <x-page-header
        title="التقويم"
        description="جدول الحصص وجلسات الحضور والامتحانات"
    />

    <!-- Month navigation + group filter -->
    <div class="card p-4 mb-6 flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2">
            <a href="{{ route('teacher.calendar.index', ['month' => $prevMonth->format('Y-m'), 'group_id' => $selectedGroupId]) }}" class="btn-ghost btn-sm">
                <i class="bi bi-chevron-right"></i> الشهر السابق
            </a>
            <span class="font-bold text-text-primary px-2">{{ $month->translatedFormat('F Y') }}</span>
            <a href="{{ route('teacher.calendar.index', ['month' => $nextMonth->format('Y-m'), 'group_id' => $selectedGroupId]) }}" class="btn-ghost btn-sm">
                الشهر التالي <i class="bi bi-chevron-left"></i>
            </a>
            <a href="{{ route('teacher.calendar.index', ['group_id' => $selectedGroupId]) }}" class="text-sm text-primary-600 hover:text-primary-700 ml-2">اليوم</a>
        </div>

        <form method="GET" class="flex items-center gap-2">
            @if (request()->filled('month'))
                <input type="hidden" name="month" value="{{ request('month') }}">
            @endif
            <select name="group_id" class="form-select text-sm py-1.5" onchange="this.form.submit()">
                <option value="">كل المجموعات</option>
                @foreach ($groups as $g)
                    <option value="{{ $g->id }}" {{ $selectedGroupId == $g->id ? 'selected' : '' }}>{{ $g->title }}</option>
                @endforeach
            </select>
        </form>
    </div>

    <!-- Legend -->
    <div class="flex flex-wrap gap-4 text-xs text-text-secondary mb-4">
        <span><span class="inline-block w-3 h-3 rounded bg-primary-100 border border-primary-200 ml-1"></span> حصة أسبوعية</span>
        <span><span class="inline-block w-3 h-3 rounded bg-green-100 border border-green-200 ml-1"></span> جلسة حضور مفتوحة</span>
        <span><span class="inline-block w-3 h-3 rounded bg-gray-100 border ml-1"></span> جلسة حضور مغلقة</span>
        <span><span class="inline-block w-3 h-3 rounded bg-warning-50 border border-warning-200 ml-1"></span> امتحان</span>
    </div>

    <!-- Calendar grid -->
    <div class="card overflow-hidden">
        <div class="grid grid-cols-7 bg-bg-sidebar text-center text-xs font-bold text-text-secondary">
            @foreach (['الأحد', 'الاثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'] as $dayName)
                <div class="py-2">{{ $dayName }}</div>
            @endforeach
        </div>
        <div class="grid grid-cols-7">
            @foreach ($weeks as $week)
                @foreach ($week as $day)
                    <div class="min-h-[110px] border-t border-l border-border-light p-1.5 {{ $day['in_month'] ? '' : 'bg-gray-50 opacity-50' }} {{ $day['is_today'] ? 'bg-primary-50/50' : '' }}">
                        <div class="text-xs font-medium text-text-secondary mb-1 {{ $day['is_today'] ? 'text-primary-700 font-bold' : '' }}">
                            {{ $day['date']->day }}
                        </div>
                        @forelse ($day['events'] as $event)
                            @if (!empty($event['url']))
                                <a href="{{ $event['url'] }}" title="{{ $event['label'] }}"
                                   class="block mb-1 px-1.5 py-0.5 rounded text-[11px] leading-snug truncate border {{ $event['color'] }}">
                                    @if ($event['time'])<span class="opacity-70">{{ $event['time'] }}</span> @endif{{ $event['label'] }}
                                </a>
                            @else
                                <div title="{{ $event['label'] }}"
                                     class="mb-1 px-1.5 py-0.5 rounded text-[11px] leading-snug truncate border {{ $event['color'] }}">
                                    @if ($event['time'])<span class="opacity-70">{{ $event['time'] }}</span> @endif{{ $event['label'] }}
                                </div>
                            @endif
                        @empty
                        @endforelse
                    </div>
                @endforeach
            @endforeach
        </div>
    </div>

    @if ($groups->isEmpty())
        <x-empty-state icon="bi-calendar" title="لا توجد مجموعات" message="أنشئ مجموعة أولاً لتظهر مواعيدها في التقويم." />
    @endif
</x-dashboard-layout>