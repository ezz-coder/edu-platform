<x-dashboard-layout title="مراجعة الامتحان">
    <div class="max-w-4xl mx-auto space-y-6">
        <!-- Header -->
        <div class="card p-6">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <div>
                    <h1 class="text-xl font-bold text-text-primary">{{ $exam->title }}</h1>
                    <p class="text-sm text-text-secondary mt-1">مراجعة إجاباتك</p>
                </div>
                <div class="text-left">
                    <p class="text-sm text-text-secondary">درجتك</p>
                    <p class="text-3xl font-bold text-primary">
                        {{ $attempt->score }} / {{ $attempt->total_marks }}
                    </p>
                    <p class="text-xs text-text-secondary mt-1">
                        {{ $attempt->finished_at?->format('Y-m-d H:i') }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Questions -->
        <div class="space-y-4">
            @foreach ($questions as $idx => $q)
                @php
                    $answer = $answers[$q->id] ?? null;
                    $selected = $answer?->selected_answer;
                    $isCorrect = $answer?->is_correct ?? false;
                    $mark = $answer?->mark ?? 0;
                @endphp

                <div class="card p-5">
                    <div class="flex items-start justify-between gap-4 mb-4">
                        <div class="flex items-start gap-3 flex-1 min-w-0">
                            <span class="w-8 h-8 rounded-full bg-bg-secondary flex items-center justify-center font-bold text-sm text-text-primary flex-shrink-0">
                                {{ $idx + 1 }}
                            </span>
                            <div class="flex-1 min-w-0">
                                <p class="font-medium text-text-primary whitespace-pre-wrap">{{ $q->question }}</p>
                                <p class="text-xs text-text-secondary mt-1">{{ $q->mark }} درجة</p>
                            </div>
                        </div>
                        <div class="flex-shrink-0">
                            @if ($isCorrect)
                                <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                    <i class="bi bi-check-circle-fill"></i> صحيح
                                    <span class="ml-1">(+{{ $mark }})</span>
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400">
                                    <i class="bi bi-x-circle-fill"></i> خطأ
                                    <span class="ml-1">(+0)</span>
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="pr-11 space-y-2">
                        @foreach (['a', 'b', 'c', 'd'] as $opt)
                            @php
                                $optionText = $q->{"option_{$opt}"};
                                if (empty($optionText)) continue;

                                $isSelected = $selected === $opt;
                                $isCorrectOption = $q->correct_answer === $opt;

                                $rowClass = '';
                                if ($isCorrectOption) {
                                    $rowClass = 'border-green-500 bg-green-50 dark:bg-green-900/20';
                                } elseif ($isSelected && !$isCorrectOption) {
                                    $rowClass = 'border-red-500 bg-red-50 dark:bg-red-900/20';
                                } else {
                                    $rowClass = 'border-border-subtle bg-bg-secondary/30';
                                }
                            @endphp

                            <div class="p-3 rounded-lg border-2 {{ $rowClass }} flex items-start gap-3">
                                <span class="w-6 h-6 rounded-full border-2 border-current flex items-center justify-center text-xs font-bold flex-shrink-0
                                    @if($isCorrectOption) text-green-600 border-green-500
                                    @elseif($isSelected && !$isCorrectOption) text-red-600 border-red-500
                                    @else text-text-secondary
                                    @endif">
                                    {{ strtoupper($opt) }}
                                </span>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm text-text-primary">{{ $optionText }}</p>
                                    @if ($isSelected)
                                        <p class="text-xs text-text-secondary mt-1 flex items-center gap-1">
                                            <i class="bi bi-person-check"></i> إجابتك
                                        </p>
                                    @endif
                                    @if ($isCorrectOption)
                                        <p class="text-xs text-green-700 dark:text-green-400 mt-1 flex items-center gap-1">
                                            <i class="bi bi-check2-circle"></i> الإجابة الصحيحة
                                        </p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Footer -->
        <div class="flex justify-center">
            <a href="{{ route('student.exams.index') }}" class="btn-primary">
                <i class="bi bi-arrow-left"></i> العودة لقائمة الامتحانات
            </a>
        </div>
    </div>
</x-dashboard-layout>
