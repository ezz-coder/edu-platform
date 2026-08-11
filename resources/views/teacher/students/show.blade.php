<x-dashboard-layout title="ملف الطالب">
    <div class="space-y-6">
        <a href="{{ route('teacher.groups.show', $group) }}" class="inline-flex items-center text-gray-600 hover:text-gray-900">
            <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
            العودة إلى المجموعة
        </a>

        @if (session('success'))
            <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">{{ session('success') }}</div>
        @endif

        <!-- Student Profile -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-start justify-between flex-wrap gap-3">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">{{ $student->name }}</h1>
                    <p class="text-sm text-gray-500 mt-1">كود الطالب: {{ $student->student_code }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('teacher.students.card', $enrollment) }}" class="px-3 py-2 bg-purple-50 text-purple-700 rounded-lg hover:bg-purple-100 transition text-sm">البطاقة</a>
                    <a href="{{ route('teacher.students.history', $enrollment) }}" class="px-3 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition text-sm">سجل التحويلات</a>
                </div>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mt-6 text-sm text-gray-600">
                <div>
                    <p class="text-gray-400">موبايل الطالب</p>
                    <p class="font-medium text-gray-900">{{ $student->mobile }}</p>
                </div>
                <div>
                    <p class="text-gray-400">موبايل ولي الأمر</p>
                    <p class="font-medium text-gray-900">{{ $student->parent_mobile }}</p>
                </div>
                <div>
                    <p class="text-gray-400">المدرس</p>
                    <p class="font-medium text-gray-900">{{ $group->teacher->name }}</p>
                </div>
                <div>
                    <p class="text-gray-400">كود المدرس</p>
                    <p class="font-medium text-gray-900">{{ $group->teacher->teacher_code }}</p>
                </div>
                <div>
                    <p class="text-gray-400">المجموعة الحالية</p>
                    <p class="font-medium text-gray-900">{{ $group->title }}</p>
                </div>
                <div>
                    <p class="text-gray-400">المرحلة</p>
                    <p class="font-medium text-gray-900">{{ $group->grade_level }}</p>
                </div>
                <div>
                    <p class="text-gray-400">تاريخ التسجيل</p>
                    <p class="font-medium text-gray-900">{{ $enrollment->created_at->format('Y-m-d') }}</p>
                </div>
                <div>
                    <p class="text-gray-400">كود الكارنيه</p>
                    <p class="font-medium text-gray-900 font-mono">{{ $enrollment->card_id }}</p>
                </div>
                <div>
                    <p class="text-gray-400">رمز QR</p>
                    <p class="font-medium text-gray-900 font-mono text-xs break-all">{{ $enrollment->qr_token }}</p>
                </div>
                <div>
                    <p class="text-gray-400">حالة الحساب</p>
                    <p class="font-medium text-gray-900">
                        <span class="px-2 py-0.5 text-xs rounded-full
                            @if ($student->status === 'active') bg-green-100 text-green-800
                            @else bg-gray-100 text-gray-800 @endif">
                            {{ $student->status === 'active' ? 'نشط' : $student->status }}
                        </span>
                    </p>
                </div>
                <div>
                    <p class="text-gray-400">حالة التسجيل</p>
                    <p class="font-medium text-gray-900">
                        <span class="px-2 py-0.5 text-xs rounded-full
                            @if ($enrollment->status === 'enrolled') bg-green-100 text-green-800
                            @elseif ($enrollment->status === 'pending_transfer') bg-yellow-100 text-yellow-800
                            @elseif ($enrollment->status === 'inactive_due_to_teacher') bg-red-100 text-red-800
                            @else bg-gray-100 text-gray-800 @endif">
                            {{ match($enrollment->status) { 'enrolled' => 'مسجل', 'pending_transfer' => 'نقل معلق', 'removed' => 'مزال يدوياً', 'inactive_due_to_absence' => 'مزال لغياب', 'inactive_due_to_teacher' => 'مزال بواسطة المدرس', default => '' } }}
                        </span>
                    </p>
                </div>
                @if ($enrollment->inactivated_at)
                    <div>
                        <p class="text-gray-400">تاريخ الإزالة</p>
                        <p class="font-medium text-gray-900">{{ $enrollment->inactivated_at->format('Y-m-d H:i') }}</p>
                    </div>
                @endif
                @if ($enrollment->histories->isNotEmpty())
                    <div>
                        <p class="text-gray-400">آخر نقل</p>
                        <p class="font-medium text-gray-900">{{ $enrollment->histories->first()->changed_at->format('Y-m-d H:i') }}</p>
                    </div>
                @endif
                @if ($enrollment->isInactive())
                    <div>
                        <p class="text-gray-400">حالة الاسترجاع</p>
                        <p class="font-medium text-gray-900">مزال — يمكن استرجاعه</p>
                    </div>
                @else
                    <div>
                        <p class="text-gray-400">حالة الاسترجاع</p>
                        <p class="font-medium text-gray-900">نشط</p>
                    </div>
                @endif
            </div>

            <div class="flex items-center gap-3 mt-6 flex-wrap">
                @if ($enrollment->status === 'enrolled' && $otherGroups->isNotEmpty())
                    <button type="button" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm" data-bs-toggle="modal" data-bs-target="#moveStudentModal">نقل الطالب</button>
                @endif
                @if ($enrollment->isInactive())
                    <button type="button" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition text-sm" data-bs-toggle="modal" data-bs-target="#restoreStudentModal">استرجاع الطالب</button>
                @endif
                @if ($enrollment->status === 'enrolled')
                    <button type="button" class="px-4 py-2 bg-red-50 text-red-700 rounded-lg hover:bg-red-100 transition text-sm" data-bs-toggle="modal" data-bs-target="#removeStudentModal">إزالة الطالب</button>
                @endif
                <a href="{{ route('teacher.groups.show', $group) }}" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition text-sm">العودة للمجموعة</a>
            </div>
        </div>

        <!-- Move Student Modal (RULE 2) -->
        @if ($enrollment->status === 'enrolled' && $otherGroups->isNotEmpty())
            <div class="modal fade" id="moveStudentModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">نقل الطالب</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                        </div>
                        <form method="POST" action="{{ route('teacher.students.move', $enrollment) }}">
                            @csrf
                            <div class="modal-body">
                                <p class="text-gray-700 mb-3">سيتم نقل الطالب إلى المجموعة الجديدة.</p>
                                <p class="text-gray-700 mb-3">لن يتم إنشاء حساب جديد.</p>
                                <p class="text-gray-700 mb-3">لن يتغير الكارنيه.</p>
                                <p class="text-gray-700 mb-3">لن يتغير QR.</p>
                                <p class="text-gray-700 mb-3">سيتم تحديث صلاحيات الوصول فقط.</p>
                                <label class="block text-sm font-medium text-gray-700 mb-1">المجموعة الوجهة</label>
                                <select name="group_id" class="block w-full border-gray-300 rounded-md">
                                    @foreach ($otherGroups as $og)
                                        <option value="{{ $og->id }}">{{ $og->title }} ({{ $og->currentStudents() }}/{{ $og->max_students }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                                <button type="submit" class="btn btn-primary">نقل الطالب</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        <!-- Remove Student Modal (RULE 4) -->
        @if ($enrollment->status === 'enrolled')
            <div class="modal fade" id="removeStudentModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">إزالة الطالب</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                        </div>
                        <div class="modal-body">
                            <p class="text-gray-700">هل أنت متأكد من إزالة {{ $student->name }}؟</p>
                            <p class="text-gray-700 mt-2">يبقى الحساب والسجل والتقييمات. يُلغى الوصول المستقبلي فقط.</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                            <form method="POST" action="{{ route('teacher.students.remove', $enrollment) }}">
                                @csrf
                                <button type="submit" class="btn btn-danger">إزالة</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Restore Student Modal (RULE 5) -->
        @if ($enrollment->isInactive())
            <div class="modal fade" id="restoreStudentModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">استرجاع الطالب</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                        </div>
                        <div class="modal-body">
                            <p class="text-gray-700 mb-2">سيعود الطالب للمجموعة الحالية.</p>
                            <p class="text-gray-700 mb-2">وسيستعيد الوصول إلى الموارد</p>
                            <p class="text-gray-700 mb-2">والامتحانات</p>
                            <p class="text-gray-700 mb-2">والحجوزات</p>
                            <p class="text-gray-700 mb-2">ابتداءً من الآن.</p>
                            <p class="text-gray-700 mb-2">لن يتم تغيير الكارنيه.</p>
                            <p class="text-gray-700">لن يتم تغيير QR.</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                            <form method="POST" action="{{ route('teacher.students.restore', $enrollment) }}">
                                @csrf
                                <button type="submit" class="btn btn-success">استرجاع</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Enrollment History (RULE 6) -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-bold text-gray-900 mb-4">سجل التحويلات</h2>
            @if ($enrollment->histories->isEmpty())
                <p class="text-gray-500 text-sm">لا يوجد سجل بعد.</p>
            @else
                <div class="space-y-3">
                    @foreach ($enrollment->histories as $h)
                        <div class="flex items-start gap-3 border-b border-gray-100 pb-3 last:border-0">
                            <span class="mt-1 w-2 h-2 rounded-full
                                @if ($h->action === 'moved') bg-blue-500
                                @elseif ($h->action === 'restored') bg-green-500
                                @elseif ($h->action === 'removed' || $h->action === 'attendance_removal') bg-red-500
                                @else bg-gray-500 @endif"></span>
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ $h->actionLabel() }}</p>
                                <p class="text-xs text-gray-500">
                                    @if ($h->fromGroup) من: {{ $h->fromGroup->title }} @endif
                                    @if ($h->toGroup && $h->toGroup->id !== $h->fromGroup?->id) ← إلى: {{ $h->toGroup->title }} @endif
                                </p>
                                <p class="text-xs text-gray-400">{{ $h->changed_at->format('Y-m-d H:i') }} • بواسطة: {{ $h->performed_by ?? 'النظام' }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Student Activity Timeline -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-bold text-gray-900 mb-4">النشاط</h2>
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
</x-dashboard-layout>