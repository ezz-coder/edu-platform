<x-dashboard-layout title="النسخ الاحتياطي">
    <div class="space-y-6">
        <x-page-header title="النسخ الاحتياطي" description="إنشاء وتحميل النسخ الاحتياطية للمنصة">
            <x-slot:action>
                <button type="button" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm"
                    data-bs-toggle="modal" data-bs-target="#createBackupModal">
                    <svg class="w-4 h-4 inline ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    إنشاء نسخة احتياطية
                </button>
            </x-slot:action>
        </x-page-header>

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

        <!-- Create Backup Modal -->
        <x-confirm-modal
            id="createBackupModal"
            title="تأكيد إنشاء نسخة احتياطية"
            body="هل أنت متأكد من إنشاء نسخة احتياطية جديدة؟ قد تستغرق هذه العملية بعض الوقت."
            confirmText="إنشاء"
            cancelText="إلغاء"
            :formId="'create-backup-form'"
            :danger="false"
        />
        <form id="create-backup-form" method="POST" action="{{ route('admin.backup.create') }}" class="hidden">@csrf</form>

        <!-- Delete Backup Modal -->
        <x-confirm-modal
            id="deleteBackupModal"
            title="تأكيد حذف النسخة الاحتياطية"
            body="هل أنت متأكد من حذف هذه النسخة الاحتياطية؟ لا يمكن التراجع عن هذا الإجراء."
            confirmText="حذف"
            cancelText="إلغاء"
            :formId="'delete-backup-form'"
            :danger="true"
        />
        <form id="delete-backup-form" method="POST" class="hidden">@csrf</form>

        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            @if ($backups->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">اسم الملف</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">الحجم</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">النوع</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">تاريخ الإنشاء</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">إجراءات</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach ($backups as $backup)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $backup->filename }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ number_format($backup->size / 1024, 2) }} KB</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">{{ $backup->type === 'manual' ? 'يدوي' : $backup->type }}</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $backup->created_at->format('Y-m-d H:i') }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <a href="{{ route('admin.backup.download', $backup) }}" class="text-blue-600 hover:text-blue-900 inline-flex items-center gap-1">
                                            <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                            تحميل
                                        </a>
                                        <button type="button" class="text-red-600 hover:text-red-900 inline-flex items-center gap-1 ms-4"
                                            onclick="prepareDeleteBackup('{{ route('admin.backup.delete', $backup) }}')"
                                            data-bs-toggle="modal" data-bs-target="#deleteBackupModal">
                                            <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-4v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            حذف
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <x-empty-state>
                    <p class="text-gray-500">لا توجد نسخ احتياطية بعد</p>
                </x-empty-state>
            @endif
        </div>

        <div class="mt-4">
            {{ $backups->links() }}
        </div>
    </div>

    <script>
        function prepareDeleteBackup(url) {
            document.getElementById('delete-backup-form').action = url;
        }
    </script>
</x-dashboard-layout>