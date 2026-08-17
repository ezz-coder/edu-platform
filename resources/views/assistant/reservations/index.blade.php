<x-dashboard-layout title="الحجوزات">
    <x-page-header
        title="الحجوزات"
        description="كل الحجوزات التي أدارها المدرس"
        :action="'<a href=\''.route('assistant.reservations.create').'\' class=\'px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition text-sm font-medium\'><i class=\'bi bi-plus-lg\'></i> حجز جديد</a>'"
    />

    @if (session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-2 rounded-lg text-sm">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-2 rounded-lg text-sm">{{ session('error') }}</div>
    @endif

    {{-- Search and Filter Form --}}
    <div class="bg-white rounded-lg shadow-sm p-4 mb-4">
        <form method="GET" action="{{ route('assistant.reservations.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">بحث</label>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="بحث بالعنوان أو الوصف..." class="w-full border border-gray-300 rounded-lg px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">النوع</label>
                <select name="type" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                    <option value="">كل الأنواع</option>
                    @foreach ($types as $value => $label)
                        <option value="{{ $value }}" {{ ($filters['type'] ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">الحالة</label>
                <select name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                    <option value="">كل الحالات</option>
                    <option value="draft" {{ ($filters['status'] ?? '') === 'draft' ? 'selected' : '' }}>مسودة</option>
                    <option value="open" {{ ($filters['status'] ?? '') === 'open' ? 'selected' : '' }}>مفتوح</option>
                    <option value="closed" {{ ($filters['status'] ?? '') === 'closed' ? 'selected' : '' }}>مغلق</option>
                    <option value="archived" {{ ($filters['status'] ?? '') === 'archived' ? 'selected' : '' }}>مؤرشف</option>
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">فلتر</button>
                <a href="{{ route('assistant.reservations.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">إلغاء</a>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <table class="w-full text-sm text-right">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="px-4 py-3">العنوان</th>
                    <th class="px-4 py-3">النوع</th>
                    <th class="px-4 py-3">المجموعات</th>
                    <th class="px-4 py-3">الحالة</th>
                    <th class="px-4 py-3">الكمية</th>
                    <th class="px-4 py-3">المحجوز</th>
                    <th class="px-4 py-3">المتبقي</th>
                    <th class="px-4 py-3">تسليم</th>
                    <th class="px-4 py-3">إجراءات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($reservations as $reservation)
                    <tr>
                        <td class="px-4 py-3 font-medium text-gray-900">{{ $reservation->title }}</td>
                        <td class="px-4 py-3">{{ $reservation->typeLabel() }}</td>
                        <td class="px-4 py-3">
                            @foreach($reservation->targetGroups as $group)
                                <span class="inline-block bg-gray-100 text-gray-700 px-2 py-0.5 rounded text-xs mb-1">{{ $group->title }}</span>
                            @endforeach
                        </td>
                        <td class="px-4 py-3">
                            @if ($reservation->status === 'draft')
                                <span class="text-gray-500">مسودة</span>
                            @elseif ($reservation->status === 'open')
                                <span class="text-green-700">مفتوح</span>
                            @elseif ($reservation->status === 'closed')
                                <span class="text-orange-600">مغلق</span>
                            @elseif ($reservation->status === 'archived')
                                <span class="text-blue-600">مؤرشف</span>
                            @else
                                <span class="text-red-600">محذوف</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">{{ $reservation->quantityDisplay() }}</td>
                        <td class="px-4 py-3">{{ $reservation->reserved_count }}</td>
                        <td class="px-4 py-3">
                            @if($reservation->isUnlimited())
                                <span class="text-gray-500">—</span>
                            @else
                                <span class="@if($reservation->remaining() <= 0) text-red-600 @elseif($reservation->remaining() <= 5) text-orange-600 @else text-green-700 @endif">{{ $reservation->remaining() }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if($reservation->reserved_count > 0)
                                <span class="text-green-700">{{ $reservation->delivered_count ?? $reservation->deliveredCount() }} تم التسليم</span>
                                <span class="text-gray-500">/ {{ $reservation->not_delivered_count ?? $reservation->notDeliveredCount() }} انتظار</span>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap gap-2">
                                <a href="{{ route('assistant.reservations.show', $reservation) }}" class="text-blue-600 hover:underline">عرض</a>
                                @if(! $reservation->isArchived() && ! $reservation->trashed())
                                    <a href="{{ route('assistant.reservations.edit', $reservation) }}" class="text-blue-600 hover:underline">تعديل</a>
                                @endif
                                @if(! $reservation->isArchived() && ! $reservation->trashed() && $reservation->status !== 'closed')
                                    <form method="POST" action="{{ route('assistant.reservations.cancel', $reservation) }}" class="hidden" id="cancelResv{{ $reservation->id }}">
                                        @csrf
                                    </form>
                                    <button type="button" class="text-red-600 hover:underline" data-bs-toggle="modal" data-bs-target="#cancelResvModal{{ $reservation->id }}">إلغاء</button>
                                    <x-confirm-modal
                                        :id="'cancelResvModal' . $reservation->id"
                                        title="إلغاء الحجز"
                                        body="سيتم إغلاق الحجز ولن يتمكن الطلاب من الحجز بعد الآن."
                                        confirm-text="إلغاء الحجز"
                                        cancel-text="تراجع"
                                        :form-id="'cancelResv' . $reservation->id"
                                    />
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-4 py-8">
                            <x-empty-state icon="bi-calendar-event" title="لا توجد حجوزات بعد" message="افتح أول حجز لطلابك." :action="'<a href=\''.route('assistant.reservations.create').'\' class=\'px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition text-sm font-medium\'><i class=\'bi bi-plus-lg\'></i> حجز جديد</a>'" />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $reservations->links() }}
</x-dashboard-layout>