<x-dashboard-layout title="المساعدون">
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-bold text-gray-900">المساعدون</h2>
            <a href="{{ route('teacher.assistants.create') }}" class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition text-sm font-medium">＋ إضافة مساعد</a>
        </div>

        @if ($assistants->isEmpty())
            <div class="bg-white rounded-lg shadow-sm p-10 text-center text-gray-500">
                لا يوجد أي مساعدين حتى الآن. أضف مساعداً لمساعدتك في المهام.
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach ($assistants as $a)
                    <div class="bg-white rounded-lg shadow-sm p-5 border border-gray-200">
                        <div class="flex items-start justify-between mb-3">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">{{ $a->name }}</h3>
                                <p class="text-sm text-gray-500">@ {{ $a->username }}</p>
                            </div>
                            <span class="px-2 py-1 text-xs font-semibold rounded-full
                                @if ($a->status === 'active') bg-green-100 text-green-800
                                @else bg-gray-100 text-gray-800 @endif">
                                {{ $a->status === 'active' ? 'نشط' : 'معطل' }}
                            </span>
                        </div>

                        <!-- Permissions summary -->
                        <div class="flex flex-wrap gap-1 mb-4">
                            @php
                                $active = collect($permissionLabels)->filter(fn ($label, $key) => $a->permissions?->{$key});
                            @endphp
                            @if ($active->isEmpty())
                                <span class="text-xs text-gray-400">بدون صلاحيات</span>
                            @else
                                @foreach ($active as $label)
                                    <span class="text-xs bg-gray-100 text-gray-700 px-2 py-1 rounded">{{ $label }}</span>
                                @endforeach
                            @endif
                        </div>

                        <!-- Actions -->
                        <div class="grid grid-cols-3 gap-2 pt-3 border-t border-gray-200 text-sm">
                            <a href="{{ route('teacher.assistants.edit', $a) }}" class="text-center py-2 bg-blue-50 text-blue-700 rounded hover:bg-blue-100 font-medium">تعديل الصلاحيات</a>

                            @if ($a->status === 'active')
                                <form method="POST" action="{{ route('teacher.assistants.disable', $a) }}" class="contents">
                                    @csrf
                                    <button type="submit" class="text-center py-2 bg-yellow-50 text-yellow-700 rounded hover:bg-yellow-100 font-medium">تعطيل</button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('teacher.assistants.enable', $a) }}" class="contents">
                                    @csrf
                                    <button type="submit" class="text-center py-2 bg-green-50 text-green-700 rounded hover:bg-green-100 font-medium">تفعيل</button>
                                </form>
                            @endif

                                <form method="POST" action="{{ route('teacher.assistants.destroy', $a) }}" id="delete-form-{{ $a->id }}" class="contents">
                                @csrf
                                @method('DELETE')
                                <button type="button" onclick="openDeleteModal({{ $a->id }})" class="text-center py-2 bg-red-50 text-red-700 rounded hover:bg-red-100 font-medium">حذف</button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        <!-- Hidden form that the modal submits through -->
        <form id="deleteModalForm" method="POST" class="d-none">@csrf @method('DELETE')</form>

        <!-- Delete confirmation modal -->
        <x-confirm-modal
            id="deleteModal"
            title="حذف المساعد"
            body="هل أنت متأكد من حذف هذا المساعد؟ بعد الحذف لن يستطيع الدخول إلى المنصة أو تنفيذ أي إجراءات مرتبطة بحسابك."
            confirmText="حذف"
            formId="deleteModalForm"
        />
    </div>

    <script>
        let pendingDeleteId = null;
        function openDeleteModal(id) {
            pendingDeleteId = id;
            const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
            modal.show();
        }
        document.addEventListener('shown.bs.modal', function (e) {
            if (e.target.id === 'deleteModal' && pendingDeleteId) {
                const form = document.getElementById('deleteModalForm');
                const src = document.getElementById('delete-form-' + pendingDeleteId);
                form.setAttribute('action', src.getAttribute('action'));
                while (form.firstChild) form.removeChild(form.firstChild);
                src.querySelectorAll('input').forEach(i => form.appendChild(i.cloneNode()));
                pendingDeleteId = null;
            }
        });
    </script>
</x-dashboard-layout>