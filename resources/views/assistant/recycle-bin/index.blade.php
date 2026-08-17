<x-dashboard-layout title="سلة المحذوفات">
    <x-page-header
        title="سلة المحذوفات"
        description="العناصر المحذوفة يمكن استرجاعها أو حذفها نهائياً"
    />

    @if (session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-2 rounded-lg text-sm mb-4">{{ session('success') }}</div>
    @endif

    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <table class="w-full text-sm text-right">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="px-4 py-3">العنوان</th>
                    <th class="px-4 py-3">نوع</th>
                    <th class="px-4 py-3">تاريخ الحذف</th>
                    <th class="px-4 py-3">إجراءات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($items as $item)
                    @php
                        $routeBase = match ($type) {
                            'question_bank' => 'assistant.question-bank',
                            default => 'assistant.' . $type . 's',
                        };
                    @endphp
                    <tr>
                        <td class="px-4 py-3 font-medium text-gray-900">{{ $item->title }}</td>
                        <td class="px-4 py-3 text-gray-600">
                            @php
                                $typeLabel = match ($type) {
                                    'question_bank' => 'بنك الأسئلة',
                                    'lesson' => 'درس',
                                    default => 'امتحان',
                                };
                            @endphp
                            {{ $typeLabel }}
                        </td>
                        <td class="px-4 py-3 text-gray-600">{{ $item->deleted_at?->format('Y-m-d H:i') ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap gap-2">
                                <form method="POST" action="{{ route($routeBase . '.restore', $item) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="text-green-600 hover:underline">استرجاع</button>
                                </form>
                                <form method="POST" action="{{ route($routeBase . '.force-delete', $item) }}" class="hidden" id="forceDelete{{ $item->id }}">
                                    @csrf @method('DELETE')
                                </form>
                                <button type="button" class="text-red-600 hover:underline" data-bs-toggle="modal" data-bs-target="#forceDeleteModal{{ $item->id }}">حذف نهائي</button>
                                <x-confirm-modal
                                    :id="'forceDeleteModal' . $item->id"
                                    title="حذف نهائي"
                                    body="هل أنت متأكد من الحذف النهائي؟ لا يمكن التراجع بعد الحذف."
                                    confirm-text="حذف نهائي"
                                    cancel-text="إلغاء"
                                    :form-id="'forceDelete' . $item->id"
                                />
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-8 text-center text-gray-500">لا توجد عناصر محذوفة</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $items->links() }}
</x-dashboard-layout>
