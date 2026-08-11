<x-dashboard-layout title="تعديل سؤال">
    <x-page-header
        title="تعديل السؤال"
        description="تعديل سؤال في بنك الأسئلة"
    />

    @if (session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-2 rounded-lg text-sm mb-4">{{ session('success') }}</div>
    @endif

    <form method="POST" action="{{ route('assistant.question-bank.update', $question) }}" class="space-y-4">
        @csrf @method('PUT')

        <div>
            <label class="block text-sm font-medium mb-1">العنوان (اختياري)</label>
            <input type="text" name="title" value="{{ old('title', $question->title) }}" class="w-full border rounded px-3 py-2">
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">السؤال</label>
            <textarea name="body" class="w-full border rounded px-3 py-2" required>{{ old('body', $question->body) }}</textarea>
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">النوع</label>
            <select name="type" class="w-full border rounded px-3 py-2" required>
                <option value="mcq" {{ $question->type === 'mcq' ? 'selected' : '' }}>اختيار من متعدد</option>
                <option value="true_false" {{ $question->type === 'true_false' ? 'selected' : '' }}>صح/خطأ</option>
                <option value="fill_blank" {{ $question->type === 'fill_blank' ? 'selected' : '' }}>ملء فراغ</option>
                <option value="short_answer" {{ $question->type === 'short_answer' ? 'selected' : '' }}>إجابة قصيرة</option>
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">الإجابة الصحيحة</label>
            <input type="text" name="correct_answer" value="{{ old('correct_answer', $question->correct_answer) }}" class="w-full border rounded px-3 py-2">
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">التفسير</label>
            <textarea name="explanation" class="w-full border rounded px-3 py-2">{{ old('explanation', $question->explanation) }}</textarea>
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">الصعوبة</label>
            <select name="difficulty" class="w-full border rounded px-3 py-2" required>
                <option value="easy" {{ $question->difficulty === 'easy' ? 'selected' : '' }}>سهل</option>
                <option value="medium" {{ $question->difficulty === 'medium' ? 'selected' : '' }}>متوسط</option>
                <option value="hard" {{ $question->difficulty === 'hard' ? 'selected' : '' }}>صعب</option>
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">الدرجات</label>
            <input type="number" name="points" class="w-full border rounded px-3 py-2" min="1" value="{{ old('points', $question->points) }}" required>
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">الحالة</label>
            <select name="status" class="w-full border rounded px-3 py-2" required>
                <option value="active" {{ $question->status === 'active' ? 'selected' : '' }}>نشط</option>
                <option value="inactive" {{ $question->status === 'inactive' ? 'selected' : '' }}>غير نشط</option>
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">الدرس (اختياري)</label>
            <select name="lesson_id" class="w-full border rounded px-3 py-2">
                <option value="">بدون درس</option>
                @foreach ($lessons as $lesson)
                    <option value="{{ $lesson->id }}" {{ $question->lesson_id === $lesson->id ? 'selected' : '' }}>{{ $lesson->title }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">المجموعة (اختياري)</label>
            <select name="group_id" class="w-full border rounded px-3 py-2">
                <option value="">بدون مجموعة</option>
                @foreach ($groups as $group)
                    <option value="{{ $group->id }}" {{ $question->group_id === $group->id ? 'selected' : '' }}>{{ $group->title }}</option>
                @endforeach
            </select>
        </div>

        <button class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">حفظ التغييرات</button>
    </form>
</x-dashboard-layout>