<x-dashboard-layout title="{{ $header }}">
    <div class="max-w-2xl mx-auto">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-lg font-bold text-gray-900">{{ $header }}</h1>
            <a href="{{ route('teacher.question-bank.index') }}" class="text-sm text-gray-600 hover:underline">العودة للقائمة</a>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6">
            <form method="POST" action="{{ route('teacher.question-bank.update', $question) }}" class="space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">العنوان (اختياري)</label>
                    <input type="text" name="title" value="{{ old('title', $question->title) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2" placeholder="عنوان مختصر للسؤال">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">نص السؤال *</label>
                    <textarea name="body" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2" required>{{ old('body', $question->body) }}</textarea>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">نوع السؤال *</label>
                        <select name="type" id="questionType" class="w-full border border-gray-300 rounded-lg px-3 py-2" required>
                            <option value="mcq" {{ old('type', $question->type) === 'mcq' ? 'selected' : '' }}>اختيار من متعدد</option>
                            <option value="true_false" {{ old('type', $question->type) === 'true_false' ? 'selected' : '' }}>صح / خطأ</option>
                            <option value="fill_blank" {{ old('type', $question->type) === 'fill_blank' ? 'selected' : '' }}>املأ الفراغ</option>
                            <option value="short_answer" {{ old('type', $question->type) === 'short_answer' ? 'selected' : '' }}>إجابة قصيرة</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">الصعوبة *</label>
                        <select name="difficulty" class="w-full border border-gray-300 rounded-lg px-3 py-2" required>
                            <option value="easy" {{ old('difficulty', $question->difficulty) === 'easy' ? 'selected' : '' }}>سهل</option>
                            <option value="medium" {{ old('difficulty', $question->difficulty) === 'medium' ? 'selected' : '' }}>متوسط</option>
                            <option value="hard" {{ old('difficulty', $question->difficulty) === 'hard' ? 'selected' : '' }}>صعب</option>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">الإجابة الصحيحة *</label>
                        <input type="text" name="correct_answer" value="{{ old('correct_answer', $question->correct_answer) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">الدرجة *</label>
                        <input type="number" name="points" min="1" value="{{ old('points', $question->points) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2" required>
                    </div>
                </div>
                <div id="mcqOptions" class="grid grid-cols-2 gap-3">
                    <div><label class="block text-sm text-gray-600 mb-1">أ</label><input type="text" name="options[a]" value="{{ old('options.a', $question->options['a'] ?? '') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2"></div>
                    <div><label class="block text-sm text-gray-600 mb-1">ب</label><input type="text" name="options[b]" value="{{ old('options.b', $question->options['b'] ?? '') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2"></div>
                    <div><label class="block text-sm text-gray-600 mb-1">ج</label><input type="text" name="options[c]" value="{{ old('options.c', $question->options['c'] ?? '') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2"></div>
                    <div><label class="block text-sm text-gray-600 mb-1">د</label><input type="text" name="options[d]" value="{{ old('options.d', $question->options['d'] ?? '') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2"></div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">الشرح (اختياري)</label>
                    <textarea name="explanation" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2">{{ old('explanation', $question->explanation) }}</textarea>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">الدرس (اختياري)</label>
                        <select name="lesson_id" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                            <option value="">بدون درس</option>
                            @foreach($lessons as $lesson)
                                <option value="{{ $lesson->id }}" {{ old('lesson_id', $question->lesson_id) == $lesson->id ? 'selected' : '' }}>{{ $lesson->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">المجموعة (اختياري)</label>
                        <select name="group_id" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                            <option value="">بدون مجموعة</option>
                            @foreach($groups as $group)
                                <option value="{{ $group->id }}" {{ old('group_id', $question->group_id) == $group->id ? 'selected' : '' }}>{{ $group->title }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">الحالة *</label>
                    <select name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2" required>
                        <option value="active" {{ old('status', $question->status) === 'active' ? 'selected' : '' }}>نشط</option>
                        <option value="inactive" {{ old('status', $question->status) === 'inactive' ? 'selected' : '' }}>غير نشط</option>
                    </select>
                </div>
                <div class="flex items-center gap-3 pt-2">
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">تحديث</button>
                    <a href="{{ route('teacher.question-bank.index') }}" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 transition">إلغاء</a>
                </div>
            </form>
        </div>
    </div>
</x-dashboard-layout>