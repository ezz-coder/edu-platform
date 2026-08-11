<?php

namespace App\Http\Controllers;

use App\Models\AcademicTerm;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamQuestion;
use App\Models\GroupEnrollment;
use App\Models\Notification;
use App\Models\StudyGroup;
use App\Models\TeacherActivity;
use App\Utilities\NotificationHelper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TeacherExamController extends Controller
{
    private function activeTermId(): ?int
    {
        $term = AcademicTerm::where('teacher_id', auth()->id())
            ->where('status', 'active')
            ->first();

        return $term?->id;
    }

    public function index(): View
    {
        $exams = Exam::where('teacher_id', auth()->id())
            ->withCount(['questions', 'attempts'])
            ->withCount(['attempts as finished_attempts' => fn ($q) => $q->where('status', 'finished')])
            ->latest()
            ->paginate(15);

        $total = Exam::where('teacher_id', auth()->id())->count();
        $published = Exam::where('teacher_id', auth()->id())->where('status', 'visible')->count();
        $hidden = Exam::where('teacher_id', auth()->id())->where('status', 'hidden')->count();
        $finishedStudents = ExamAttempt::where('status', 'finished')
            ->whereHas('exam', fn ($q) => $q->where('teacher_id', auth()->id()))
            ->distinct('student_id')
            ->count('student_id');

        return view('teacher.exams.index', [
            'header' => 'الامتحانات',
            'exams' => $exams,
            'total' => $total,
            'published' => $published,
            'hidden' => $hidden,
            'finishedStudents' => $finishedStudents,
        ]);
    }

    public function create(): View
    {
        $groups = StudyGroup::where('teacher_id', auth()->id())->orderBy('title')->get();

        return view('teacher.exams.create', [
            'header' => 'إنشاء امتحان',
            'groups' => $groups,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'duration_minutes' => ['required', 'integer', 'min:1'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'group_ids' => ['required', 'array', 'min:1'],
            'group_ids.*' => ['integer', 'exists:study_groups,id'],
        ]);

        $exam = Exam::create([
            'teacher_id' => auth()->id(),
            'academic_term_id' => $this->activeTermId(),
            'title' => $validated['title'],
            'description' => $validated['description'],
            'duration_minutes' => $validated['duration_minutes'],
            'starts_at' => $validated['starts_at'] ?? null,
            'ends_at' => $validated['ends_at'] ?? null,
            'status' => 'visible',
        ]);

        $exam->targetGroups()->sync($validated['group_ids']);

        TeacherActivity::log(auth()->id(), 'exam_created', 'إنشاء امتحان', "تم إنشاء امتحان {$exam->title}");

        // Notify targeted students (one each, never notify teacher per student).
        $studentIds = NotificationHelper::groupStudentIds($validated['group_ids']);
        NotificationHelper::notifyStudents(
            auth()->id(),
            $studentIds,
            'exam_created',
            'امتحان جديد',
            "تم إنشاء امتحان جديد: {$exam->title}",
            route('student.exams.index')
        );

        return redirect()->route('teacher.exams.questions', $exam)->with('success', 'تم إنشاء الامتحان. أضف الأسئلة الآن.');
    }

    public function questions(Exam $exam): View
    {
        $this->authorizeExam($exam);

        $questions = $exam->questions()->orderBy('sort_order')->get();
        $groups = StudyGroup::where('teacher_id', auth()->id())->orderBy('title')->get();

        return view('teacher.exams.questions', [
            'header' => $exam->title,
            'exam' => $exam,
            'questions' => $questions,
            'groups' => $groups,
        ]);
    }

    public function addQuestion(Request $request, Exam $exam): RedirectResponse
    {
        $this->authorizeExam($exam);

        $validated = $request->validate([
            'question' => ['required', 'string'],
            'option_a' => ['required', 'string'],
            'option_b' => ['required', 'string'],
            'option_c' => ['required', 'string'],
            'option_d' => ['required', 'string'],
            'correct_answer' => ['required', 'in:a,b,c,d'],
            'mark' => ['required', 'integer', 'min:1'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        ExamQuestion::create([
            'exam_id' => $exam->id,
            'type' => 'mcq',
            'question' => $validated['question'],
            'option_a' => $validated['option_a'],
            'option_b' => $validated['option_b'],
            'option_c' => $validated['option_c'],
            'option_d' => $validated['option_d'],
            'correct_answer' => $validated['correct_answer'],
            'mark' => $validated['mark'],
            'sort_order' => $validated['sort_order'] ?? $exam->questions()->count(),
        ]);

        return back()->with('success', 'تمت إضافة السؤال.');
    }

    public function deleteQuestion(ExamQuestion $question): RedirectResponse
    {
        $this->authorizeExam($question->exam);

        $question->delete();

        return back()->with('success', 'تم حذف السؤال.');
    }

    public function edit(Exam $exam): View
    {
        $this->authorizeExam($exam);
        $groups = StudyGroup::where('teacher_id', auth()->id())->orderBy('title')->get();

        return view('teacher.exams.edit', [
            'header' => 'تعديل امتحان',
            'exam' => $exam,
            'groups' => $groups,
        ]);
    }

    public function update(Request $request, Exam $exam): RedirectResponse
    {
        $this->authorizeExam($exam);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'duration_minutes' => ['required', 'integer', 'min:1'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'status' => ['required', 'in:visible,hidden'],
            'group_ids' => ['required', 'array', 'min:1'],
            'group_ids.*' => ['integer', 'exists:study_groups,id'],
        ]);

        $exam->update([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'duration_minutes' => $validated['duration_minutes'],
            'starts_at' => $validated['starts_at'],
            'ends_at' => $validated['ends_at'],
            'status' => $validated['status'],
        ]);

        $exam->targetGroups()->sync($validated['group_ids']);

        return redirect()->route('teacher.exams.index')->with('success', 'تم تحديث الامتحان.');
    }

    public function publish(Exam $exam): RedirectResponse
    {
        $this->authorizeExam($exam);

        $exam->update(['status' => 'visible']);

        // Notify targeted students
        $groupIds = $exam->targetGroups()->pluck('id')->toArray();
        if (! empty($groupIds)) {
            $studentIds = NotificationHelper::groupStudentIds($groupIds);
            NotificationHelper::notifyStudents(
                auth()->id(),
                $studentIds,
                'exam_published',
                'امتحان منشور',
                "تم نشر امتحان {$exam->title}. يمكنك البدء في الحل الآن.",
                route('student.exams.index')
            );
        }

        TeacherActivity::log(auth()->id(), 'exam_published', 'نشر امتحان', "تم نشر امتحان {$exam->title}");

        return back()->with('success', 'تم نشر الامتحان. الطلاب يمكنهم الحل الآن.');
    }

    public function close(Exam $exam): RedirectResponse
    {
        $this->authorizeExam($exam);

        $exam->update(['status' => 'hidden']);

        // Notify targeted students
        $groupIds = $exam->targetGroups()->pluck('id')->toArray();
        if (! empty($groupIds)) {
            $studentIds = NotificationHelper::groupStudentIds($groupIds);
            NotificationHelper::notifyStudents(
                auth()->id(),
                $studentIds,
                'exam_closed',
                'تم إيقاف الامتحان',
                "تم إيقاف امتحان {$exam->title}. لا يمكن للطلاب بدء محاولة جديدة.",
                route('student.exams.index')
            );
        }

        TeacherActivity::log(auth()->id(), 'exam_closed', 'إيقاف امتحان', "تم إيقاف امتحان {$exam->title}");

        return back()->with('success', 'تم إيقاف الامتحان. لن يمكن للطلاب بدء محاولة جديدة.');
    }

    public function archive(Exam $exam): RedirectResponse
    {
        $this->authorizeExam($exam);

        $exam->update(['status' => 'hidden']);
        $exam->delete();

        TeacherActivity::log(auth()->id(), 'exam_archived', 'أرشفة امتحان', "تم أرشفة امتحان {$exam->title}");

        return back()->with('success', 'تم أرشفة الامتحان. سيظل متاحاً للعرض والتقارير.');
    }

    public function destroy(Exam $exam): RedirectResponse
    {
        $this->authorizeExam($exam);
        $exam->delete();

        return redirect()->route('teacher.exams.index')->with('success', 'تم حذف الامتحان.');
    }

    public function recycleBin(): View
    {
        $deleted = Exam::onlyTrashed()
            ->where('teacher_id', auth()->id())
            ->latest()
            ->paginate(20);

        return view('teacher.recycle-bin.index', [
            'header' => 'سلة المحذوفات - الاختبارات',
            'items' => $deleted,
            'type' => 'exam',
        ]);
    }

    public function restore(Exam $exam): RedirectResponse
    {
        $this->authorizeExam($exam);

        if ($exam->trashed()) {
            $exam->restore();
        }

        TeacherActivity::log(auth()->id(), 'exam_restored', 'استرجاع اختبار', "تم استرجاع اختبار {$exam->title}");

        return back()->with('success', 'تم استرجاع الاختبار بنجاح');
    }

    public function forceDelete(Exam $exam): RedirectResponse
    {
        $this->authorizeExam($exam);

        if ($exam->trashed()) {
            $exam->forceDelete();
        }

        TeacherActivity::log(auth()->id(), 'exam_permanent_deleted', 'حذف نهائي اختبار', "تم حذف اختبار {$exam->title} نهائياً");

        return back()->with('success', 'تم الحذف النهائي بنجاح');
    }

    public function duplicate(Exam $exam): RedirectResponse
    {
        $this->authorizeExam($exam);

        $copy = Exam::create([
            'teacher_id' => auth()->id(),
            'academic_term_id' => $this->activeTermId(),
            'title' => $exam->title . ' (نسخة)',
            'description' => $exam->description,
            'duration_minutes' => $exam->duration_minutes,
            'starts_at' => $exam->starts_at,
            'ends_at' => $exam->ends_at,
            'status' => 'visible',
        ]);

        $copy->targetGroups()->sync($exam->targetGroups->pluck('id'));

        foreach ($exam->questions as $q) {
            ExamQuestion::create([
                'exam_id' => $copy->id,
                'type' => 'mcq',
                'question' => $q->question,
                'option_a' => $q->option_a,
                'option_b' => $q->option_b,
                'option_c' => $q->option_c,
                'option_d' => $q->option_d,
                'correct_answer' => $q->correct_answer,
                'mark' => $q->mark,
                'sort_order' => $q->sort_order,
            ]);
        }

        return redirect()->route('teacher.exams.edit', $copy)->with('success', 'تم نسخ الامتحان مع أسئلته.');
    }

    /**
     * PART 1 + 2 + 3 + 4 + 8 + 9: Exam management page with stats, attempts table, search, filter.
     */
    public function attempts(Request $request, Exam $exam): View
    {
        $this->authorizeExam($exam);

        $groups = StudyGroup::where('teacher_id', auth()->id())->orderBy('title')->get();

        // Targeted students = students enrolled in any target group of this exam.
        $targetGroupIds = $exam->targetGroups()->pluck('group_id');
        $targetedStudentIds = GroupEnrollment::whereIn('group_id', $targetGroupIds)
            ->where('status', 'enrolled')
            ->distinct('student_id')
            ->pluck('student_id');

        // Stats (database queries only).
        $attemptsBase = ExamAttempt::where('exam_id', $exam->id);
        $stats = [
            'targeted' => $targetedStudentIds->count(),
            'started' => (clone $attemptsBase)->whereIn('status', ['running', 'finished'])->distinct('student_id')->count('student_id'),
            'finished' => (clone $attemptsBase)->where('status', 'finished')->distinct('student_id')->count('student_id'),
            'running' => (clone $attemptsBase)->where('status', 'running')->distinct('student_id')->count('student_id'),
            'not_started' => max(0, $targetedStudentIds->count() - (clone $attemptsBase)->whereIn('status', ['running', 'finished'])->distinct('student_id')->count('student_id')),
            'cancelled' => (clone $attemptsBase)->where('status', 'cancelled')->distinct('student_id')->count('student_id'),
        ];

        // Running students (PART 8) - simple server calculation.
        $running = (clone $attemptsBase)->where('status', 'running')
            ->with(['student', 'student.enrollments' => fn ($q) => $q->where('status', 'enrolled')->whereIn('group_id', $targetGroupIds)])
            ->get();

        // Finished students (PART 9) - newest first.
        $finished = (clone $attemptsBase)->where('status', 'finished')
            ->with(['student', 'student.enrollments' => fn ($q) => $q->where('status', 'enrolled')->whereIn('group_id', $targetGroupIds)])
            ->orderByDesc('finished_at')
            ->get();

        // Attempts table with search + filter (PART 2,3,4).
        $query = ExamAttempt::where('exam_id', $exam->id)
            ->with(['student', 'student.enrollments' => fn ($q) => $q->where('status', 'enrolled')->whereIn('group_id', $targetGroupIds)]);

        // Search by name / code / mobile.
        if ($search = $request->input('search')) {
            $query->whereHas('student', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('student_code', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%");
            });
        }

        // Filter by group (via enrollment).
        if ($groupId = $request->input('group_id')) {
            $query->whereHas('student', fn ($q) => $q->whereHas('enrollments', fn ($q2) => $q2->where('group_id', $groupId)->where('status', 'enrolled')));
        }

        // Filter by status.
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $attempts = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        return view('teacher.exams.attempts', [
            'header' => $exam->title,
            'exam' => $exam,
            'groups' => $groups,
            'stats' => $stats,
            'running' => $running,
            'finished' => $finished,
            'attempts' => $attempts,
            'filters' => [
                'search' => $request->input('search'),
                'group_id' => $request->input('group_id'),
                'status' => $request->input('status'),
            ],
        ]);
    }

    /**
     * PART 5: Teacher-only student result page with questions + answers.
     */
    public function showAttempt(ExamAttempt $attempt): View
    {
        $this->authorizeExam($attempt->exam);

        $attempt->load([
            'student',
            'answers.question',
            'student.enrollments' => fn ($q) => $q->where('status', 'enrolled'),
        ]);

        $enrollment = $attempt->student->enrollments
            ->whereIn('group_id', $attempt->exam->targetGroups()->pluck('group_id')->toArray())
            ->first();

        $questions = $attempt->exam->questions()->orderBy('sort_order')->get();

        return view('teacher.exams.result', [
            'header' => $attempt->exam->title,
            'exam' => $attempt->exam,
            'attempt' => $attempt,
            'enrollment' => $enrollment,
            'questions' => $questions,
        ]);
    }

    /**
     * PART 6: Reset one student's attempt (delete attempt + answers).
     */
    public function resetAttempt(ExamAttempt $attempt): RedirectResponse
    {
        $this->authorizeExam($attempt->exam);

        $attempt->answers()->delete();
        $attempt->delete();

        return redirect()->route('teacher.exams.attempts', $attempt->exam)
            ->with('success', 'تم إعادة تعيين محاولة الطالب. يمكنه أداء الامتحان مرة أخرى.');
    }

    /**
     * PART 7: Edit exam start/end time only.
     */
    public function editTime(Exam $exam): View
    {
        $this->authorizeExam($exam);

        return view('teacher.exams.time', [
            'header' => 'تعديل وقت الامتحان',
            'exam' => $exam,
        ]);
    }

    public function updateTime(Request $request, Exam $exam): RedirectResponse
    {
        $this->authorizeExam($exam);

        $validated = $request->validate([
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ]);

        $exam->update([
            'starts_at' => $validated['starts_at'],
            'ends_at' => $validated['ends_at'],
        ]);

        return redirect()->route('teacher.exams.attempts', $exam)
            ->with('success', 'تم تحديث وقت الامتحان. الطلاب الذين بدأوا يستمرون بمؤقتهم الحالي.');
    }

    private function authorizeExam(Exam $exam): void
    {
        if ($exam->teacher_id !== auth()->id()) {
            abort(403);
        }
    }
}