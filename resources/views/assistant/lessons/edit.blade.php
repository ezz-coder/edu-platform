<x-dashboard-layout title="تعديل درس">
    <div class="max-w-2xl mx-auto">
        <h1 class="text-lg font-bold text-gray-900 mb-4">تعديل درس</h1>

        <form method="POST" action="{{ route('assistant.lessons.update', $lesson) }}" class="bg-white rounded-lg shadow-sm p-6 space-y-4">
            @csrf @method('PUT')

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">المجموعة</label>
                <select name="group_id" class="w-full border border-gray-300 rounded-lg px-3 py-2" required>
                    @foreach ($groups as $g)
                        <option value="{{ $g->id }}" {{ $lesson->group_id == $g->id ? 'selected' : '' }}>{{ $g->title }} ({{ $g->grade_level }})</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">عنوان الدرس</label>
                <input type="text" name="title" value="{{ old('title', $lesson->title) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2" required>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">الوصف (اختياري)</label>
                <textarea name="description" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2">{{ old('description', $lesson->description) }}</textarea>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">الترتيب</label>
                <input type="number" name="display_order" value="{{ old('display_order', $lesson->display_order) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2" min="0">
            </div>

            <div>
                <label class="flex items-center gap-2 text-sm font-medium text-gray-700">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $lesson->is_active) ? 'checked' : '' }}> فعال
                </label>
            </div>

            <div class="flex gap-2">
                <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">حفظ</button>
                <a href="{{ route('assistant.lessons.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">إلغاء</a>
            </div>
        </form>
    </div>
</x-dashboard-layout>