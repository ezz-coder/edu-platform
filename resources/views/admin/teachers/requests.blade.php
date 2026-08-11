<x-dashboard-layout title="طلبات تسجيل المدرسين">
    <div class="space-y-6">
        <x-page-header title="طلبات تسجيل المدرسين" description="الموافقة أو رفض طلبات تسجيل المدرسين الجدد" />

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
            @if ($requests->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">الاسم</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">رقم الموبايل</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">البريد الإلكتروني</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">تاريخ التسجيل</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach ($requests as $request)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">{{ $request->name }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-500">{{ $request->mobile ?? '—' }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-500">{{ $request->email ?? '—' }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-500">{{ $request->created_at->format('Y-m-d') }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2 space-x-reverse">
                                        <!-- Approve -->
                                        <button type="button" class="text-green-600 hover:text-green-900 ml-2"
                                            data-bs-toggle="modal" data-bs-target="#approveModal{{ $request->id }}">
                                            موافقة
                                        </button>

                                        <!-- Reject -->
                                        <button type="button" class="text-red-600 hover:text-red-900"
                                            data-bs-toggle="modal" data-bs-target="#rejectModal{{ $request->id }}">
                                            رفض
                                        </button>

                                        <!-- Approve Modal -->
                                        <x-confirm-modal
                                            id="approveModal{{ $request->id }}"
                                            title="تأكيد الموافقة"
                                            body="هل أنت متأكد من الموافقة على طلب المدرس {{ $request->name }}؟"
                                            confirmText="موافقة"
                                            cancelText="إلغاء"
                                            :formId="'approve-form-' . $request->id"
                                            :danger="false"
                                        />

                                        <form id="approve-form-{{ $request->id }}" method="POST" action="{{ route('admin.teachers.approve', $request) }}" class="hidden">
                                            @csrf
                                        </form>

                                        <!-- Reject Modal -->
                                        <div class="modal fade" id="rejectModal{{ $request->id }}" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">تأكيد الرفض</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                                                    </div>
                                                    <form method="POST" action="{{ route('admin.teachers.reject', $request) }}">
                                                        @csrf
                                                        <div class="modal-body">
                                                            <p class="text-gray-700 mb-3">هل أنت متأكد من رفض طلب المدرس {{ $request->name }}؟</p>
                                                            <div class="mb-3">
                                                                <label class="block text-sm font-medium text-gray-700 mb-1">سبب الرفض (اختياري)</label>
                                                                <textarea name="rejection_reason" rows="2" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm" placeholder="أكتب سبب الرفض هنا..."></textarea>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                                                            <button type="submit" class="btn btn-danger">رفض</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <x-empty-state>
                    <p class="text-gray-500">لا توجد طلبات تسجيل جديدة</p>
                </x-empty-state>
            @endif
        </div>

        <div class="mt-4">
            {{ $requests->links() }}
        </div>
    </div>
</x-dashboard-layout>