<x-dashboard-layout title="{{ $exam->title }}">
    <div class="max-w-3xl mx-auto">
        <!-- Timer bar -->
        <div class="sticky top-0 z-20 bg-white border border-gray-200 rounded-lg shadow-sm p-3 mb-4 flex items-center justify-between">
            <div>
                <span class="text-sm text-gray-500">الوقت المتبقي:</span>
                <span id="timer" class="text-lg font-bold text-blue-700 ml-2">--:--</span>
            </div>
            <div class="flex items-center gap-2">
                <form method="POST" action="{{ route('student.exams.leave', $attempt) }}" class="hidden" id="leaveExam{{ $attempt->id }}">
                    @csrf
                </form>
                <button type="button" class="px-3 py-1.5 bg-red-600 text-white rounded-lg hover:bg-red-700 transition text-sm" data-bs-toggle="modal" data-bs-target="#leaveExamModal{{ $attempt->id }}">خروج</button>
                <x-confirm-modal
                    :id="'leaveExamModal' . $attempt->id"
                    title="الخروج من الامتحان"
                    body="هل تريد الخروج؟ سيتم إنهاء المحاولة نهائياً ولا يمكن العودة."
                    confirm-text="خروج"
                    cancel-text="إلغاء"
                    :form-id="'leaveExam' . $attempt->id"
                />
                <button type="button" id="finishExamBtn" class="px-3 py-1.5 bg-green-600 text-white rounded-lg hover:bg-green-700 transition text-sm">تسليم الامتحان</button>
            </div>
        </div>

        <form id="exam-form" method="POST" action="{{ route('student.exams.submit', $attempt) }}" class="space-y-4">
            @csrf
            <div class="bg-white rounded-lg shadow-sm p-4 text-sm text-gray-600 flex items-center justify-between">
                <span>{{ $exam->title }}</span>
                <span id="questionProgress">السؤال <span id="currentQNum">1</span> من {{ $questions->count() }}</span>
            </div>

            @foreach ($questions as $q)
                <div class="question-card {{ $loop->first ? '' : 'hidden' }}" data-question-id="{{ $q->id }}">
                    <div class="bg-white rounded-lg shadow-sm p-5">
                        <p class="font-medium text-gray-900 mb-4">{{ $q->question }}</p>
                        <div class="space-y-2">
                            @foreach (['a' => $q->option_a, 'b' => $q->option_b, 'c' => $q->option_c, 'd' => $q->option_d] as $key => $text)
                                <label class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 hover:bg-gray-50 cursor-pointer">
                                    <input type="radio" name="answers[{{ $q->id }}]" value="{{ $key }}" data-question="{{ $q->id }}" class="w-4 h-4 text-blue-600 answer-input" {{ ($answers[$q->id] ?? null) === $key ? 'checked' : '' }}>
                                    <span class="text-sm text-gray-800">{{ $text }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach

            <div class="flex items-center justify-between pt-2">
                <button type="button" id="prevBtn" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 transition text-sm" disabled>السابق</button>
                <button type="button" id="nextBtn" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm">التالي</button>
            </div>
        </form>
    </div>

    <!-- Finish confirmation modal -->
    <div class="modal fade" id="finishExamModal{{ $attempt->id }}" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">تسليم الامتحان</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>هل أنت متأكد من تسليم الامتحان؟ لا يمكنك العودة لتعديل الإجابات بعد التسليم.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg" data-bs-dismiss="modal">إلغاء</button>
                    <button type="button" id="confirmFinish" class="px-4 py-2 bg-green-600 text-white rounded-lg">تسليم</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const questions = document.querySelectorAll('.question-card');
        let currentIndex = 0;

        function showQuestion(index) {
            questions.forEach((q, i) => {
                q.classList.toggle('hidden', i !== index);
            });
            document.getElementById('currentQNum').textContent = index + 1;
            document.getElementById('prevBtn').disabled = index === 0;
            document.getElementById('nextBtn').textContent = index === questions.length - 1 ? 'إنهاء' : 'التالي';
        }

        document.getElementById('prevBtn').addEventListener('click', () => {
            if (currentIndex > 0) {
                currentIndex--;
                showQuestion(currentIndex);
            }
        });

        document.getElementById('nextBtn').addEventListener('click', () => {
            if (currentIndex < questions.length - 1) {
                currentIndex++;
                showQuestion(currentIndex);
            } else {
                openFinishModal();
            }
        });

        function openFinishModal() {
            const modal = new bootstrap.Modal(document.getElementById('finishExamModal{{ $attempt->id }}'));
            modal.show();
        }

        document.getElementById('finishExamBtn').addEventListener('click', openFinishModal);
        document.getElementById('confirmFinish').addEventListener('click', () => {
            document.getElementById('exam-form').submit();
        });

        // Auto-save on answer change
        document.querySelectorAll('.answer-input').forEach(input => {
            input.addEventListener('change', () => {
                const questionId = input.dataset.question;
                const answer = input.value;
                // Simple auto-save via fetch
                fetch("{{ route('student.exams.autoSave', $attempt) }}", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        question_id: questionId,
                        answer: answer
                    })
                });
            });
        });

        // Timer
        const deadline = new Date("{{ $attempt->deadline()->toIso8601String() }}").getTime();
        const timerEl = document.getElementById('timer');

        function tick() {
            const now = Date.now();
            let diff = Math.floor((deadline - now) / 1000);
            if (diff <= 0) {
                timerEl.textContent = '00:00';
                document.getElementById('exam-form').submit();
                return;
            }
            const m = String(Math.floor(diff / 60)).padStart(2, '0');
            const s = String(diff % 60).padStart(2, '0');
            timerEl.textContent = m + ':' + s;
            setTimeout(tick, 1000);
        }
        tick();

        // Anti-cheat
        document.addEventListener('contextmenu', e => e.preventDefault());
        document.addEventListener('copy', e => e.preventDefault());
        document.addEventListener('cut', e => e.preventDefault());
        document.addEventListener('selectstart', e => e.preventDefault());
        window.addEventListener('beforeunload', function (e) {
            e.preventDefault();
            e.returnValue = '';
        });
    </script>
</x-dashboard-layout>