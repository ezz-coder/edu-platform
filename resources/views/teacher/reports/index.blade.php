<x-dashboard-layout title="التقارير">
    <div class="space-y-6">
        <div>
            <h1 class="text-lg font-bold text-gray-900">التقارير</h1>
            <p class="text-sm text-gray-500">ملخصات الحضور والدرجات والأنشطة لمجموعاتك</p>
        </div>

        <!-- Filters -->
        <form method="GET" action="{{ route('teacher.reports.index') }}" class="bg-white rounded-lg shadow-sm p-4 flex flex-wrap gap-3 items-end">
            <div>
                <label class="block text-xs text-gray-500 mb-1">المجموعة</label>
                <select name="group_id" class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm">
                    <option value="">كل المجموعات</option>
                    @foreach ($groups as $g)
                        <option value="{{ $g->id }}" {{ $filters['group_id'] == $g->id ? 'selected' : '' }}>{{ $g->title }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">من تاريخ</label>
                <input type="date" name="date_from" value="{{ $filters['date_from'] }}" class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm">
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">إلى تاريخ</label>
                <input type="date" name="date_to" value="{{ $filters['date_to'] }}" class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm">
            </div>
            <button type="submit" class="px-4 py-1.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm">تطبيق</button>
            <a href="{{ route('teacher.reports.index') }}" class="px-4 py-1.5 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition text-sm">مسح</a>
        </form>

        <!-- Attendance summary -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <div class="bg-white rounded-lg shadow-sm p-3 text-center">
                <p class="text-xs text-gray-500">جلسات الحضور</p>
                <p class="text-xl font-bold text-gray-900">{{ $attendanceTotals['sessions'] }}</p>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-3 text-center">
                <p class="text-xs text-gray-500">حضور</p>
                <p class="text-xl font-bold text-green-600">{{ $attendanceTotals['present'] }}</p>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-3 text-center">
                <p class="text-xs text-gray-500">غياب</p>
                <p class="text-xl font-bold text-red-600">{{ $attendanceTotals['absent'] }}</p>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-3 text-center">
                <p class="text-xs text-gray-500">تأخير</p>
                <p class="text-xl font-bold text-amber-600">{{ $attendanceTotals['late'] }}</p>
            </div>
        </div>

        <!-- Attendance sessions table -->
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <h2 class="font-semibold p-4 border-b border-gray-100">ملخص جلسات الحضور</h2>
            @if ($sessions->isEmpty())
                <p class="p-4 text-sm text-gray-500">لا توجد جلسات حضور مطابقة للفلاتر.</p>
            @else
                <table class="w-full text-sm text-right">
                    <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <th class="px-3 py-2">المجموعة</th>
                            <th class="px-3 py-2">الدرس رقم</th>
                            <th class="px-3 py-2">التاريخ</th>
                            <th class="px-3 py-2">حالة الجلسة</th>
                            <th class="px-3 py-2">حضور</th>
                            <th class="px-3 py-2">غياب</th>
                            <th class="px-3 py-2">تأخير</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($sessions as $s)
                            <tr>
                                <td class="px-3 py-2">{{ $s->group_title }}</td>
                                <td class="px-3 py-2">{{ $s->lesson_number ?? '—' }}</td>
                                <td class="px-3 py-2">{{ \Illuminate\Support\Carbon::parse($s->attendance_date)->format('Y-m-d') }}</td>
                                <td class="px-3 py-2">{{ $s->session_status === 'open' ? 'مفتوحة' : 'مغلقة' }}</td>
                                <td class="px-3 py-2 text-green-600">{{ $s->present_count }}</td>
                                <td class="px-3 py-2 text-red-600">{{ $s->absent_count }}</td>
                                <td class="px-3 py-2 text-amber-600">{{ $s->late_count }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        <!-- Grades summary -->
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <h2 class="font-semibold p-4 border-b border-gray-100">ملخص الدرجات</h2>
            <div class="grid grid-cols-3 divide-x divide-x-reverse divide-gray-100">
                <div class="p-4 text-center">
                    <p class="text-xs text-gray-500">عدد المحاولات المنتهية</p>
                    <p class="text-xl font-bold text-blue-600">{{ $gradesTotals['attempts'] }}</p>
                </div>
                <div class="p-4 text-center">
                    <p class="text-xs text-gray-500">متوسط الدرجات</p>
                    <p class="text-xl font-bold text-green-600">{{ $gradesTotals['attempts'] > 0 ? $gradesTotals['avg_score'] : '—' }}</p>
                </div>
                <div class="p-4 text-center">
                    <p class="text-xs text-gray-500">أعلى درجة</p>
                    <p class="text-xl font-bold text-amber-600">{{ $gradesTotals['max_score'] ?? '—' }}</p>
                </div>
            </div>
        </div>

        <!-- Per-student report (when a group is selected) -->
        @if ($selectedGroup)
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <h2 class="font-semibold p-4 border-b border-gray-100">تقرير الطلاب — {{ $selectedGroup->title }}</h2>
                @if ($studentReports->isEmpty())
                    <p class="p-4 text-sm text-gray-500">لا يوجد طلاب في هذه المجموعة.</p>
                @else
                    <table class="w-full text-sm text-right">
                        <thead class="bg-gray-50 text-gray-600">
                            <tr>
                                <th class="px-3 py-2">الطالب</th>
                                <th class="px-3 py-2">الكود</th>
                                <th class="px-3 py-2">الحالة</th>
                                <th class="px-3 py-2">حضور</th>
                                <th class="px-3 py-2">غياب</th>
                                <th class="px-3 py-2">تأخير</th>
                                <th class="px-3 py-2">امتحانات مؤداة</th>
                                <th class="px-3 py-2">متوسط الدرجة</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($studentReports as $r)
                                <tr>
                                    <td class="px-3 py-2">{{ $r['enrollment']->student->name }}</td>
                                    <td class="px-3 py-2 font-mono text-xs">{{ $r['enrollment']->student->student_code }}</td>
                                    <td class="px-3 py-2">{{ $r['enrollment']->status === 'enrolled' ? 'مسجل' : 'غير نشط' }}</td>
                                    <td class="px-3 py-2 text-green-600">{{ $r['present'] }}</td>
                                    <td class="px-3 py-2 text-red-600">{{ $r['absent'] }}</td>
                                    <td class="px-3 py-2 text-amber-600">{{ $r['late'] }}</td>
                                    <td class="px-3 py-2">{{ $r['attempts'] }}</td>
                                    <td class="px-3 py-2">{{ $r['avg_score'] ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        @endif

        <!-- Recent activity -->
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <h2 class="font-semibold p-4 border-b border-gray-100">آخر الأنشطة</h2>
            @if ($activities->isEmpty())
                <p class="p-4 text-sm text-gray-500">لا توجد أنشطة مطابقة.</p>
            @else
                <ul class="divide-y divide-gray-100">
                    @foreach ($activities as $activity)
                        <li class="px-4 py-2 flex justify-between items-center text-sm">
                            <span>{{ $activity->description }}</span>
                            <span class="text-xs text-gray-400">{{ $activity->created_at->format('Y-m-d H:i') }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</x-dashboard-layout>