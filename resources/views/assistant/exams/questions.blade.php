<x-dashboard-layout title="الأسئلة - {{ $exam->title }}">
    <x-page-header
        title="الأسئلة"
        description="إدارة أسئلة الامتحان: {{ $exam->title }}"
    />

    @if (session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-2 rounded-lg text-sm mb-4">{{ session('success') }}</div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Add Question Form -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-semibold mb-4">إضافة سؤال جديد</h3>
            <form method="POST" action="{{ route('assistant.exams.questions.add', $exam) }}" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-sm font-medium mb-1">السؤال</label>
                    <textarea name="question" class="w-full border rounded px-3 py-2" required></textarea>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div><label class="block text-sm font-medium mb-1">الخيار أ</label><input type="text" name="option_a" class="w-full border rounded px-2 py-1" required></div>
                    <div><label class="block text-sm font-medium mb-1">الخيار ب</label><input type="text" name="option_b" class="w-full border rounded px-2 py-1" required></div>
                    <div><label class="block text-sm font-medium mb-1">الخيار ج</label><input type="text" name="option_c" class="w-full border rounded px-2 py-1" required></div>
                    <div><label class="block text-sm font-medium mb-1">الخيار د</label><input type="text" name="option_d" class="w-full border rounded px-2 py-1" required></div>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">الإجابة الصحيحة</label>
                    <select name="correct_answer" class="w-full border rounded px-2 py-1" required>
                        <option value="a">أ</option>
                        <option value="b">ب</option>
                        <option value="c">ج</option>
                        <option value="d">د</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">الدرجة</label>
                    <input type="number" name="mark" class="w-full border rounded px-2 py-1" min="1" value="1" required>
                </div>
                <button class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">إضافة السؤال</button>
            </form>

            <hr class="my-4">

            <h3 class="text-lg font-semibold mb-2">اختيار من بنك الأسئلة</h3>
            <a href="{{ route('assistant.exams.questions.pick', $exam) }}" class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700 inline-block">بنك الأسئلة</a>
        </div>

        <!-- Questions List -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-semibold mb-4">قائمة الأسئلة ({{ $questions->count() }})</h3>
            @if ($questions->isEmpty())
                <p class="text-gray-500">لا توجد أسئلة بعد. أضف سؤالاً أولاً.</p>
            @else
                <ol class="space-y-2">
                    @foreach ($questions as $question)
                        <li class="border rounded p-3">
                            <div class="flex justify-between items-start">
                                <div>
                                    <span class="font-medium">{{ $question->question }}</span>
                                    <span class="text-xs bg-gray-100 px-2 py-1 rounded mr-2">{{ $question->mark }} درجة</span>
                                </div>
                                <form method="POST" action="{{ route('assistant.exams.questions.delete', [$exam, $question]) }}">
                                    @csrf @method('DELETE')
                                    <button class="text-red-600 text-sm hover:underline">حذف</button>
                                </form>
                            </div>
                        </li>
                    @endforeach
                </ol>
            @endif
        </div>
    </div>
</x-dashboard-layout>