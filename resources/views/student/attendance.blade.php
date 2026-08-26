<x-dashboard-layout>
    <div class="max-w-4xl mx-auto space-y-6">
        <!-- Summary cards -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <div class="bg-bg-card rounded-card border border-border-light p-4 text-center">
                <p class="text-xs text-text-muted">حضور</p>
                <p class="text-2xl font-bold text-green-600">{{ ($summary['regular'] ?? 0) + ($summary['present'] ?? 0) }}</p>
            </div>
            <div class="bg-bg-card rounded-card border border-border-light p-4 text-center">
                <p class="text-xs text-text-muted">تأخير</p>
                <p class="text-2xl font-bold text-orange-500">{{ $summary['late'] ?? 0 }}</p>
            </div>
            <div class="bg-bg-card rounded-card border border-border-light p-4 text-center">
                <p class="text-xs text-text-muted">غياب</p>
                <p class="text-2xl font-bold text-red-600">{{ $summary['absent'] ?? 0 }}</p>
            </div>
            <div class="bg-bg-card rounded-card border border-border-light p-4 text-center">
                <p class="text-xs text-text-muted">نسبة الحضور</p>
                <p class="text-2xl font-bold text-primary-600">{{ $percentage !== null ? $percentage . '%' : '—' }}</p>
            </div>
        </div>

        @if (! $enrollment)
            <div class="bg-bg-card rounded-card border border-border-light p-8 text-center text-text-muted">
                أنت غير مسجل في أي مجموعة حالياً.
            </div>
        @elseif ($records->isEmpty())
            <div class="bg-bg-card rounded-card border border-border-light p-8 text-center text-text-muted">
                لا توجد سجلات حضور بعد.
            </div>
        @else
            <div class="bg-bg-card rounded-card border border-border-light overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-text-muted">
                        <tr>
                            <th class="px-4 py-3 text-right font-medium">الحصة</th>
                            <th class="px-4 py-3 text-right font-medium">التاريخ</th>
                            <th class="px-4 py-3 text-right font-medium">الحالة</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border-light">
                        @foreach ($records as $record)
                            <tr>
                                <td class="px-4 py-3 text-text-primary">الحصة رقم {{ $record->lesson_number }}</td>
                                <td class="px-4 py-3 text-text-muted">{{ \Carbon\Carbon::parse($record->attendance_date)->translatedFormat('j M Y') }}</td>
                                <td class="px-4 py-3">
                                    @if (in_array($record->attendance_type, ['present', 'regular'], true))
                                        <span class="inline-block px-2 py-0.5 rounded-full bg-green-100 text-green-700 text-xs font-medium">حاضر</span>
                                    @elseif ($record->attendance_type === 'late')
                                        <span class="inline-block px-2 py-0.5 rounded-full bg-orange-100 text-orange-700 text-xs font-medium">متأخر</span>
                                    @else
                                        <span class="inline-block px-2 py-0.5 rounded-full bg-red-100 text-red-700 text-xs font-medium">غائب</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</x-dashboard-layout>