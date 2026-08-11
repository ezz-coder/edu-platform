<x-dashboard-layout title="{{ $header }}">
    <div class="max-w-2xl mx-auto">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-lg font-bold text-gray-900">{{ $header }}</h1>
            <a href="{{ route('teacher.question-bank.index') }}" class="text-sm text-gray-600 hover:underline">العودة للقائمة</a>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6">
            <form method="POST" action="{{ route('teacher.question-bank.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">العنوان (اختياري)</label>
                    <input type="text" name="title" value="{{ old('title') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2" placeholder="عنوان مختصر للسؤال">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">نص السؤال *</label>
                    <textarea name="body" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2" required>{{ old('body') }}</textarea>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">نوع السؤال *</label>
                        <select name="type" id="questionType" class="w-full border border-gray-300 rounded-lg px-3 py-2" required>
                            <option value="mcq">اختيار من متعدد</option>
                            <option value="true_false">صح / خطأ</option>
                            <option value="fill_blank">املأ الفراغ</option>
                            <option value="short_answer">إجابة قصيرة</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">الصعوبة *</label>
                        <select name="difficulty" class="w-full border border-gray-300 rounded-lg px-3 py-2" required>
                            <option value="easy">سهل</option>
                            <option value="medium" selected>متوسط</option>
                            <option value="hard">صعب</option>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">الإجابة الصحيحة *</label>
                        <input type="text" name="correct_answer" value="{{ old('correct_answer') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">الدرجة *</label>
                        <input type="number" name="points" min="1" value="{{ old('points', 1) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2" required>
                    </div>
                </div>
                <div id="mcqOptions" class="grid grid-cols-2 gap-3">
                    <div><label class="block text-sm text-gray-600 mb-1">أ</label><input type="text" name="options[a]" class="w-full border border-gray-300 rounded-lg px-3 py-2"></div>
                    <div><label class="block text-sm text-gray-600 mb-1">ب</label><input type="text" name="options[b]" class="w-full border border-gray-300 rounded-lg px-3 py-2"></div>
                    <div><label class="block text-sm text-gray-600 mb-1">ج</label><input type="text" name="options[c]" class="w-full border border-gray-300 rounded-lg px-3 py-2"></div>
                    <div><label class="block text-sm text-gray-600 mb-1">د</label><input type="text" name="options[d]" class="w-full border border-gray-300 rounded-lg px-3 py-2"></div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">الشرح (اختياري)</label>
                    <textarea name="explanation" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2">{{ old('explanation') }}</textarea>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">الدرس (اختياري)</label>
                        <select name="lesson_id" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                            <option value="">بدون درس</option>
                            @foreach($lessons as $lesson)
                                <option value="{{ $lesson->id }}" {{ old('lesson_id') == $lesson->id ? 'selected' : '' }}>{{ $lesson->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">المجموعة (اختياري)</label>
                        <select name="group_id" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                            <option value="">بدون مجموعة</option>
                            @foreach($groups as $group)
                                <option value="{{ $group->id }}" {{ old('group_id') == $group->id ? 'selected' : '' }}>{{ $group->title }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="flex items-center gap-3 pt-2">
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">حفظ</button>
                    <a href="{{ route('teacher.question-bank.index') }}" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 transition">إلغاء</a>
                </div>
            </form>
        </div>
    </div>
</x-dashboard-layout>