<x-dashboard-layout title="{{ $header }}">
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <h1 class="text-lg font-bold text-gray-900">{{ $header }}</h1>
            <a href="{{ route('teacher.exams.questions', $exam) }}" class="text-sm text-gray-600 hover:underline">العودة للامتحان</a>
        </div>

        @if (session('success'))
            <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-2 rounded-lg text-sm">{{ session('success') }}</div>
        @endif

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow-sm p-4">
            <form method="GET" action="{{ route('teacher.exams.questions.pick', $exam) }}" class="grid grid-cols-1 md:grid-cols-5 gap-3">
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="بحث..." class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                <select name="type" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <option value="">كل الأنواع</option>
                    <option value="mcq" {{ ($filters['type'] ?? '') === 'mcq' ? 'selected' : '' }}>اختيار من متعدد</option>
                    <option value="true_false" {{ ($filters['type'] ?? '') === 'true_false' ? 'selected' : '' }}>صح / خطأ</option>
                    <option value="fill_blank" {{ ($filters['type'] ?? '') === 'fill_blank' ? 'selected' : '' }}>املأ الفراغ</option>
                    <option value="short_answer" {{ ($filters['type'] ?? '') === 'short_answer' ? 'selected' : '' }}>إجابة قصيرة</option>
                </select>
                <select name="difficulty" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <option value="">كل الصعوبات</option>
                    <option value="easy" {{ ($filters['difficulty'] ?? '') === 'easy' ? 'selected' : '' }}>سهل</option>
                    <option value="medium" {{ ($filters['difficulty'] ?? '') === 'medium' ? 'selected' : '' }}>متوسط</option>
                    <option value="hard" {{ ($filters['difficulty'] ?? '') === 'hard' ? 'selected' : '' }}>صعب</option>
                </select>
                <select name="lesson_id" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <option value="">كل الدروس</option>
                    @foreach($lessons as $lesson)
                        <option value="{{ $lesson->id }}" {{ ($filters['lesson_id'] ?? '') == $lesson->id ? 'selected' : '' }}>{{ $lesson->title }}</option>
                    @endforeach
                </select>
                <button type="submit" class="px-4 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-900 transition text-sm">تصفية</button>
            </form>
        </div>

        <!-- Questions list -->
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <div class="divide-y divide-gray-100">
                @forelse ($questions as $q)
                    <div class="p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="text-sm text-gray-800 flex-1">
                                @if($q->title)
                                    <p class="font-semibold mb-1">{{ $q->title }}</p>
                                @endif
                                <p>{{ $q->body }}</p>
                                <div class="flex items-center gap-3 mt-2 text-xs text-gray-500">
                                    <span class="px-2 py-0.5 bg-gray-100 rounded">{{ $q->points }} درجة</span>
                                    <span class="px-2 py-0.5 bg-gray-100 rounded">{{ $q->difficulty }}</span>
                                    <span class="px-2 py-0.5 bg-gray-100 rounded">{{ $q->type }}</span>
                                    @if($q->lesson)
                                        <span class="px-2 py-0.5 bg-blue-50 text-blue-700 rounded">{{ $q->lesson->title }}</span>
                                    @endif
                                    @if($q->group)
                                        <span class="px-2 py-0.5 bg-green-50 text-green-700 rounded">{{ $q->group->title }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <form method="POST" action="{{ route('teacher.exams.questions.attach', $exam) }}" class="inline">
                                    @csrf
                                    <input type="hidden" name="question_id" value="{{ $q->id }}">
                                    <button type="submit" class="px-3 py-1.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm">إضافة للامتحان</button>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="p-4 text-center text-gray-500">لا توجد أسئلة مطابقة للتصفية.</p>
                @endforelse
            </div>
        </div>

        <div class="mt-4">
            {{ $questions->links() }}
        </div>
    </div>
</x-dashboard-layout>