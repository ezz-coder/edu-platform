<x-dashboard-layout title="طلابي">
    <x-page-header
        title="طلابي"
        description="كل الطلاب المسجلين في مجموعاتك"
    />

    @if (session('success'))
        <div class="alert-success mb-6">{{ session('success') }}</div>
    @endif

    <!-- Pending Transfer Requests -->
    @if ($transferRequests->isNotEmpty())
        <div class="card border-warning-200 mb-6">
            <div class="card-header border-warning-200 bg-warning-50">
                <h2 class="text-lg font-bold text-text-primary"><i class="bi bi-arrow-left-right text-warning-600 ml-2"></i> طلبات النقل المعلقة</h2>
            </div>
            <div class="card-body space-y-3">
                @foreach ($transferRequests as $req)
                    <div class="flex items-center justify-between bg-bg-card rounded-button p-4 border border-border-light">
                        <div>
                            <p class="font-medium text-text-primary">{{ $req->student->name }} ({{ $req->student->student_code }})</p>
                            <p class="text-sm text-text-secondary">
                                من: {{ $req->group->title }} ← إلى: {{ $req->requestedGroup->title }}
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <form method="POST" action="{{ route('teacher.students.transfer.approve', $req) }}">
                                @csrf
                                <button type="submit" class="btn-success btn-sm">قبول</button>
                            </form>
                            <form method="POST" action="{{ route('teacher.students.transfer.reject', $req) }}">
                                @csrf
                                <button type="submit" class="btn-danger btn-sm">رفض</button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Search & Filter -->
    <div class="card p-6 mb-6">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="form-group">
                <label for="search" class="form-label">بحث</label>
                <input id="search" name="search" type="text" class="form-input" value="{{ old('search', $filters['search'] ?? '') }}" placeholder="الاسم / الكود / الموبايل">
            </div>
            <div class="form-group">
                <label for="group_id" class="form-label">المجموعة</label>
                <select id="group_id" name="group_id" class="form-select">
                    <option value="">كل المجموعات</option>
                    @foreach ($groups as $g)
                        <option value="{{ $g->id }}" {{ ($filters['group_id'] ?? '') == $g->id ? 'selected' : '' }}>{{ $g->title }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="status" class="form-label">الحالة</label>
                <select id="status" name="status" class="form-select">
                    <option value="">كل الحالات</option>
                    <option value="enrolled" {{ ($filters['status'] ?? '') == 'enrolled' ? 'selected' : '' }}>مسجل</option>
                    <option value="pending_transfer" {{ ($filters['status'] ?? '') == 'pending_transfer' ? 'selected' : '' }}>طلب نقل معلق</option>
                    <option value="removed" {{ ($filters['status'] ?? '') == 'removed' ? 'selected' : '' }}>مزال</option>
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="btn-primary w-full">
                    <i class="bi bi-search"></i> بحث
                </button>
            </div>
        </form>
    </div>

    <!-- Students Table -->
    <div class="table-container">
        <div class="overflow-x-auto">
            <table class="table">
                <thead>
                    <tr>
                        <th>كود الطالب</th>
                        <th>الاسم</th>
                        <th>موبايل الطالب</th>
                        <th>موبايل ولي الأمر</th>
                        <th>المجموعة</th>
                        <th>تاريخ التسجيل</th>
                        <th>الحالة</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($enrollments as $enrollment)
                        <tr>
                            <td class="font-medium text-text-primary">{{ $enrollment->student->student_code }}</td>
                            <td class="text-text-primary">{{ $enrollment->student->name }}</td>
                            <td class="text-text-secondary">{{ $enrollment->student->mobile }}</td>
                            <td class="text-text-secondary">{{ $enrollment->student->parent_mobile }}</td>
                            <td class="text-text-secondary">{{ $enrollment->group->title }}</td>
                            <td class="text-text-secondary">{{ $enrollment->created_at->format('Y-m-d') }}</td>
                            <td>
                                <span class="badge {{ $enrollment->status === 'enrolled' ? 'badge-success' : ($enrollment->status === 'pending_transfer' ? 'badge-warning' : 'badge-neutral') }}">
                                    {{ match($enrollment->status) {
                                        'enrolled' => 'مسجل',
                                        'pending_transfer' => 'طلب نقل معلق',
                                        'removed' => 'مزال',
                                        default => '',
                                    } }}
                                </span>
                            </td>
                            <td>
                                <form method="POST" action="{{ route('teacher.students.remove', $enrollment) }}" class="hidden" id="rmStu{{ $enrollment->id }}">
                                    @csrf
                                </form>
                                <button type="button" class="text-danger-600 hover:text-danger-700 font-medium text-sm transition-colors duration-150" data-bs-toggle="modal" data-bs-target="#rmStuModal{{ $enrollment->id }}">إزالة</button>
                                <x-confirm-modal
                                    :id="'rmStuModal' . $enrollment->id"
                                    title="إزالة الطالب"
                                    body="هل أنت متأكد من إزالة {{ $enrollment->student->name }}؟ يمكن إعادته لاحقاً عبر طلب نقل."
                                    confirm-text="إزالة"
                                    cancel-text="إلغاء"
                                    :form-id="'rmStu' . $enrollment->id"
                                />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                <x-empty-state icon="bi-people" title="لا يوجد طلاب مطابقون" message="جرّب تغيير معايير البح أو أضف طلاباً لمجموعاتك." />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Move student form (only if teacher has more than one group) -->
    @if ($groups->count() > 1)
        <div class="card p-6 mt-6">
            <p class="text-sm text-text-secondary mb-3"><i class="bi bi-arrow-left-right ml-2"></i> نقل طالب إلى مجموعة أخرى:</p>
            <div class="flex flex-wrap gap-3">
                @foreach ($enrollments->where('status', 'enrolled') as $enrollment)
                    <form method="POST" action="{{ route('teacher.students.move', $enrollment) }}" class="inline-flex items-center gap-2">
                        @csrf
                        <span class="text-sm text-text-primary">{{ $enrollment->student->name }}</span>
                        <select name="group_id" class="form-select text-sm py-1.5">
                            @foreach ($groups as $target)
                                @if ($target->id !== $enrollment->group_id)
                                    <option value="{{ $target->id }}">{{ $target->title }} ({{ $target->currentStudents() }}/{{ $target->max_students }})</option>
                                @endif
                            @endforeach
                        </select>
                        <button type="submit" class="text-primary-600 hover:text-primary-700 text-sm font-medium transition-colors duration-150">نقل</button>
                    </form>
                @endforeach
            </div>
        </div>
    @endif
</x-dashboard-layout>