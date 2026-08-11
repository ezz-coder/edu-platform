<x-dashboard-layout title="{{ $exam->title }}">
    <div class="space-y-6">
        <div class="flex items-center justify-between flex-wrap gap-2">
            <div>
                <h1 class="text-lg font-bold text-gray-900">{{ $exam->title }}</h1>
                <p class="text-sm text-gray-500">
                    المدة: {{ $exam->duration_minutes }} دقيقة |
                    من: {{ $exam->starts_at?->format('Y-m-d H:i') ?? '—' }} |
                    إلى: {{ $exam->ends_at?->format('Y-m-d H:i') ?? '—' }}
                </p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('teacher.exams.questions', $exam) }}" class="px-3 py-1.5 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition text-sm">الأسئلة</a>
                <a href="{{ route('teacher.exams.edit', $exam) }}" class="px-3 py-1.5 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition text-sm">تعديل</a>
                <a href="{{ route('teacher.exams.time.edit', $exam) }}" class="px-3 py-1.5 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition text-sm">تعديل الوقت</a>
                <a href="{{ route('teacher.exams.index') }}" class="px-3 py-1.5 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition text-sm">العودة</a>
            </div>
        </div>

        @if (session('success'))
            <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-2 rounded-lg text-sm">{{ session('success') }}</div>
        @endif

        <!-- PART 1: Stats -->
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
            <div class="bg-white rounded-lg shadow-sm p-3 text-center">
                <p class="text-xs text-gray-500">الطلاب المستهدفون</p>
                <p class="text-xl font-bold text-gray-900">{{ $stats['targeted'] }}</p>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-3 text-center">
                <p class="text-xs text-gray-500">بدأوا</p>
                <p class="text-xl font-bold text-blue-600">{{ $stats['started'] }}</p>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-3 text-center">
                <p class="text-xs text-gray-500">أكملوا</p>
                <p class="text-xl font-bold text-green-600">{{ $stats['finished'] }}</p>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-3 text-center">
                <p class="text-xs text-gray-500">قيد الأداء</p>
                <p class="text-xl font-bold text-amber-600">{{ $stats['running'] }}</p>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-3 text-center">
                <p class="text-xs text-gray-500">لم يبدأوا</p>
                <p class="text-xl font-bold text-gray-400">{{ $stats['not_started'] }}</p>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-3 text-center">
                <p class="text-xs text-gray-500">ملغاة</p>
                <p class="text-xl font-bold text-red-600">{{ $stats['cancelled'] }}</p>
            </div>
        </div>

        <!-- PART 8: Running students -->
        @if ($running->isNotEmpty())
            <div class="bg-white rounded-lg shadow-sm p-4">
                <h2 class="text-md font-bold text-gray-900 mb-3">طلاب يؤدون الامتحان الآن</h2>
                <div class="space-y-2">
                    @foreach ($running as $a)
                        @php
                            $enr = $a->student->enrollments->first();
                            $remaining = $a->started_at->copy()->addMinutes($exam->duration_minutes)->diffInSeconds(now(), false) * -1;
                            $remaining = max(0, $remaining);
                            $rm = floor($remaining / 60);
                            $rs = $remaining % 60;
                        @endphp
                        <div class="flex items-center justify-between text-sm border-b border-gray-100 pb-2">
                            <span>{{ $a->student->name }} — {{ $enr?->group->title ?? '—' }}</span>
                            <span class="text-gray-500">بدأ: {{ $a->started_at?->format('H:i') }} | المتبقي: {{ $rm }}:{{ str_pad($rs, 2, '0', STR_PAD_LEFT) }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- PART 2,3,4: Attempts table with search + filter -->
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <div class="p-4 border-b border-gray-100">
                <form method="GET" class="flex flex-wrap gap-2 items-end">
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">بحث (الاسم / الكود / الجوال)</label>
                        <input type="text" name="search" value="{{ $filters['search'] }}" placeholder="ابحث..." class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm w-56">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">المجموعة</label>
                        <select name="group_id" class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm">
                            <option value="">الكل</option>
                            @foreach ($groups as $g)
                                <option value="{{ $g->id }}" {{ $filters['group_id'] == $g->id ? 'selected' : '' }}>{{ $g->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">الحالة</label>
                        <select name="status" class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm">
                            <option value="">الكل</option>
                            <option value="running" {{ $filters['status'] === 'running' ? 'selected' : '' }}>قيد الأداء</option>
                            <option value="finished" {{ $filters['status'] === 'finished' ? 'selected' : '' }}>أكمل</option>
                            <option value="cancelled" {{ $filters['status'] === 'cancelled' ? 'selected' : '' }}>ملغاة</option>
                            <option value="not_started" {{ $filters['status'] === 'not_started' ? 'selected' : '' }}>لم يبدأ</option>
                        </select>
                    </div>
                    <button type="submit" class="px-4 py-1.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm">تصفية</button>
                    <a href="{{ route('teacher.exams.attempts', $exam) }}" class="px-4 py-1.5 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition text-sm">مسح</a>
                </form>
            </div>

            <table class="w-full text-sm text-right">
                <thead class="bg-gray-50 text-gray-600">
                    <tr>
                        <th class="px-3 py-2">كود الطالب</th>
                        <th class="px-3 py-2">الاسم</th>
                        <th class="px-3 py-2">المجموعة</th>
                        <th class="px-3 py-2">الجوال</th>
                        <th class="px-3 py-2">بدأ</th>
                        <th class="px-3 py-2">انتهى</th>
                        <th class="px-3 py-2">المدة</th>
                        <th class="px-3 py-2">الحالة</th>
                        <th class="px-3 py-2">الدرجة</th>
                        <th class="px-3 py-2">إجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($attempts as $a)
                        @php
                            $enr = $a->student->enrollments->first();
                            $duration = $a->finished_at && $a->started_at
                                ? $a->started_at->diffInMinutes($a->finished_at) . ' د'
                                : ($a->started_at ? '—' : '—');
                        @endphp
                        <tr>
                            <td class="px-3 py-2">{{ $a->student->student_code ?? '—' }}</td>
                            <td class="px-3 py-2 font-medium text-gray-900">{{ $a->student->name }}</td>
                            <td class="px-3 py-2">{{ $enr?->group->title ?? '—' }}</td>
                            <td class="px-3 py-2">{{ $a->student->mobile ?? '—' }}</td>
                            <td class="px-3 py-2">{{ $a->started_at?->format('Y-m-d H:i') ?? '—' }}</td>
                            <td class="px-3 py-2">{{ $a->finished_at?->format('Y-m-d H:i') ?? '—' }}</td>
                            <td class="px-3 py-2">{{ $duration }}</td>
                            <td class="px-3 py-2">
                                @if ($a->status === 'running') <span class="text-amber-600">قيد الأداء</span>
                                @elseif ($a->status === 'finished') <span class="text-green-600">أكمل</span>
                                @elseif ($a->status === 'cancelled') <span class="text-red-600">ملغاة</span>
                                @else <span class="text-gray-400">لم يبدأ</span>
                                @endif
                            </td>
                            <td class="px-3 py-2">{{ $a->score !== null ? $a->score . ' / ' . $a->total_marks : '—' }}</td>
                            <td class="px-3 py-2">
                                <div class="flex flex-wrap gap-2">
                                    <a href="{{ route('teacher.exams.attempt.show', $a) }}" class="text-blue-600 hover:underline">عرض</a>
                                    @if ($a->status !== 'not_started')
                                        <button type="button" class="text-red-600 hover:underline" data-bs-toggle="modal" data-bs-target="#resetModal{{ $a->id }}">إعادة تعيين</button>
                                    @endif
                                </div>

                                <!-- PART 6 + 13: Reset confirmation modal -->
                                @if ($a->status !== 'not_started')
                                    <x-confirm-modal
                                        :id="'resetModal' . $a->id"
                                        title="إعادة تعيين محاولة الطالب"
                                        body="سيتم حذف محاولة الطالب بالكامل. وسيمكنه أداء الامتحان مرة أخرى. لا يمكن التراجع بعد التنفيذ."
                                        confirm-text="إعادة التعيين"
                                        cancel-text="إلغاء"
                                        :form-id="'resetForm' . $a->id"
                                    />
                                    <form id="resetForm{{ $a->id }}" method="POST" action="{{ route('teacher.exams.attempt.reset', $a) }}" class="hidden">@csrf</form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="px-3 py-6 text-center text-gray-500">لا توجد محاولات مطابقة.</td></tr>
                    @endforelse
                </tbody>
            </table>

            <div class="p-4">
                {{ $attempts->links() }}
            </div>
        </div>

        <!-- PART 9: Finished students (newest first) -->
        @if ($finished->isNotEmpty())
            <div class="bg-white rounded-lg shadow-sm p-4">
                <h2 class="text-md font-bold text-gray-900 mb-3">الطلاب الذين أكملوا (الأحدث أولاً)</h2>
                <div class="space-y-1 text-sm">
                    @foreach ($finished as $a)
                        <div class="flex items-center justify-between border-b border-gray-100 pb-1">
                            <a href="{{ route('teacher.exams.attempt.show', $a) }}" class="text-blue-600 hover:underline">{{ $a->student->name }}</a>
                            <span class="text-gray-500">{{ $a->finished_at?->format('Y-m-d H:i') }} — {{ $a->score }}/{{ $a->total_marks }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</x-dashboard-layout>