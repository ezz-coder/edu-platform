<x-dashboard-layout title="إدارة المدرسين">
    <div class="space-y-6">
        <x-page-header title="إدارة المدرسين" description="تفعيل، تعليق، حذف واستعادة المدرسين" />

        @if (session('success'))
            <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
                {{ session('error') }}
            </div>
        @endif

        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            @if ($teachers->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">الاسم</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">رقم الموبايل</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">كود المدرس</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">المجموعات</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">الحالة</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach ($teachers as $teacher)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">{{ $teacher->name }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-500">{{ $teacher->mobile ?? '—' }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-500">{{ $teacher->teacher_code ?? '—' }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-500">{{ $teacher->study_groups_count }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full
                                            @if ($teacher->status === 'active') bg-green-100 text-green-800
                                            @elseif ($teacher->status === 'pending') bg-yellow-100 text-yellow-800
                                            @elseif ($teacher->status === 'rejected') bg-red-100 text-red-800
                                            @elseif ($teacher->status === 'suspended') bg-gray-100 text-gray-800
                                            @elseif ($teacher->status === 'inactive') bg-orange-100 text-orange-800
                                            @elseif ($teacher->status === 'deleted') bg-red-100 text-red-800
                                            @endif">
                                            {{ match($teacher->status) {
                                                'active' => 'نشط',
                                                'pending' => 'قيد الانتظار',
                                                'rejected' => 'مرفوض',
                                                'suspended' => 'معلق',
                                                'inactive' => 'غير نشط',
                                                'deleted' => 'محذوف',
                                                default => '',
                                            } }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex gap-2">
                                            @if ($teacher->status !== 'active')
                                                <button type="button" class="text-green-600 hover:text-green-900 text-xs"
                                                    data-bs-toggle="modal" data-bs-target="#activateModal{{ $teacher->id }}">
                                                    تفعيل
                                                </button>
                                            @endif

                                            @if ($teacher->status === 'active')
                                                <button type="button" class="text-orange-600 hover:text-orange-900 text-xs"
                                                    data-bs-toggle="modal" data-bs-target="#deactivateModal{{ $teacher->id }}">
                                                    إلغاء تفعيل
                                                </button>
                                            @endif

                                            @if ($teacher->status !== 'suspended' && $teacher->status !== 'deleted')
                                                <button type="button" class="text-gray-600 hover:text-gray-900 text-xs"
                                                    data-bs-toggle="modal" data-bs-target="#suspendModal{{ $teacher->id }}">
                                                    تعليق
                                                </button>
                                            @endif

                                            @if ($teacher->status === 'suspended' || $teacher->status === 'inactive' || $teacher->status === 'deleted')
                                                <button type="button" class="text-blue-600 hover:text-blue-900 text-xs"
                                                    data-bs-toggle="modal" data-bs-target="#restoreModal{{ $teacher->id }}">
                                                    استعادة
                                                </button>
                                            @endif

                                            @if ($teacher->status !== 'deleted')
                                                <button type="button" class="text-red-600 hover:text-red-900 text-xs"
                                                    data-bs-toggle="modal" data-bs-target="#deleteModal{{ $teacher->id }}">
                                                    حذف
                                                </button>
                                            @endif
                                        </div>

                                        <!-- Activate Modal -->
                                        <x-confirm-modal
                                            id="activateModal{{ $teacher->id }}"
                                            title="تأكيد التفعيل"
                                            body="هل أنت متأكد من تفعيل المدرس {{ $teacher->name }}؟"
                                            confirmText="تفعيل"
                                            cancelText="إلغاء"
                                            :formId="'activate-form-' . $teacher->id"
                                            :danger="false"
                                        />
                                        <form id="activate-form-{{ $teacher->id }}" method="POST" action="{{ route('admin.teachers.activate', $teacher) }}" class="hidden">@csrf</form>

                                        <!-- Deactivate Modal -->
                                        <x-confirm-modal
                                            id="deactivateModal{{ $teacher->id }}"
                                            title="تأكيد إلغاء التفعيل"
                                            body="هل أنت متأكد من إلغاء تفعيل المدرس {{ $teacher->name }}؟"
                                            confirmText="إلغاء التفعيل"
                                            cancelText="إلغاء"
                                            :formId="'deactivate-form-' . $teacher->id"
                                        />
                                        <form id="deactivate-form-{{ $teacher->id }}" method="POST" action="{{ route('admin.teachers.deactivate', $teacher) }}" class="hidden">@csrf</form>

                                        <!-- Suspend Modal -->
                                        <x-confirm-modal
                                            id="suspendModal{{ $teacher->id }}"
                                            title="تأكيد التعليق"
                                            body="هل أنت متأكد من تعليق المدرس {{ $teacher->name }}؟"
                                            confirmText="تعليق"
                                            cancelText="إلغاء"
                                            :formId="'suspend-form-' . $teacher->id"
                                        />
                                        <form id="suspend-form-{{ $teacher->id }}" method="POST" action="{{ route('admin.teachers.suspend', $teacher) }}" class="hidden">@csrf</form>

                                        <!-- Restore Modal -->
                                        <x-confirm-modal
                                            id="restoreModal{{ $teacher->id }}"
                                            title="تأكيد الاستعادة"
                                            body="هل أنت متأكد من استعادة المدرس {{ $teacher->name }}؟"
                                            confirmText="استعادة"
                                            cancelText="إلغاء"
                                            :formId="'restore-form-' . $teacher->id"
                                            :danger="false"
                                        />
                                        <form id="restore-form-{{ $teacher->id }}" method="POST" action="{{ route('admin.teachers.restore', $teacher) }}" class="hidden">@csrf</form>

                                        <!-- Delete Modal -->
                                        <x-confirm-modal
                                            id="deleteModal{{ $teacher->id }}"
                                            title="تأكيد الحذف"
                                            body="هل أنت متأكد من حذف المدرس {{ $teacher->name }}؟"
                                            confirmText="حذف"
                                            cancelText="إلغاء"
                                            :formId="'delete-form-' . $teacher->id"
                                        />
                                        <form id="delete-form-{{ $teacher->id }}" method="POST" action="{{ route('admin.teachers.soft-delete', $teacher) }}" class="hidden">@csrf</form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <x-empty-state>
                    <p class="text-gray-500">لا يوجد مدرسون</p>
                </x-empty-state>
            @endif
        </div>

        <div class="mt-4">
            {{ $teachers->links() }}
        </div>
    </div>
</x-dashboard-layout>
