<x-dashboard-layout title="تعديل امتحان">
    <div class="max-w-2xl mx-auto">
        <h1 class="text-lg font-bold text-gray-900 mb-4">تعديل امتحان</h1>

        <form method="POST" action="{{ route('teacher.exams.update', $exam) }}" class="bg-white rounded-lg shadow-sm p-6 space-y-4">
            @csrf @method('PUT')

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">العنوان</label>
                <input type="text" name="title" value="{{ old('title', $exam->title) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2" required>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">الوصف</label>
                <textarea name="description" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2">{{ old('description', $exam->description) }}</textarea>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">المدة بالدقائق</label>
                <input type="number" name="duration_minutes" min="1" value="{{ old('duration_minutes', $exam->duration_minutes) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2" required>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">وقت البداية</label>
                    <input type="datetime-local" name="starts_at" value="{{ old('starts_at', $exam->starts_at?->format('Y-m-d\TH:i')) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">وقت النهاية</label>
                    <input type="datetime-local" name="ends_at" value="{{ old('ends_at', $exam->ends_at?->format('Y-m-d\TH:i')) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">الحالة</label>
                <select name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2" required>
                    <option value="visible" {{ old('status', $exam->status) === 'visible' ? 'selected' : '' }}>منشور</option>
                    <option value="hidden" {{ old('status', $exam->status) === 'hidden' ? 'selected' : '' }}>مخفي</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">المجموعات المستهدفة</label>
                <select name="group_ids[]" multiple class="w-full border border-gray-300 rounded-lg px-3 py-2" size="4" required>
                    @foreach ($groups as $g)
                        <option value="{{ $g->id }}" {{ $exam->targetGroups->contains('id', $g->id) ? 'selected' : '' }}>{{ $g->title }} ({{ $g->grade_level }})</option>
                    @endforeach
                </select>
            </div>

            <div class="flex gap-2">
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">حفظ</button>
                <a href="{{ route('teacher.exams.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">إلغاء</a>
            </div>
        </form>
    </div>
</x-dashboard-layout>