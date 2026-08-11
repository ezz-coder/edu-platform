<x-dashboard-layout title="اختيار سؤال من البنك">
    <x-page-header
        title="اختيار سؤال من بنك الأسئلة"
        description="إرفاق سؤال بنك الأسئلة إلى الامتحان: {{ $exam->title }}"
    />

    @if (session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-2 rounded-lg text-sm mb-4">{{ session('success') }}</div>
    @endif

    <form method="GET" class="mb-4 flex flex-wrap gap-2 items-end">
        <div>
            <label class="block text-xs mb-1">النوع</label>
            <select name="type" class="border rounded px-2 py-1">
                <option value="">الكل</option>
                <option value="mcq" {{ ($filters['type'] ?? '') === 'mcq' ? 'selected' : '' }}>اختيار من متعدد</option>
                <option value="true_false" {{ ($filters['type'] ?? '') === 'true_false' ? 'selected' : '' }}>صح/خطأ</option>
                <option value="fill_blank" {{ ($filters['type'] ?? '') === 'fill_blank' ? 'selected' : '' }}>ملء فراغ</option>
                <option value="short_answer" {{ ($filters['type'] ?? '') === 'short_answer' ? 'selected' : '' }}>إجابة قصيرة</option>
            </select>
        </div>
        <div>
            <label class="block text-xs mb-1">الصعوبة</label>
            <select name="difficulty" class="border rounded px-2 py-1">
                <option value="">الكل</option>
                <option value="easy" {{ ($filters['difficulty'] ?? '') === 'easy' ? 'selected' : '' }}>سهل</option>
                <option value="medium" {{ ($filters['difficulty'] ?? '') === 'medium' ? 'selected' : '' }}>متوسط</option>
                <option value="hard" {{ ($filters['difficulty'] ?? '') === 'hard' ? 'selected' : '' }}>صعب</option>
            </select>
        </div>
        <div>
            <label class="block text-xs mb-1">الدرس</label>
            <select name="lesson_id" class="border rounded px-2 py-1">
                <option value="">الكل</option>
                @foreach ($lessons as $lesson)
                    <option value="{{ $lesson->id }}" {{ ($filters['lesson_id'] ?? '') == $lesson->id ? 'selected' : '' }}>{{ $lesson->title }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs mb-1">المجموعة</label>
            <select name="group_id" class="border rounded px-2 py-1">
                <option value="">الكل</option>
                @foreach ($groups as $group)
                    <option value="{{ $group->id }}" {{ ($filters['group_id'] ?? '') == $group->id ? 'selected' : '' }}>{{ $group->title }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="بحث..." class="border rounded px-2 py-1">
        </div>
        <button class="px-3 py-1 bg-blue-600 text-white rounded">بحث</button>
        <a href="{{ route('assistant.exams.questions.pick', $exam) }}" class="px-3 py-1 bg-gray-300 rounded">مسح</a>
    </form>

    <div class="bg-white rounded-lg shadow-sm p-4">
        @if ($questions->isEmpty())
            <p class="text-gray-500">لا توجد أسئلة متاحة.</p>
        @else
            <form method="POST" action="{{ route('assistant.exams.questions.attach', $exam) }}">
                @csrf
                <div class="space-y-3">
                    @foreach ($questions as $question)
                        <div class="border rounded p-3">
                            <label class="flex items-start cursor-pointer">
                                <input type="radio" name="question_id" value="{{ $question->id }}" class="mt-1 ml-3" required>
                                <div>
                                    <p class="font-medium">{{ $question->body ?? $question->title }}</p>
                                    <p class="text-xs text-gray-500 mt-1">
                                        النوع: {{ match($question->type) { 'mcq' => 'اختيار من متعدد', 'true_false' => 'صح/خطأ', 'fill_blank' => 'ملء فراغ', 'short_answer' => 'إجابة قصيرة', default => '' } }}
                                        | الدرجات: {{ $question->points }}
                                    </p>
                                </div>
                            </label>
                        </div>
                    @endforeach
                </div>
                <div class="mt-4">
                    <label class="block text-sm font-medium mb-1">الدرجات (اختياري - افتراضي من البنك)</label>
                    <input type="number" name="points" class="border rounded px-2 py-1 w-32" min="1">
                </div>
                <button class="mt-4 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">إرفاق السؤال</button>
            </form>
        @endif
    </div>

    {{ $questions->links() }}
</x-dashboard-layout>