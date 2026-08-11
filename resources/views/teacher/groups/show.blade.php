<x-dashboard-layout title="مساحة المجموعة">
    <div class="space-y-6">
        <a href="{{ route('teacher.groups.index') }}" class="inline-flex items-center text-gray-600 hover:text-gray-900">
            <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
            العودة إلى مجموعاتي
        </a>

        @if (session('success'))
            <div class="bg-green-50 border border-green-200 text-green-800 rounded-lg px-4 py-3 text-sm">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="bg-red-50 border border-red-200 text-red-800 rounded-lg px-4 py-3 text-sm">{{ session('error') }}</div>
        @endif
        @if (session('info'))
            <div class="bg-blue-50 border border-blue-200 text-blue-800 rounded-lg px-4 py-3 text-sm">{{ session('info') }}</div>
        @endif
        @if ($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-800 rounded-lg px-4 py-3 text-sm">
                <ul class="list-disc list-inside space-y-1">
                    @foreach ($errors->all() as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Group Information -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-start justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">{{ $group->title }}</h1>
                    <p class="text-sm text-gray-500 mt-1">{{ $group->grade_level }}</p>
                </div>
                <span class="px-2 py-1 text-xs font-semibold rounded-full
                    @if ($group->status === 'active') bg-green-100 text-green-800
                    @else bg-gray-100 text-gray-800 @endif">
                    {{ $group->status === 'active' ? 'نشط' : 'غير نشط' }}
                </span>
            </div>

            @if ($group->description)
                <p class="text-gray-600 text-sm mt-3">{{ $group->description }}</p>
            @endif

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6 text-sm text-gray-600">
                <div>
                    <p class="text-gray-400">المدرس</p>
                    <p class="font-medium text-gray-900">{{ $group->teacher->name }}</p>
                </div>
                <div>
                    <p class="text-gray-400">اليوم</p>
                    <p class="font-medium text-gray-900">{{ $group->day }}</p>
                </div>
                <div>
                    <p class="text-gray-400">الوقت</p>
                    <p class="font-medium text-gray-900">{{ $group->start_time->format('H:i') }} - {{ $group->end_time->format('H:i') }}</p>
                </div>
                <div>
                    <p class="text-gray-400">النوع</p>
                    <p class="font-medium text-gray-900">
                        {{ match($group->gender_type) { 'boys' => 'بنين', 'girls' => 'بنات', 'mixed' => 'مشترك', default => '' } }}
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-3 mt-6">
                <a href="{{ route('teacher.groups.edit', $group) }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm">تعديل</a>
                <a href="{{ route('teacher.groups.cards.print', $group) }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition text-sm"><i class="bi bi-printer"></i> طباعة بطاقات الطلاب</a>
                @if ($group->status === 'active')
                    <button type="button" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition text-sm" data-bs-toggle="modal" data-bs-target="#addStudentModal"><i class="bi bi-person-plus"></i> إضافة طالب</button>
                @endif
            </div>
        </div>

        <!-- Capacity Section -->
        @php
            $remaining = $group->remainingSeats();
            $full = $group->isFull();
            $warning = ! $full && $remaining <= 5;
        @endphp
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-bold text-gray-900 mb-4">السعة</h2>
            <div class="grid grid-cols-3 gap-4 text-center">
                <div>
                    <p class="text-sm text-gray-500">الحالي</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $group->currentStudents() }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">الحد الأقصى</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $group->max_students }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">المقاعد المتبقية</p>
                    <p class="text-2xl font-bold {{ $full ? 'text-red-600' : ($warning ? 'text-orange-600' : 'text-green-600') }}">{{ $remaining }}</p>
                </div>
            </div>
            @if ($full)
                <div class="mt-4 text-sm text-red-700 bg-red-50 rounded-lg px-3 py-2">المجموعة ممتلئة</div>
            @elseif ($warning)
                <div class="mt-4 text-sm text-orange-700 bg-orange-50 rounded-lg px-3 py-2">المقاعد شارفت على الانتهاء ({{ $remaining }} متبقٍ)</div>
            @endif
        </div>

        <!-- Bulk Toolbar (RULE 12) -->
        @if ($enrollments->isNotEmpty())
            <form method="POST" action="{{ route('teacher.students.bulk.move') }}" id="bulkForm" class="hidden">
                @csrf
                <input type="hidden" name="enrollment_ids" id="bulkIds">
                <input type="hidden" name="group_id" id="bulkGroupId">
            </form>

            <div class="bg-white rounded-lg shadow-sm p-4 flex flex-wrap items-center gap-3">
                <span class="text-sm text-gray-600 font-medium">الإجراءات الجماعية:</span>
                <button type="button" class="px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm" onclick="openBulkMove()">نقل المحددين</button>
                <button type="button" class="px-3 py-2 bg-red-50 text-red-700 rounded-lg hover:bg-red-100 transition text-sm" data-bs-toggle="modal" data-bs-target="#bulkRemoveModal">إزالة المحددين</button>
                <button type="button" class="px-3 py-2 bg-green-50 text-green-700 rounded-lg hover:bg-green-100 transition text-sm" data-bs-toggle="modal" data-bs-target="#bulkRestoreModal">استرجاع المحددين</button>
            </div>
        @endif

        <!-- Students -->
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <h2 class="text-lg font-bold text-gray-900">طلاب المجموعة ({{ $group->currentStudents() }})</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-3 py-3"><input type="checkbox" id="selectAll" class="rounded border-gray-300 text-blue-600"></th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">كود الطالب</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الاسم</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">موبايل الطالب</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">موبايل ولي الأمر</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">تاريخ التسجيل</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الحالة</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($enrollments as $enrollment)
                            <tr>
                                <td class="px-3 py-3">
                                    <input type="checkbox" class="row-check rounded border-gray-300 text-blue-600" value="{{ $enrollment->id }}">
                                </td>
                                <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $enrollment->student->student_code }}</td>
                                <td class="px-4 py-3 text-sm text-gray-900">{{ $enrollment->student->name }}</td>
                                <td class="px-4 py-3 text-sm text-gray-500">{{ $enrollment->student->mobile }}</td>
                                <td class="px-4 py-3 text-sm text-gray-500">{{ $enrollment->student->parent_mobile }}</td>
                                <td class="px-4 py-3 text-sm text-gray-500">{{ $enrollment->created_at->format('Y-m-d') }}</td>
                                <td class="px-4 py-3 text-sm">
                                    <span class="px-2 py-0.5 text-xs rounded-full
                                        @if ($enrollment->status === 'enrolled') bg-green-100 text-green-800
                                        @elseif ($enrollment->status === 'pending_transfer') bg-yellow-100 text-yellow-800
                                        @elseif ($enrollment->status === 'inactive_due_to_teacher') bg-red-100 text-red-800
                                        @else bg-gray-100 text-gray-800 @endif">
                                        {{ match($enrollment->status) { 'enrolled' => 'مسجل', 'pending_transfer' => 'نقل معلق', 'removed' => 'مزال يدوياً', 'inactive_due_to_absence' => 'مزال لغياب', 'inactive_due_to_teacher' => 'مزال بواسطة المدرس', default => '' } }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <div class="flex flex-wrap items-center gap-3">
                                         <a href="{{ route('teacher.students.show', $enrollment) }}" class="text-blue-600 hover:text-blue-900 font-medium">الملف</a>
                                         <a href="{{ route('teacher.students.card', $enrollment) }}" class="text-purple-600 hover:text-purple-900 font-medium">البطاقة</a>
                                         @if ($otherGroups->isNotEmpty())
                                             <form method="POST" action="{{ route('teacher.students.move', $enrollment) }}" class="inline-flex items-center gap-1">
                                                 @csrf
                                                 <select name="group_id" class="text-xs border-gray-300 rounded focus:ring-blue-500 focus:border-blue-500">
                                                     @foreach ($otherGroups as $og)
                                                         <option value="{{ $og->id }}">{{ $og->title }}</option>
                                                     @endforeach
                                                 </select>
                                                 <button type="submit" class="text-green-600 hover:text-green-900 font-medium text-xs">نقل</button>
                                             </form>
                                         @endif
                                         @if ($enrollment->isInactive())
                                             <form method="POST" action="{{ route('teacher.students.restore', $enrollment) }}" class="inline">
                                                 @csrf
                                                 <button type="submit" class="text-green-600 hover:text-green-900 font-medium">استرجاع</button>
                                             </form>
                                         @else
                                             <button type="button" class="text-red-600 hover:text-red-900 font-medium" data-bs-toggle="modal" data-bs-target="#rmGrpStuModal{{ $enrollment->id }}">إزالة</button>
                                             <x-confirm-modal
                                                 :id="'rmGrpStuModal' . $enrollment->id"
                                                 title="إزالة الطالب"
                                                 body="هل أنت متأكد من إزالة {{ $enrollment->student->name }} من المجموعة؟"
                                                 confirm-text="إزالة"
                                                 cancel-text="إلغاء"
                                                 :form-id="'rmGrpStu' . $enrollment->id"
                                             />
                                         @endif
                                         <form method="POST" action="{{ route('teacher.students.remove', $enrollment) }}" class="hidden" id="rmGrpStu{{ $enrollment->id }}">
                                             @csrf
                                         </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-8 text-center text-gray-500">لا يوجد طلاب في هذه المجموعة</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($enrollments->hasPages())
                <div class="px-6 py-3">
                    {{ $enrollments->links() }}
                </div>
            @endif
        </div>

        <!-- Bulk Remove Modal -->
        @if ($enrollments->isNotEmpty())
            <div class="modal fade" id="bulkRemoveModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">إزالة المحددين</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                        </div>
                        <div class="modal-body">
                            <p class="text-gray-700">سيتم إزالة الطلاب المحددين. يبقى السجل والتقييمات.</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                            <button type="button" class="btn btn-danger" onclick="submitBulk('remove')">إزالة المحددين</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bulk Restore Modal -->
            <div class="modal fade" id="bulkRestoreModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">استرجاع المحددين</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                        </div>
                        <div class="modal-body">
                            <p class="text-gray-700">سيتم استرجاع الطلاب المزالين المحددين واستعادة وصولهم فوراً.</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                            <button type="button" class="btn btn-success" onclick="submitBulk('restore')">استرجاع المحددين</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bulk Move Modal -->
            <div class="modal fade" id="bulkMoveModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">نقل المحددين</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                        </div>
                        <div class="modal-body">
                            <p class="text-gray-700 mb-3">سيتم نقل الطلاب المحددين إلى المجموعة التالية. لن يتغير الكارنيه أو QR.</p>
                            <label class="block text-sm font-medium text-gray-700 mb-1">المجموعة الوجهة</label>
                            <select id="bulkMoveGroup" class="block w-full border-gray-300 rounded-md">
                                @foreach ($otherGroups as $og)
                                    <option value="{{ $og->id }}">{{ $og->title }} ({{ $og->currentStudents() }}/{{ $og->max_students }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                            <button type="button" class="btn btn-primary" onclick="confirmBulkMove()">نقل المحددين</button>
                        </div>
                    </div>
                </div>
        </div>
        @endif

        <!-- Add Student Modal -->
        <div class="modal fade" id="addStudentModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form method="POST" action="{{ route('teacher.groups.students.store', $group) }}">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title">إضافة طالب إلى المجموعة</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                        </div>
                        <div class="modal-body space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">اسم الطالب</label>
                                <input type="text" name="name" value="{{ old('name') }}" class="form-input" placeholder="الاسم الكامل" required>
                                @if ($errors->has('name'))
                                    <p class="text-red-600 text-xs mt-1">{{ $errors->first('name') }}</p>
                                @endif
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">موبايل الطالب</label>
                                <input type="text" name="mobile" value="{{ old('mobile') }}" class="form-input" placeholder="01xxxxxxxxx" required>
                                @if ($errors->has('mobile'))
                                    <p class="text-red-600 text-xs mt-1">{{ $errors->first('mobile') }}</p>
                                @endif
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">موبايل ولي الأمر</label>
                                <input type="text" name="parent_mobile" value="{{ old('parent_mobile') }}" class="form-input" placeholder="01xxxxxxxxx" required>
                                @if ($errors->has('parent_mobile'))
                                    <p class="text-red-600 text-xs mt-1">{{ $errors->first('parent_mobile') }}</p>
                                @endif
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">كلمة المرور</label>
                                <input type="password" name="password" class="form-input" placeholder="كلمة المرور" required>
                                @if ($errors->has('password'))
                                    <p class="text-red-600 text-xs mt-1">{{ $errors->first('password') }}</p>
                                @endif
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">تأكيد كلمة المرور</label>
                                <input type="password" name="password_confirmation" class="form-input" placeholder="تأكيد كلمة المرور" required>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                            <button type="submit" class="btn btn-primary">إضافة الطالب</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Attendance History -->
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <h2 class="text-lg font-bold text-gray-900">سجل الحضور</h2>
                <a href="{{ route('teacher.attendance.create') }}" class="text-sm text-blue-600 hover:text-blue-900 font-medium">بدء حصة جديدة</a>
            </div>
            @if ($attendanceSessions->isEmpty())
                <div class="px-6 py-8 text-center text-gray-500 text-sm">لا توجد جلسات حضور مسجلة لهذه المجموعة بعد.</div>
            @else
                <div class="divide-y divide-gray-100">
                    @foreach ($attendanceSessions as $sess)
                        <div class="px-6 py-3 flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-900">الحصة {{ $sess->lesson_number }} - {{ $sess->attendance_date->format('Y-m-d') }}</p>
                                <p class="text-xs text-gray-400">
                                    {{ $sess->started_at->format('H:i') }}
                                    @if ($sess->closed_at) • أُغلقت {{ $sess->closed_at->format('H:i') }} @endif
                                </p>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 text-xs rounded-full
                                    @if ($sess->status === 'open') bg-green-100 text-green-800
                                    @else bg-gray-100 text-gray-800 @endif">
                                    {{ $sess->status === 'open' ? 'مفتوحة' : 'مغلقة' }}
                                </span>
                                <a href="{{ route('teacher.attendance.show', $sess) }}" class="text-blue-600 hover:text-blue-900 font-medium text-sm">فتح</a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Group Activity -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-bold text-gray-900 mb-4">النشاط الأخير للمجموعة</h2>
            @if ($activities->isEmpty())
                <p class="text-gray-500 text-sm">لا يوجد نشاط بعد.</p>
            @else
                <div class="space-y-3">
                    @foreach ($activities as $activity)
                        <div class="flex items-start gap-3 border-b border-gray-100 pb-3 last:border-0">
                            <span class="mt-1 w-2 h-2 rounded-full bg-blue-500"></span>
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ $activity->title }}</p>
                                @if ($activity->description)
                                    <p class="text-xs text-gray-500">{{ $activity->description }}</p>
                                @endif
                                <p class="text-xs text-gray-400">{{ $activity->created_at->diffForHumans() }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <script>
        document.getElementById('selectAll')?.addEventListener('change', function () {
            document.querySelectorAll('.row-check').forEach(c => c.checked = this.checked);
        });

        function selectedIds() {
            return Array.from(document.querySelectorAll('.row-check:checked')).map(c => c.value);
        }

        function openBulkMove() {
            if (selectedIds().length === 0) {
                alert('يرجى تحديد طالب واحد على الأقل');
                return;
            }
            new bootstrap.Modal(document.getElementById('bulkMoveModal')).show();
        }

        function confirmBulkMove() {
            document.getElementById('bulkIds').value = JSON.stringify(selectedIds());
            document.getElementById('bulkGroupId').value = document.getElementById('bulkMoveGroup').value;
            document.getElementById('bulkForm').submit();
        }

        function submitBulk(type) {
            const ids = selectedIds();
            if (ids.length === 0) {
                alert('يرجى تحديد طالب واحد على الأقل');
                return;
            }
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = type === 'remove'
                ? '{{ route('teacher.students.bulk.remove') }}'
                : '{{ route('teacher.students.bulk.restore') }}';
            const csrf = document.createElement('input');
            csrf.type = 'hidden';
            csrf.name = '_token';
            csrf.value = '{{ csrf_token() }}';
            form.appendChild(csrf);
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'enrollment_ids';
            input.value = JSON.stringify(ids);
            form.appendChild(input);
            document.body.appendChild(form);
            form.submit();
        }

        @if ($errors->any())
            new bootstrap.Modal(document.getElementById('addStudentModal')).show();
        @endif
    </script>
</x-dashboard-layout>