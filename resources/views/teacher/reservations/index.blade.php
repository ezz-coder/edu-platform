<x-dashboard-layout title="الحجوزات">
    <x-page-header
        title="الحجوزات"
        description="كل الحجوزات التي فتحتها لطلابك"
        :action="'<a href=\''.route('teacher.reservations.create').'\' class=\'px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition text-sm font-medium\'><i class=\'bi bi-plus-lg\'></i> حجز جديد</a>'"
    />

    @if (session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-2 rounded-lg text-sm">{{ session('success') }}</div>
    @endif

    <div class="mb-4">
        <a href="{{ route('teacher.reservations.recycle-bin') }}" class="text-gray-600 hover:underline">سلة المحذوفات - الحجوزات</a>
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
                                <a href="{{ route('teacher.reservations.show', $reservation) }}" class="text-blue-600 hover:underline">عرض</a>
                                @if(! $reservation->isArchived() && ! $reservation->trashed())
                                    <a href="{{ route('teacher.reservations.edit', $reservation) }}" class="text-blue-600 hover:underline">تعديل</a>
                                @endif
                                @if(! $reservation->isArchived() && ! $reservation->trashed())
                                    <form method="POST" action="{{ route('teacher.reservations.destroy', $reservation) }}" class="hidden" id="delResv{{ $reservation->id }}">
                                        @csrf @method('DELETE')
                                    </form>
                                    <button type="button" class="text-red-600 hover:underline" data-bs-toggle="modal" data-bs-target="#delResvModal{{ $reservation->id }}">حذف</button>
                                    <x-confirm-modal
                                        :id="'delResvModal' . $reservation->id"
                                        title="حذف الحجز"
                                        body="سيتم حذف الحجز وكل الطلبات المرتبطة به."
                                        confirm-text="حذف"
                                        cancel-text="إلغاء"
                                        :form-id="'delResv' . $reservation->id"
                                    />
                                    @if($reservation->status !== 'archived' && ! $reservation->trashed())
                                        <form method="POST" action="{{ route('teacher.reservations.archive', $reservation) }}" class="hidden" id="archiveResv{{ $reservation->id }}">
                                            @csrf
                                        </form>
                                        <button type="button" class="text-blue-600 hover:underline" data-bs-toggle="modal" data-bs-target="#archiveResvModal{{ $reservation->id }}">أرشفة</button>
                                        <x-confirm-modal
                                            :id="'archiveResvModal' . $reservation->id"
                                            title="أرشفة الحجز"
                                            body="سيتم أرشفة الحجز وستصبح مرئياً كتاريخ فقط."
                                            confirm-text="أرشفة"
                                            cancel-text="إلغاء"
                                            :form-id="'archiveResv' . $reservation->id"
                                        />
                                    @endif
                                    @if($reservation->isClosed() && ! $reservation->isArchived() && ! $reservation->trashed())
                                        <form method="POST" action="{{ route('teacher.reservations.reopen', $reservation) }}" class="hidden" id="reopenResv{{ $reservation->id }}">
                                            @csrf
                                        </form>
                                        <button type="button" class="text-green-600 hover:underline" data-bs-toggle="modal" data-bs-target="#reopenResvModal{{ $reservation->id }}">إعادة فتح</button>
                                        <x-confirm-modal
                                            :id="'reopenResvModal' . $reservation->id"
                                            title="إعادة فتح الحجز"
                                            body="سيتم إعادة فتح الحجز ويمكن للطلاب الحجز مرة أخرى."
                                            confirm-text="إعادة فتح"
                                            cancel-text="إلغاء"
                                            :form-id="'reopenResv' . $reservation->id"
                                        />
                                    @endif
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-4 py-8">
                            <x-empty-state icon="bi-calendar-event" title="لا توجد حجوزات بعد" message="افتح أول حجز لطلابك." :action="'<a href=\''.route('teacher.reservations.create').'\' class=\'px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition text-sm font-medium\'><i class=\'bi bi-plus-lg\'></i> حجز جديد</a>'" />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $reservations->links() }}
</x-dashboard-layout>