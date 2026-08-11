<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\ExamAnswer;
use App\Models\ExamAttempt;
use App\Models\GroupEnrollment;
use App\Models\Notification;
use App\Models\StudyGroup;
use App\Models\User;
use App\Utilities\NotificationHelper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentExamController extends Controller
{
    /**
     * List exams available for the student's active account.
     */
    public function index(): View
    {
        $account = auth()->user()->activeStudentAccount();
        $group = $this->activeGroup($account);

        $exams = collect();
        if ($group) {
            $exams = Exam::where('teacher_id', $group->teacher_id)
                ->where('status', 'visible')
                ->whereHas('targetGroups', fn ($q) => $q->where('group_id', $group->id))
                ->with(['attempts' => fn ($q) => $q->where('student_id', $account->id)])
                ->latest()
                ->paginate(15);
        }

        return view('student.exams.index', [
            'header' => 'الامتحانات',
            'account' => $account,
            'group' => $group,
            'exams' => $exams,
        ]);
    }

    /**
     * Start or resume the exam. Creates a running attempt once.
     */
    public function start(Exam $exam): RedirectResponse
    {
        $account = auth()->user()->activeStudentAccount();
        $group = $this->activeGroup($account);

        if (! $group || ! $exam->targetGroups()->where('group_id', $group->id)->exists()) {
            abort(403);
        }

        if (! $exam->isOpen()) {
            return back()->with('error', 'الامتحان خارج الوقت المحدد.');
        }

        $attempt = ExamAttempt::firstOrCreate(
            ['exam_id' => $exam->id, 'student_id' => $account->id],
            ['status' => 'running', 'started_at' => now()]
        );

        // If it was finished before, do not allow a new attempt.
        if ($attempt->isFinished()) {
            return redirect()->route('student.exams.result', $attempt)->with('info', 'لقد أديت هذا الامتحان مسبقاً.');
        }

        // If newly created, set started_at.
        if (! $attempt->started_at) {
            $attempt->update(['started_at' => now()]);
        }

        return redirect()->route('student.exams.take', $attempt);
    }

    /**
     * Take the exam (single page, all questions, timer).
     */
    public function take(ExamAttempt $attempt): View
    {
        $this->authorizeAttempt($attempt);

        if ($attempt->isFinished()) {
            return redirect()->route('student.exams.result', $attempt);
        }

        $exam = $attempt->exam;
        $questions = $exam->questions()->orderBy('sort_order')->get();
        $answers = $attempt->answers()->pluck('selected_answer', 'question_id');

        return view('student.exams.take', [
            'header' => $exam->title,
            'attempt' => $attempt,
            'exam' => $exam,
            'questions' => $questions,
            'answers' => $answers,
        ]);
    }

    /**
     * Submit the exam -> auto grade.
     */
    public function submit(Request $request, ExamAttempt $attempt): RedirectResponse
    {
        $this->authorizeAttempt($attempt);

        if ($attempt->isFinished()) {
            return redirect()->route('student.exams.result', $attempt);
        }

        $exam = $attempt->exam;
        $questions = $exam->questions()->get();
        $selections = $request->input('answers', []);

        $score = 0;
        $total = 0;

        foreach ($questions as $q) {
            $total += $q->mark;
            $selected = $selections[$q->id] ?? null;
            $isCorrect = $selected !== null && $selected === $q->correct_answer;
            $mark = $isCorrect ? $q->mark : 0;
            $score += $mark;

            ExamAnswer::updateOrCreate(
                ['attempt_id' => $attempt->id, 'question_id' => $q->id],
                ['selected_answer' => $selected, 'is_correct' => $isCorrect, 'mark' => $mark]
            );
        }

        $attempt->update([
            'finished_at' => now(),
            'score' => $score,
            'total_marks' => $total,
            'status' => 'finished',
        ]);

        // Smart rule: notify the teacher ONLY on the FIRST finished attempt.
        // After that, zero extra notifications per student.
        $teacherId = $attempt->exam->teacher_id;
        $alreadyNotified = Notification::where('teacher_id', $teacherId)
            ->where('type', 'exam')
            ->where('title', 'بدأ استقبال إجابات الامتحان')
            ->where('link', route('teacher.exams.attempts', $attempt->exam))
            ->exists();

        if (! $alreadyNotified) {
            $priorFinished = ExamAttempt::where('exam_id', $attempt->exam_id)
                ->where('status', 'finished')
                ->where('id', '!=', $attempt->id)
                ->exists();

            if (! $priorFinished) {
                NotificationHelper::notifyTeacher(
                    $teacherId,
                    'exam',
                    'بدأ استقبال إجابات الامتحان',
                    "بدأ الطلاب في تسليم إجابات امتحان: {$attempt->exam->title}",
                    route('teacher.exams.attempts', $attempt->exam)
                );
            }
        }

        return redirect()->route('student.exams.result', $attempt)->with('success', 'تم تسليم الامتحان.');
    }

    /**
     * Result page (score only, no answer key).
     */
    public function result(ExamAttempt $attempt): View
    {
        $this->authorizeAttempt($attempt);

        if (! $attempt->exam->show_result_immediately) {
            return view('student.exams.result-hidden', [
                'header' => $attempt->exam->title,
                'attempt' => $attempt,
                'exam' => $attempt->exam,
            ]);
        }

        return view('student.exams.result', [
            'header' => $attempt->exam->title,
            'attempt' => $attempt,
            'exam' => $attempt->exam,
        ]);
    }

    /**
     * Auto-save answer during exam.
     */
    public function autoSave(Request $request, ExamAttempt $attempt): RedirectResponse
    {
        $this->authorizeAttempt($attempt);

        if ($attempt->isFinished()) {
            return redirect()->route('student.exams.result', $attempt);
        }

        $validated = $request->validate([
            'question_id' => ['required', 'integer', 'exists:exam_questions,id'],
            'answer' => ['nullable', 'string'],
        ]);

        $question = \App\Models\ExamQuestion::findOrFail($validated['question_id']);
        
        if ($question->exam_id !== $attempt->exam_id) {
            abort(403);
        }

        $selected = $validated['answer'];
        $isCorrect = $selected !== null && $selected === $question->correct_answer;
        $mark = $isCorrect ? $question->mark : 0;

        ExamAnswer::updateOrCreate(
            ['attempt_id' => $attempt->id, 'question_id' => $question->id],
            ['selected_answer' => $selected, 'is_correct' => $isCorrect, 'mark' => $mark]
        );

        return response()->json(['success' => true]);
    }

    /**
     * Intentional leave -> cancel the attempt permanently.
     */
    public function leave(ExamAttempt $attempt): RedirectResponse
    {
        $this->authorizeAttempt($attempt);

        if ($attempt->isRunning()) {
            $attempt->update(['status' => 'cancelled', 'finished_at' => now()]);
        }

        return redirect()->route('student.exams.index')->with('info', 'تم إنهاء محاولة الامتحان.');
    }

    private function activeGroup(?User $account): ?StudyGroup
    {
        if (! $account) {
            return null;
        }

        return GroupEnrollment::where('student_id', $account->id)
            ->where('status', 'enrolled')
            ->with('group')
            ->first()?->group;
    }

    private function authorizeAttempt(ExamAttempt $attempt): void
    {
        $account = auth()->user()->activeStudentAccount();
        if (! $account || $attempt->student_id !== $account->id) {
            abort(403);
        }
    }
}