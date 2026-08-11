<x-dashboard-layout title="{{ $exam->title }}">
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <h1 class="text-lg font-bold text-gray-900">{{ $exam->title }}</h1>
            <a href="{{ route('teacher.exams.index') }}" class="text-sm text-blue-600 hover:underline">العودة</a>
        </div>

        @if (session('success'))
            <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-2 rounded-lg text-sm">{{ session('success') }}</div>
        @endif

        <div class="flex items-center gap-3">
            <a href="{{ route('teacher.exams.questions.pick', $exam) }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm">اختيار من بنك الأسئلة</a>
            <a href="{{ route('teacher.question-bank.create') }}" class="px-4 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-900 transition text-sm">إنشاء سؤال جديد</a>
        </div>

        <!-- Questions list -->
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <h2 class="text-md font-bold text-gray-900 p-4 border-b">الأسئلة ({{ $questions->count() }})</h2>
            <div class="divide-y divide-gray-100">
                @forelse ($questions as $q)
                    <div class="p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="text-sm text-gray-800">
                                <span class="font-semibold">{{ $q->sort_order + 1 }}.</span> {{ $q->question }}
                                <span class="text-xs text-gray-400 mr-2">({{ $q->mark }} درجة)</span>
                            </div>
                            <form method="POST" action="{{ route('teacher.exams.questions.delete', ['exam' => $exam, 'question' => $q]) }}" class="hidden" id="delQ{{ $q->id }}">
                                @csrf @method('DELETE')
                            </form>
                            <button type="button" class="text-red-600 hover:underline text-sm" data-bs-toggle="modal" data-bs-target="#delQModal{{ $q->id }}">حذف</button>
                            <x-confirm-modal
                                :id="'delQModal' . $q->id"
                                title="حذف السؤال"
                                body="سيتم حذف السؤال نهائياً."
                                confirm-text="حذف"
                                cancel-text="إلغاء"
                                :form-id="'delQ' . $q->id"
                            />
                        </div>
                        <div class="grid grid-cols-2 gap-2 mt-2 text-sm">
                            <p class="{{ $q->correct_answer === 'a' ? 'font-bold text-green-700' : 'text-gray-600' }}">أ. {{ $q->option_a }}</p>
                            <p class="{{ $q->correct_answer === 'b' ? 'font-bold text-green-700' : 'text-gray-600' }}">ب. {{ $q->option_b }}</p>
                            <p class="{{ $q->correct_answer === 'c' ? 'font-bold text-green-700' : 'text-gray-600' }}">ج. {{ $q->option_c }}</p>
                            <p class="{{ $q->correct_answer === 'd' ? 'font-bold text-green-700' : 'text-gray-600' }}">د. {{ $q->option_d }}</p>
                        </div>
                    </div>
                @empty
                    <p class="p-4 text-center text-gray-500">لا توجد أسئلة بعد.</p>
                @endforelse
            </div>
        </div>
    </div>
</x-dashboard-layout>