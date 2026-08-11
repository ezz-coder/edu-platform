<x-dashboard-layout title="نتيجة الطالب">
    <div class="max-w-3xl mx-auto space-y-4">
        <div class="flex items-center justify-between">
            <h1 class="text-lg font-bold text-gray-900">نتيجة الطالب</h1>
            <a href="{{ route('teacher.exams.attempts', $exam) }}" class="text-sm text-blue-600 hover:underline">العودة للقائمة</a>
        </div>

        <!-- Student info -->
        <div class="bg-white rounded-lg shadow-sm p-5 grid grid-cols-2 md:grid-cols-3 gap-3 text-sm">
            <div><span class="text-gray-500">الاسم:</span> <span class="font-medium text-gray-900">{{ $attempt->student->name }}</span></div>
            <div><span class="text-gray-500">كود الطالب:</span> <span class="font-medium text-gray-900">{{ $attempt->student->student_code ?? '—' }}</span></div>
            <div><span class="text-gray-500">المدرّس:</span> <span class="font-medium text-gray-900">{{ $exam->teacher->name }}</span></div>
            <div><span class="text-gray-500">المجموعة:</span> <span class="font-medium text-gray-900">{{ $enrollment?->group->title ?? '—' }}</span></div>
            <div><span class="text-gray-500">بدأ:</span> <span class="font-medium text-gray-900">{{ $attempt->started_at?->format('Y-m-d H:i') ?? '—' }}</span></div>
            <div><span class="text-gray-500">انتهى:</span> <span class="font-medium text-gray-900">{{ $attempt->finished_at?->format('Y-m-d H:i') ?? '—' }}</span></div>
            <div><span class="text-gray-500">المدة:</span> <span class="font-medium text-gray-900">{{ $attempt->started_at && $attempt->finished_at ? $attempt->started_at->diffInMinutes($attempt->finished_at) . ' د' : '—' }}</span></div>
            <div><span class="text-gray-500">الدرجة:</span> <span class="font-medium text-blue-700">{{ $attempt->score }}/{{ $attempt->total_marks }}</span></div>
            <div>
                <span class="text-gray-500">الحالة:</span>
                <span class="font-medium">
                    @if ($attempt->status === 'finished') <span class="text-green-600">أكمل</span>
                    @elseif ($attempt->status === 'running') <span class="text-amber-600">قيد الأداء</span>
                    @elseif ($attempt->status === 'cancelled') <span class="text-red-600">ملغاة</span>
                    @else <span class="text-gray-400">لم يبدأ</span>
                    @endif
                </span>
            </div>
        </div>

        <!-- Questions + answers -->
        <div class="space-y-3">
            @foreach ($questions as $q)
                @php
                    $ans = $attempt->answers->firstWhere('question_id', $q->id);
                    $selected = $ans?->selected_answer;
                    $correct = $q->correct_answer;
                @endphp
                <div class="bg-white rounded-lg shadow-sm p-4">
                    <p class="font-medium text-gray-900 mb-2">{{ $loop->iteration }}. {{ $q->question }} <span class="text-xs text-gray-400">({{ $q->mark }} درجة)</span></p>
                    <div class="grid grid-cols-2 gap-2 text-sm">
                        <p class="{{ $correct === 'a' ? 'font-bold text-green-700' : 'text-gray-600' }}">أ. {{ $q->option_a }}</p>
                        <p class="{{ $correct === 'b' ? 'font-bold text-green-700' : 'text-gray-600' }}">ب. {{ $q->option_b }}</p>
                        <p class="{{ $correct === 'c' ? 'font-bold text-green-700' : 'text-gray-600' }}">ج. {{ $q->option_c }}</p>
                        <p class="{{ $correct === 'd' ? 'font-bold text-green-700' : 'text-gray-600' }}">د. {{ $q->option_d }}</p>
                    </div>
                    <div class="mt-3 flex flex-wrap gap-4 text-sm border-t border-gray-100 pt-2">
                        <span class="text-gray-600">إجابة الطالب: <span class="font-semibold">{{ $selected ? strtoupper($selected) : '—' }}</span></span>
                        <span class="text-gray-600">الإجابة الصحيحة: <span class="font-semibold text-green-700">{{ strtoupper($correct) }}</span></span>
                        <span class="text-gray-600">الدرجة: <span class="font-semibold">{{ $ans?->mark ?? 0 }}</span></span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-dashboard-layout>