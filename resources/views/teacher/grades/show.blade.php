<x-dashboard-layout title="{{ $exam->title }}">
    <div class="space-y-6">
        <div class="flex items-center justify-between flex-wrap gap-2">
            <div>
                <h1 class="text-lg font-bold text-gray-900">{{ $exam->title }}</h1>
                <p class="text-sm text-gray-500">إدارة درجات الطلاب في هذا الامتحان</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('teacher.grades.export', $exam) }}" class="px-3 py-1.5 bg-green-600 text-white rounded-lg hover:bg-green-700 transition text-sm">
                    <i class="bi bi-download"></i> تصدير CSV
                </a>
                <a href="{{ route('teacher.grades.index') }}" class="px-3 py-1.5 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition text-sm">العودة</a>
            </div>
        </div>

        @if (session('success'))
            <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-2 rounded-lg text-sm">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-2 rounded-lg text-sm">{{ session('error') }}</div>
        @endif

        <!-- Stats -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <div class="bg-white rounded-lg shadow-sm p-3 text-center">
                <p class="text-xs text-gray-500">الطلاب المستهدفون</p>
                <p class="text-xl font-bold text-gray-900">{{ $stats['targeted'] }}</p>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-3 text-center">
                <p class="text-xs text-gray-500">الدرجات المسجلة</p>
                <p class="text-xl font-bold text-blue-600">{{ $stats['graded'] }}</p>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-3 text-center">
                <p class="text-xs text-gray-500">متوسط الدرجات</p>
                <p class="text-xl font-bold text-green-600">{{ $stats['avg'] !== null ? number_format($stats['avg'], 1) : '—' }}</p>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-3 text-center">
                <p class="text-xs text-gray-500">أعلى درجة</p>
                <p class="text-xl font-bold text-amber-600">{{ $stats['max'] ?? '—' }}</p>
            </div>
        </div>

        <!-- Grades table with inline editing -->
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
                    <button type="submit" class="px-4 py-1.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm">تصفية</button>
                    <a href="{{ route('teacher.grades.show', $exam) }}" class="px-4 py-1.5 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition text-sm">مسح</a>
                </form>
            </div>

            <table class="w-full text-sm text-right">
                <thead class="bg-gray-50 text-gray-600">
                    <tr>
                        <th class="px-3 py-2">كود الطالب</th>
                        <th class="px-3 py-2">الاسم</th>
                        <th class="px-3 py-2">المجموعة</th>
                        <th class="px-3 py-2">الدرجة</th>
                        <th class="px-3 py-2">النسبة</th>
                        <th class="px-3 py-2">تاريخ الإنهاء</th>
                        <th class="px-3 py-2">إجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($attempts as $a)
                        @php
                            $enr = $a->student->enrollments->first();
                            $pct = $a->total_marks > 0 ? round(($a->score / $a->total_marks) * 100, 1) : 0;
                        @endphp
                        <tr>
                            <td class="px-3 py-2">{{ $a->student->student_code ?? '—' }}</td>
                            <td class="px-3 py-2 font-medium text-gray-900">{{ $a->student->name }}</td>
                            <td class="px-3 py-2">{{ $enr?->group->title ?? '—' }}</td>
                            <td class="px-3 py-2 font-bold text-blue-700">{{ $a->score }} / {{ $a->total_marks }}</td>
                            <td class="px-3 py-2">{{ $pct }}%</td>
                            <td class="px-3 py-2">{{ $a->finished_at?->format('Y-m-d H:i') ?? '—' }}</td>
                            <td class="px-3 py-2">
                                <button type="button" class="text-blue-600 hover:underline" data-bs-toggle="modal" data-bs-target="#editGradeModal{{ $a->id }}">تعديل</button>

                                <x-confirm-modal
                                    :id="'editGradeModal' . $a->id"
                                    title="تعديل درجة الطالب"
                                    body="أدخل الدرجة الجديدة للطالب {{ $a->student->name }} في امتحان {{ $exam->title }}."
                                    confirm-text="حفظ"
                                    cancel-text="إلغاء"
                                    :form-id="'editGradeForm' . $a->id"
                                />
                                <form id="editGradeForm{{ $a->id }}" method="POST" action="{{ route('teacher.grades.update', $a) }}" class="hidden">
                                    @csrf
                                    @method('PUT')
                                    <input type="number" name="score" value="{{ $a->score }}" min="0" max="{{ $a->total_marks }}" required class="border border-gray-300 rounded-lg px-2 py-1 text-sm w-24">
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-3 py-6 text-center text-gray-500">لا توجد درجات مسجلة لهذا الامتحان.</td></tr>
                    @endforelse
                </tbody>
            </table>

            <div class="p-4">
                {{ $attempts->links() }}
            </div>
        </div>
    </div>
</x-dashboard-layout>