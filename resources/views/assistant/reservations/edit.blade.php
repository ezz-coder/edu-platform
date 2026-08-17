<x-dashboard-layout title="تعديل حجز">
    <div class="max-w-2xl mx-auto">
        <h1 class="text-lg font-bold text-gray-900 mb-4">تعديل حجز</h1>

        <form method="POST" action="{{ route('assistant.reservations.update', $reservation) }}" class="bg-white rounded-lg shadow-sm p-6 space-y-4">
            @csrf @method('PUT')

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">العنوان</label>
                <input type="text" name="title" value="{{ old('title', $reservation->title) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2" required>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">الوصف</label>
                <textarea name="description" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2">{{ old('description', $reservation->description) }}</textarea>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">نوع الحجز</label>
                <select name="type" class="w-full border border-gray-300 rounded-lg px-3 py-2" required>
                    @foreach($types as $value => $label)
                        <option value="{{ $value }}" {{ old('type', $reservation->type) === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">الكمية (اختياري - إذا تُركت فاراغ، الحجز غير محدود)</label>
                <input type="number" name="quantity" min="1" value="{{ old('quantity', $reservation->quantity > 0 ? $reservation->quantity : '') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2" placeholder="غير محدود">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">تاريخ الفتح</label>
                    <input type="date" name="open_date" value="{{ old('open_date', $reservation->open_date?->format('Y-m-d')) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">تاريخ الإغلاق</label>
                    <input type="date" name="close_date" value="{{ old('close_date', $reservation->close_date?->format('Y-m-d')) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">الحالة</label>
                <select name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2" required>
                    <option value="draft" {{ old('status', $reservation->status) === 'draft' ? 'selected' : '' }}>مسودة</option>
                    <option value="open" {{ old('status', $reservation->status) === 'open' ? 'selected' : '' }}>مفتوح</option>
                    <option value="closed" {{ old('status', $reservation->status) === 'closed' ? 'selected' : '' }}>مغلق</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">المجموعات المستهدفة</label>
                <select name="group_ids[]" multiple class="w-full border border-gray-300 rounded-lg px-3 py-2" size="4" required>
                    @foreach ($groups as $g)
                        <option value="{{ $g->id }}" {{ $reservation->targetGroups->contains('id', $g->id) ? 'selected' : '' }}>{{ $g->title }} ({{ $g->grade_level }})</option>
                    @endforeach
                </select>
            </div>

            <div class="flex gap-2">
                <button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition">حفظ</button>
                <a href="{{ route('assistant.reservations.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">إلغاء</a>
            </div>
        </form>
    </div>
</x-dashboard-layout>