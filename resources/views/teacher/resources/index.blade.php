<x-dashboard-layout title="المصادر">
    <x-page-header
        title="المصادر"
        description="كل المصادر التي نشرتها لطلابك"
        :action="'<a href=\''.route('teacher.resources.create').'\' class=\'px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition text-sm font-medium\'><i class=\'bi bi-plus-lg\'></i> مصدر جديد</a>'"
    />

    @if (session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-2 rounded-lg text-sm">{{ session('success') }}</div>
    @endif

    <div class="mb-4">
        <a href="{{ route('teacher.resources.recycle-bin') }}" class="text-gray-600 hover:underline">سلة المحذوفات - المصادر</a>
    </div>

    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <table class="w-full text-sm text-right">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="px-4 py-3">العنوان</th>
                    <th class="px-4 py-3">النوع</th>
                    <th class="px-4 py-3">الحالة</th>
                    <th class="px-4 py-3">مثبّت</th>
                    <th class="px-4 py-3">تاريخ النشر</th>
                    <th class="px-4 py-3">إجراءات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($resources as $resource)
                    <tr>
                        <td class="px-4 py-3 font-medium text-gray-900">{{ $resource->title }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $resource->type }}</td>
                        <td class="px-4 py-3">
                            @if ($resource->status === 'visible')
                                <span class="text-green-700">ظاهر</span>
                            @elseif ($resource->status === 'hidden')
                                <span class="text-gray-500">مخفي</span>
                            @else
                                <span class="text-red-600">مؤرشف</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">{{ $resource->pinned ? '✓' : '—' }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $resource->publish_date?->format('Y-m-d') ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap gap-2">
                                <a href="{{ route('teacher.resources.edit', $resource) }}" class="text-blue-600 hover:underline">تعديل</a>
                                <form method="POST" action="{{ route('teacher.resources.pin', $resource) }}">
                                    @csrf
                                    <button class="text-yellow-600 hover:underline">{{ $resource->pinned ? 'إلغاء التثبيت' : 'تثبيت' }}</button>
                                </form>
                                <form method="POST" action="{{ route('teacher.resources.duplicate', $resource) }}">
                                    @csrf
                                    <button class="text-purple-600 hover:underline">نسخ</button>
                                </form>
                                <form method="POST" action="{{ route('teacher.resources.destroy', $resource) }}" class="hidden" id="delRes{{ $resource->id }}">
                                    @csrf @method('DELETE')
                                </form>
                                <button type="button" class="text-red-600 hover:underline" data-bs-toggle="modal" data-bs-target="#delResModal{{ $resource->id }}">حذف</button>
                                <x-confirm-modal
                                    :id="'delResModal' . $resource->id"
                                    title="حذف المصدر"
                                    body="سيتم حذف المصدر نهائياً ولا يمكن التراجع."
                                    confirm-text="حذف"
                                    cancel-text="إلغاء"
                                    :form-id="'delRes' . $resource->id"
                                />
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8">
                            <x-empty-state icon="bi-file-earmark" title="لا توجد مصادر بعد" message="ابدأ بنشر أول مصدر لطلابك." :action="'<a href=\''.route('teacher.resources.create').'\' class=\'px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition text-sm font-medium\'><i class=\'bi bi-plus-lg\'></i> مصدر جديد</a>'" />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $resources->links() }}
</x-dashboard-layout>