<x-dashboard-layout title="إضافة درس">
    <div class="max-w-2xl mx-auto">
        <h1 class="text-lg font-bold text-gray-900 mb-4">إضافة درس</h1>

        <form method="POST" action="{{ route('assistant.lessons.store') }}" class="bg-white rounded-lg shadow-sm p-6 space-y-4">
            @csrf

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">المجموعة</label>
                <select name="group_id" class="w-full border border-gray-300 rounded-lg px-3 py-2" required>
                    <option value="">اختر المجموعة</option>
                    @foreach ($groups as $g)
                        <option value="{{ $g->id }}">{{ $g->title }} ({{ $g->grade_level }})</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">عنوان الدرس</label>
                <input type="text" name="title" value="{{ old('title') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2" required>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">الوصف (اختياري)</label>
                <textarea name="description" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2">{{ old('description') }}</textarea>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">الترتيب</label>
                <input type="number" name="display_order" value="{{ old('display_order', 0) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2" min="0">
            </div>

            <div class="flex gap-2">
                <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">حفظ</button>
                <a href="{{ route('assistant.lessons.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">إلغاء</a>
            </div>
        </form>
    </div>
</x-dashboard-layout>