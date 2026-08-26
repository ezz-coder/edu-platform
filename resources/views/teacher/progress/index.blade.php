<x-dashboard-layout title="تتبع تقدم الطلاب">
    <x-page-header
        title="تتبع تقدم الطلاب"
        description="درجات الامتحانات والحضور لطلاب كل مجموعة"
    />

    <!-- Group selection -->
    <div class="card p-4 mb-6">
        <form method="GET" class="flex flex-wrap items-center gap-3">
            <label for="group_id" class="text-sm font-medium text-text-primary">المجموعة:</label>
            <select id="group_id" name="group_id" class="form-select text-sm py-1.5 max-w-xs" onchange="this.form.submit()">
                @foreach ($groups as $g)
                    <option value="{{ $g->id }}" {{ $selectedGroupId == $g->id ? 'selected' : '' }}>{{ $g->title }}</option>
                @endforeach
            </select>
        </form>
    </div>

    @if ($groups->isEmpty())
        <x-empty-state icon="bi-graph-up" title="لا توجد مجموعات" message="أنشئ مجموعة وسجل طلاباً أولاً." />
    @else
        <!-- Students progress table -->
        <div class="table-container mb-6">
            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>الطالب</th>
                            <th>امتحانات مكتملة</th>
                            <th>متوسط الدرجات %</th>
                            <th>حاضر</th>
                            <th>غائب</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($students as $s)
                            <tr>
                                <td class="font-medium text-text-primary">{{ $s['enrollment']->student->name }} <span class="text-text-muted text-xs">({{ $s['enrollment']->student->student_code }})</span></td>
                                <td>{{ $s['exams_taken'] }}</td>
                                <td>{{ $s['avg_percent'] !== null ? $s['avg_percent'].'%' : '—' }}</td>
                                <td><span class="badge badge-success">{{ $s['present'] }}</span></td>
                                <td><span class="badge badge-danger">{{ $s['absent'] }}</span></td>
                                <td>
                                    <a href="{{ route('teacher.progress.index', ['group_id' => $selectedGroupId, 'student_id' => $s['enrollment']->student_id]) }}" class="text-primary-600 hover:text-primary-700 text-sm font-medium">تفاصيل</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">
                                    <x-empty-state icon="bi-people" title="لا يوجد طلاب مسجلون" message="لا يوجد طلاب نشطون في هذه المجموعة." />
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Single student detail -->
        @if ($selectedStudent)
            <div class="card p-6">
                <h2 class="text-lg font-bold text-text-primary mb-1">{{ $selectedStudent['enrollment']->student->name }}</h2>
                <p class="text-sm text-text-secondary mb-4">
                    الحضور: {{ $studentAttendance['present'] }} حاضر / {{ $studentAttendance['absent'] }} غائب
                </p>

                <h3 class="font-bold text-text-primary mb-2">نتائج الامتحانات</h3>
                @if ($studentAttempts->isEmpty())
                    <x-empty-state icon="bi-file-earmark-text" title="لا توجد نتائج" message="لم يكمل هذا الطالب أي امتحان بعد." />
                @else
                    <div class="overflow-x-auto">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>الامتحان</th>
                                    <th>الدرجة</th>
                                    <th>النسبة %</th>
                                    <th>تاريخ الإنهاء</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($studentAttempts as $attempt)
                                    <tr>
                                        <td class="text-text-primary">{{ $attempt->exam?->title }}</td>
                                        <td>{{ $attempt->score }} / {{ $attempt->total_marks }}</td>
                                        <td>{{ $attempt->total_marks > 0 ? round(($attempt->score / $attempt->total_marks) * 100, 1).'%' : '—' }}</td>
                                        <td class="text-text-secondary">{{ optional($attempt->finished_at)->format('Y-m-d H:i') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                <a href="{{ route('teacher.progress.index', ['group_id' => $selectedGroupId]) }}" class="btn-ghost btn-sm mt-4 inline-flex">
                    <i class="bi bi-arrow-right"></i> رجوع لقائمة الطلاب
                </a>
            </div>
        @endif
    @endif
</x-dashboard-layout>