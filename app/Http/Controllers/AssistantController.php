<?php

namespace App\Http\Controllers;

use App\Models\AcademicTerm;
use App\Models\ActivityLog;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\Exam;
use App\Models\ExamQuestion;
use App\Models\GroupEnrollment;
use App\Models\QuestionBank;
use App\Models\Resource;
use App\Models\Reservation;
use App\Models\StudyGroup;
use App\Models\TeacherActivity;
use App\Utilities\NotificationHelper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Sprint 18 — Assistant scoped actions.
 *
 * Every action is scoped to the assistant's own teacher (assistant->teacher_id).
 * Permissions are enforced by the `assistant:<permission>` middleware on the
 * routes. Concrete authorization (group/record ownership) is re-checked here.
 */
class AssistantController extends Controller
{
    private const RESOURCE_TYPES = ['pdf', 'image', 'zip', 'google_drive', 'external_link', 'video_link', 'other'];

    private function teacherId(): int
    {
        return auth('assistant')->id()
            ? auth('assistant')->user()->teacher_id
            : 0;
    }

    private function activeTermId(): ?int
    {
        $term = AcademicTerm::where('teacher_id', $this->teacherId())
            ->where('status', 'active')
            ->first();

        return $term?->id;
    }

    /* ============================================================= */
    /* EXAM MANAGEMENT (can_manage_exams)                            */
    /* ============================================================= */

    public function examsIndex(): View
    {
        $exams = Exam::where('teacher_id', $this->teacherId())
            ->withCount(['questions', 'attempts'])
            ->withCount(['attempts as finished_attempts' => fn ($q) => $q->where('status', 'finished')])
            ->latest()
            ->paginate(15);

        return view('assistant.exams.index', [
            'header' => 'الامتحانات',
            'exams' => $exams,
        ]);
    }

    public function examsRecycleBin(): View
    {
        $deleted = Exam::onlyTrashed()
            ->where('teacher_id', $this->teacherId())
            ->latest()
            ->paginate(20);

        return view('assistant.recycle-bin.index', [
            'header' => 'سلة المحذوفات - الاختبارات',
            'items' => $deleted,
            'type' => 'exam',
        ]);
    }

    public function examsQuestions(Exam $exam): View
    {
        $this->authorizeExam($exam);

        $questions = $exam->questions()->orderBy('sort_order')->get();
        $groups = StudyGroup::where('teacher_id', $this->teacherId())->orderBy('title')->get();

        return view('assistant.exams.questions', [
            'header' => $exam->title,
            'exam' => $exam,
            'questions' => $questions,
            'groups' => $groups,
        ]);
    }

    public function examsAddQuestion(Request $request, Exam $exam): RedirectResponse
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

        ActivityLog::log([
            'actor_id' => auth('assistant')->id(),
            'actor_type' => 'assistant',
            'actor_type_label' => 'مساعد',
            'action' => 'exam_question_added',
            'category' => 'exam',
            'description' => "تمت إضافة سؤال إلى امتحان {$exam->title} (بواسطة مساعد)",
            'owner_id' => $this->teacherId(),
            'owner_type' => 'user',
        ]);

        TeacherActivity::log($this->teacherId(), 'exam_question_added', 'إضافة سؤال', "تمت إضافة سؤال إلى امتحان {$exam->title} (بواسطة مساعد)");

        return back()->with('success', 'تمت إضافة السؤال.');
    }

    public function examsDeleteQuestion(ExamQuestion $question): RedirectResponse
    {
        $this->authorizeExam($question->exam);

        $question->delete();

        ActivityLog::log([
            'actor_id' => auth('assistant')->id(),
            'actor_type' => 'assistant',
            'actor_type_label' => 'مساعد',
            'action' => 'exam_question_deleted',
            'category' => 'exam',
            'description' => 'تم حذف سؤال من امتحان (بواسطة مساعد)',
            'owner_id' => $this->teacherId(),
            'owner_type' => 'user',
        ]);

        TeacherActivity::log($this->teacherId(), 'exam_question_deleted', 'حذف سؤال', 'تم حذف سؤال من امتحان (بواسطة مساعد)');

        return back()->with('success', 'تم حذف السؤال.');
    }

    public function examsEdit(Exam $exam): View
    {
        $this->authorizeExam($exam);
        $groups = StudyGroup::where('teacher_id', $this->teacherId())->orderBy('title')->get();

        return view('assistant.exams.edit', [
            'header' => 'تعديل امتحان',
            'exam' => $exam,
            'groups' => $groups,
        ]);
    }

    public function examsUpdate(Request $request, Exam $exam): RedirectResponse
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
            'description' => $validated['description'] ?? null,
            'duration_minutes' => $validated['duration_minutes'],
            'starts_at' => $validated['starts_at'] ?? null,
            'ends_at' => $validated['ends_at'] ?? null,
            'status' => $validated['status'],
        ]);

        $exam->targetGroups()->sync($validated['group_ids']);

        ActivityLog::log([
            'actor_id' => auth('assistant')->id(),
            'actor_type' => 'assistant',
            'actor_type_label' => 'مساعد',
            'action' => 'exam_updated',
            'category' => 'exam',
            'description' => "تم تعديل امتحان {$exam->title} (بواسطة مساعد)",
            'owner_id' => $this->teacherId(),
            'owner_type' => 'user',
        ]);

        TeacherActivity::log($this->teacherId(), 'exam_updated', 'تعديل امتحان', "تم تعديل امتحان {$exam->title} (بواسطة مساعد)");

        return redirect()->route('assistant.exams.index')->with('success', 'تم تحديث الامتحان.');
    }

    public function examsDestroy(Exam $exam): RedirectResponse
    {
        $this->authorizeExam($exam);
        $exam->delete();

        ActivityLog::log([
            'actor_id' => auth('assistant')->id(),
            'actor_type' => 'assistant',
            'actor_type_label' => 'مساعد',
            'action' => 'exam_deleted',
            'category' => 'exam',
            'description' => "تم حذف امتحان {$exam->title} (بواسطة مساعد)",
            'owner_id' => $this->teacherId(),
            'owner_type' => 'user',
        ]);

        TeacherActivity::log($this->teacherId(), 'exam_deleted', 'حذف امتحان', "تم حذف امتحان {$exam->title} (بواسطة مساعد)");

        return redirect()->route('assistant.exams.index')->with('success', 'تم حذف الامتحان.');
    }

    public function examsRestore(Exam $exam): RedirectResponse
    {
        $this->authorizeExam($exam);

        if ($exam->trashed()) {
            $exam->restore();
        }

        ActivityLog::log([
            'actor_id' => auth('assistant')->id(),
            'actor_type' => 'assistant',
            'actor_type_label' => 'مساعد',
            'action' => 'exam_restored',
            'category' => 'exam',
            'description' => "تم استرجاع امتحان {$exam->title} (بواسطة مساعد)",
            'owner_id' => $this->teacherId(),
            'owner_type' => 'user',
        ]);

        TeacherActivity::log($this->teacherId(), 'exam_restored', 'استرجاع امتحان', "تم استرجاع امتحان {$exam->title} (بواسطة مساعد)");

        return back()->with('success', 'تم استرجاع الاختبار بنجاح');
    }

    public function examsForceDelete(Exam $exam): RedirectResponse
    {
        $this->authorizeExam($exam);

        if ($exam->trashed()) {
            $exam->forceDelete();
        }

        ActivityLog::log([
            'actor_id' => auth('assistant')->id(),
            'actor_type' => 'assistant',
            'actor_type_label' => 'مساعد',
            'action' => 'exam_permanent_deleted',
            'category' => 'exam',
            'description' => "تم حذف امتحان {$exam->title} نهائياً (بواسطة مساعد)",
            'owner_id' => $this->teacherId(),
            'owner_type' => 'user',
        ]);

        TeacherActivity::log($this->teacherId(), 'exam_permanent_deleted', 'حذف نهائي امتحان', "تم حذف امتحان {$exam->title} نهائياً (بواسطة مساعد)");

        return back()->with('success', 'تم الحذف النهائي بنجاح');
    }

    public function examsPublish(Exam $exam): RedirectResponse
    {
        $this->authorizeExam($exam);

        $exam->update(['status' => 'visible']);

        // Notify targeted students
        $groupIds = $exam->targetGroups()->pluck('id')->toArray();
        if (! empty($groupIds)) {
            $studentIds = NotificationHelper::groupStudentIds($groupIds);
            NotificationHelper::notifyStudents(
                $this->teacherId(),
                $studentIds,
                'exam_published',
                'امتحان منشور',
                "تم نشر امتحان {$exam->title}. يمكنك البدء في الحل الآن.",
                route('student.exams.index')
            );
        }

        ActivityLog::log([
            'actor_id' => auth('assistant')->id(),
            'actor_type' => 'assistant',
            'actor_type_label' => 'مساعد',
            'action' => 'exam_published',
            'category' => 'exam',
            'description' => "تم نشر امتحان {$exam->title} (بواسطة مساعد)",
            'owner_id' => $this->teacherId(),
            'owner_type' => 'user',
        ]);

        TeacherActivity::log($this->teacherId(), 'exam_published', 'نشر امتحان', "تم نشر امتحان {$exam->title} (بواسطة مساعد)");

        return back()->with('success', 'تم نشر الامتحان. الطلاب يمكنهم الحل الآن.');
    }

    public function examsClose(Exam $exam): RedirectResponse
    {
        $this->authorizeExam($exam);

        $exam->update(['status' => 'hidden']);

        // Notify targeted students
        $groupIds = $exam->targetGroups()->pluck('id')->toArray();
        if (! empty($groupIds)) {
            $studentIds = NotificationHelper::groupStudentIds($groupIds);
            NotificationHelper::notifyStudents(
                $this->teacherId(),
                $studentIds,
                'exam_closed',
                'تم إيقاف الامتحان',
                "تم إيقاف امتحان {$exam->title}. لا يمكن للطلاب بدء محاولة جديدة.",
                route('student.exams.index')
            );
        }

        ActivityLog::log([
            'actor_id' => auth('assistant')->id(),
            'actor_type' => 'assistant',
            'actor_type_label' => 'مساعد',
            'action' => 'exam_closed',
            'category' => 'exam',
            'description' => "تم إيقاف امتحان {$exam->title} (بواسطة مساعد)",
            'owner_id' => $this->teacherId(),
            'owner_type' => 'user',
        ]);

        TeacherActivity::log($this->teacherId(), 'exam_closed', 'إيقاف امتحان', "تم إيقاف امتحان {$exam->title} (بواسطة مساعد)");

        return back()->with('success', 'تم إيقاف الامتحان.');
    }

    public function examsArchive(Exam $exam): RedirectResponse
    {
        $this->authorizeExam($exam);

        $exam->update(['status' => 'hidden']);
        $exam->delete();

        ActivityLog::log([
            'actor_id' => auth('assistant')->id(),
            'actor_type' => 'assistant',
            'actor_type_label' => 'مساعد',
            'action' => 'exam_archived',
            'category' => 'exam',
            'description' => "تم أرشفة امتحان {$exam->title} (بواسطة مساعد)",
            'owner_id' => $this->teacherId(),
            'owner_type' => 'user',
        ]);

        TeacherActivity::log($this->teacherId(), 'exam_archived', 'أرشفة امتحان', "تم أرشفة امتحان {$exam->title} (بواسطة مساعد)");

        return back()->with('success', 'تم أرشفة الامتحان. سيظل متاحاً للعرض والتقارير.');
    }

    public function examsPickFromBank(Request $request, Exam $exam): View
    {
        $this->authorizeExam($exam);

        $teacherId = $this->teacherId();
        $query = QuestionBank::forTeacher($teacherId)->active()->with(['lesson', 'group']);

        // Filters
        if ($type = $request->input('type')) {
            $query->byType($type);
        }
        if ($difficulty = $request->input('difficulty')) {
            $query->byDifficulty($difficulty);
        }
        if ($lessonId = $request->input('lesson_id')) {
            $query->where('lesson_id', $lessonId);
        }
        if ($groupId = $request->input('group_id')) {
            $query->where('group_id', $groupId);
        }
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('body', 'like', "%{$search}%");
            });
        }

        $questions = $query->paginate(20);

        $lessons = \App\Models\Lesson::forTeacher($teacherId)->orderBy('display_order')->get();
        $groups = StudyGroup::where('teacher_id', $teacherId)->orderBy('title')->get();

        return view('assistant.exams.questions-pick', [
            'header' => 'اختيار سؤال من البنك',
            'exam' => $exam,
            'questions' => $questions,
            'lessons' => $lessons,
            'groups' => $groups,
            'filters' => $request->only(['type', 'difficulty', 'lesson_id', 'group_id', 'search']),
        ]);
    }

    public function examsAttachFromBank(Request $request, Exam $exam): RedirectResponse
    {
        $this->authorizeExam($exam);

        $validated = $request->validate([
            'question_id' => ['required', 'exists:question_bank,id'],
            'points' => ['nullable', 'integer', 'min:1'],
        ]);

        $bankQuestion = QuestionBank::findOrFail($validated['question_id']);

        $this->authorizeQuestionInBank($bankQuestion);

        $sortOrder = $exam->questions()->count();

        ExamQuestion::create([
            'exam_id' => $exam->id,
            'question_bank_id' => $bankQuestion->id,
            'type' => $bankQuestion->type,
            'question' => $bankQuestion->body,
            'correct_answer' => $bankQuestion->correct_answer,
            'option_a' => $bankQuestion->options['a'] ?? null,
            'option_b' => $bankQuestion->options['b'] ?? null,
            'option_c' => $bankQuestion->options['c'] ?? null,
            'option_d' => $bankQuestion->options['d'] ?? null,
            'mark' => $validated['points'] ?? $bankQuestion->points,
            'sort_order' => $sortOrder,
        ]);

        ActivityLog::log([
            'actor_id' => auth('assistant')->id(),
            'actor_type' => 'assistant',
            'actor_type_label' => 'مساعد',
            'action' => 'exam_question_attached',
            'category' => 'exam',
            'description' => "تم إرفاق سؤال من بنك الأسئلة إلى امتحان {$exam->title} (بواسطة مساعد)",
            'owner_id' => $this->teacherId(),
            'owner_type' => 'user',
        ]);

        TeacherActivity::log($this->teacherId(), 'exam_question_attached', 'إرفاق سؤال', "تم إرفاق سؤال من بنك الأسئلة إلى امتحان {$exam->title} (بواسطة مساعد)");

        return back()->with('success', 'تمت إضافة السؤال إلى الامتحان.');
    }

    /* ============================================================= */
    /* QUESTION BANK (can_manage_exams)                                */
    /* ============================================================= */

    public function questionBankIndex(Request $request): View
    {
        $teacherId = $this->teacherId();

        $query = QuestionBank::forTeacher($teacherId)
            ->with(['lesson', 'group'])
            ->latest();

        // Filters
        if ($type = $request->input('type')) {
            $query->byType($type);
        }
        if ($difficulty = $request->input('difficulty')) {
            $query->byDifficulty($difficulty);
        }
        if ($lessonId = $request->input('lesson_id')) {
            $query->where('lesson_id', $lessonId);
        }
        if ($groupId = $request->input('group_id')) {
            $query->where('group_id', $groupId);
        }
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('body', 'like', "%{$search}%");
            });
        }

        $questions = $query->paginate(20);

        $lessons = \App\Models\Lesson::forTeacher($teacherId)->orderBy('display_order')->get();
        $groups = StudyGroup::where('teacher_id', $teacherId)->orderBy('title')->get();

        return view('assistant.question-bank.index', [
            'header' => 'بنك الأسئلة',
            'questions' => $questions,
            'lessons' => $lessons,
            'groups' => $groups,
            'filters' => $request->only(['type', 'difficulty', 'lesson_id', 'group_id', 'search']),
        ]);
    }

    public function questionBankCreate(): View
    {
        $lessons = \App\Models\Lesson::forTeacher($this->teacherId())->orderBy('display_order')->get();
        $groups = StudyGroup::where('teacher_id', $this->teacherId())->orderBy('title')->get();

        return view('assistant.question-bank.create', [
            'header' => 'إضافة سؤال جديد',
            'lessons' => $lessons,
            'groups' => $groups,
        ]);
    }

    public function questionBankStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'type' => ['required', 'in:mcq,true_false,fill_blank,short_answer'],
            'correct_answer' => ['nullable', 'string'],
            'options' => ['nullable', 'array'],
            'explanation' => ['nullable', 'string'],
            'difficulty' => ['required', 'in:easy,medium,hard'],
            'points' => ['required', 'integer', 'min:1'],
            'lesson_id' => ['nullable', 'exists:lessons,id'],
            'group_id' => ['nullable', 'exists:study_groups,id'],
        ]);

        $validated['teacher_id'] = $this->teacherId();
        $validated['status'] = 'active';

        $question = QuestionBank::create($validated);

        ActivityLog::log([
            'actor_id' => auth('assistant')->id(),
            'actor_type' => 'assistant',
            'actor_type_label' => 'مساعد',
            'action' => 'question_created',
            'category' => 'question_bank',
            'description' => 'تم إنشاء سؤال جديد في بنك الأسئلة (بواسطة مساعد)',
            'owner_id' => $this->teacherId(),
            'owner_type' => 'user',
        ]);

        TeacherActivity::log($this->teacherId(), 'question_created', 'إنشاء سؤال', "تم إنشاء سؤال جديد في بنك الأسئلة (بواسطة مساعد)");

        return redirect()->route('assistant.question-bank.index')
            ->with('success', 'تمت إضافة السؤال إلى بنك الأسئلة.');
    }

    public function questionBankEdit(QuestionBank $question): View
    {
        $this->authorizeQuestionInBank($question);

        $lessons = \App\Models\Lesson::forTeacher($this->teacherId())->orderBy('display_order')->get();
        $groups = StudyGroup::where('teacher_id', $this->teacherId())->orderBy('title')->get();

        return view('assistant.question-bank.edit', [
            'header' => 'تعديل السؤال',
            'question' => $question,
            'lessons' => $lessons,
            'groups' => $groups,
        ]);
    }

    public function questionBankUpdate(Request $request, QuestionBank $question): RedirectResponse
    {
        $this->authorizeQuestionInBank($question);

        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'type' => ['required', 'in:mcq,true_false,fill_blank,short_answer'],
            'correct_answer' => ['nullable', 'string'],
            'options' => ['nullable', 'array'],
            'explanation' => ['nullable', 'string'],
            'difficulty' => ['required', 'in:easy,medium,hard'],
            'points' => ['required', 'integer', 'min:1'],
            'status' => ['required', 'in:active,inactive'],
            'lesson_id' => ['nullable', 'exists:lessons,id'],
            'group_id' => ['nullable', 'exists:study_groups,id'],
        ]);

        $question->update($validated);

        ActivityLog::log([
            'actor_id' => auth('assistant')->id(),
            'actor_type' => 'assistant',
            'actor_type_label' => 'مساعد',
            'action' => 'question_updated',
            'category' => 'question_bank',
            'description' => 'تم تعديل سؤال في بنك الأسئلة (بواسطة مساعد)',
            'owner_id' => $this->teacherId(),
            'owner_type' => 'user',
        ]);

        TeacherActivity::log($this->teacherId(), 'question_updated', 'تعديل سؤال', "تم تعديل سؤال في بنك الأسئلة (بواسطة مساعد)");

        return redirect()->route('assistant.question-bank.index')
            ->with('success', 'تم تحديث السؤال بنجاح.');
    }

    public function questionBankDestroy(QuestionBank $question): RedirectResponse
    {
        $this->authorizeQuestionInBank($question);

        $question->delete();

        ActivityLog::log([
            'actor_id' => auth('assistant')->id(),
            'actor_type' => 'assistant',
            'actor_type_label' => 'مساعد',
            'action' => 'question_deleted',
            'category' => 'question_bank',
            'description' => 'تم حذف سؤال من بنك الأسئلة (بواسطة مساعد)',
            'owner_id' => $this->teacherId(),
            'owner_type' => 'user',
        ]);

        TeacherActivity::log($this->teacherId(), 'question_deleted', 'حذف سؤال', "تم حذف سؤال من بنك الأسئلة (بواسطة مساعد)");

        return back()->with('success', 'تم حذف السؤال.');
    }

    public function questionBankRecycleBin(): View
    {
        $deleted = QuestionBank::onlyTrashed()
            ->where('teacher_id', $this->teacherId())
            ->latest()
            ->paginate(20);

        return view('assistant.recycle-bin.index', [
            'header' => 'سلة المحذوفات - بنك الأسئلة',
            'items' => $deleted,
            'type' => 'question_bank',
        ]);
    }

    public function questionBankRestore(QuestionBank $question): RedirectResponse
    {
        $this->authorizeQuestionInBank($question);

        if ($question->trashed()) {
            $question->restore();
        }

        ActivityLog::log([
            'actor_id' => auth('assistant')->id(),
            'actor_type' => 'assistant',
            'actor_type_label' => 'مساعد',
            'action' => 'question_restored',
            'category' => 'question_bank',
            'description' => 'تم استرجاع سؤال من بنك الأسئلة (بواسطة مساعد)',
            'owner_id' => $this->teacherId(),
            'owner_type' => 'user',
        ]);

        TeacherActivity::log($this->teacherId(), 'question_restored', 'استرجاع سؤال', "تم استرجاع سؤال من بنك الأسئلة (بواسطة مساعد)");

        return back()->with('success', 'تم استرجاع السؤال بنجاح');
    }

    public function questionBankForceDelete(QuestionBank $question): RedirectResponse
    {
        $this->authorizeQuestionInBank($question);

        if ($question->trashed()) {
            $question->forceDelete();
        }

        ActivityLog::log([
            'actor_id' => auth('assistant')->id(),
            'actor_type' => 'assistant',
            'actor_type_label' => 'مساعد',
            'action' => 'question_permanent_deleted',
            'category' => 'question_bank',
            'description' => 'تم حذف سؤال نهائياً من بنك الأسئلة (بواسطة مساعد)',
            'owner_id' => $this->teacherId(),
            'owner_type' => 'user',
        ]);

        TeacherActivity::log($this->teacherId(), 'question_permanent_deleted', 'حذف نهائي سؤال', "تم حذف سؤال نهائياً من بنك الأسئلة (بواسطة مساعد)");

        return back()->with('success', 'تم الحذف النهائي بنجاح');
    }

    /* ============================================================= */
    /* ATTENDANCE (can_take_attendance)                             */
    /* ============================================================= */

    public function attendanceIndex(): View
    {
        $sessions = AttendanceSession::where('teacher_id', $this->teacherId())
            ->with('group')
            ->latest('started_at')
            ->get();

        return view('assistant.attendance.index', [
            'header' => 'جلسات الحضور',
            'sessions' => $sessions,
        ]);
    }

    public function attendanceCreate(): View
    {
        $groups = StudyGroup::where('teacher_id', $this->teacherId())
            ->where('status', 'active')
            ->orderBy('title')
            ->get();

        return view('assistant.attendance.create', [
            'header' => 'بدء حصة حضور',
            'groups' => $groups,
        ]);
    }

    public function attendanceStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'group_id' => ['required', 'exists:study_groups,id'],
            'lesson_number' => ['required', 'integer', 'min:1'],
        ]);

        $group = StudyGroup::where('id', $validated['group_id'])
            ->where('teacher_id', $this->teacherId())
            ->firstOrFail();

        $open = AttendanceSession::where('study_group_id', $group->id)
            ->where('teacher_id', $this->teacherId())
            ->where('status', 'open')
            ->first();

        if ($open) {
            return redirect()->route('assistant.attendance.show', $open)
                ->with('info', 'توجد حصة مفتوحة لهذه المجموعة.');
        }

        $session = AttendanceSession::create([
            'study_group_id' => $group->id,
            'teacher_id' => $this->teacherId(),
            'lesson_number' => $validated['lesson_number'],
            'academic_term_id' => $group->academic_term_id,
            'attendance_date' => now()->toDateString(),
            'started_at' => now(),
            'status' => 'open',
        ]);

        TeacherActivity::log($this->teacherId(), 'attendance_session_created', 'بدء حصة حضور', "تم بدء حصة حضور جديدة (بواسطة مساعد)");

        return redirect()->route('assistant.attendance.show', $session);
    }

    public function attendanceShow(AttendanceSession $session): View
    {
        $this->authorizeSession($session);

        $group = $session->group;
        $total = $group->currentStudents();

        $enrollments = GroupEnrollment::where('group_id', $group->id)
            ->where('status', 'enrolled')
            ->with('student')
            ->orderBy('created_at')
            ->get();

        $recordsByStudent = $session->records()
            ->with('student')
            ->get()
            ->keyBy('student_id');

        $recorded = $recordsByStudent->count();
        $remaining = max(0, $total - $recorded);

        $lastRecord = $session->records()
            ->with('student')
            ->latest('scanned_at')
            ->first();

        return view('assistant.attendance.show', [
            'header' => 'جلسة حضور - ' . $group->title,
            'session' => $session,
            'group' => $group,
            'enrollments' => $enrollments,
            'recordsByStudent' => $recordsByStudent,
            'total' => $total,
            'recorded' => $recorded,
            'remaining' => $remaining,
            'lastRecord' => $lastRecord,
        ]);
    }

    public function attendanceScan(Request $request, AttendanceSession $session): RedirectResponse
    {
        $this->authorizeSession($session);

        if (! $session->isOpen()) {
            return back()->with('error', 'الحصة مغلقة.');
        }

        $validated = $request->validate([
            'student_id' => ['nullable', 'integer'],
            'student_code' => ['required_without:student_id', 'string'],
            'qr_token' => ['nullable', 'string'],
        ]);

        return $this->recordAttendance($session, $validated);
    }

    public function attendanceClose(Request $request, AttendanceSession $session): RedirectResponse
    {
        $this->authorizeSession($session);

        if ($session->isOpen()) {
            $group = $session->group;

            $enrolledIds = GroupEnrollment::where('group_id', $group->id)
                ->where('status', 'enrolled')
                ->pluck('student_id');

            $recordedIds = AttendanceRecord::where('attendance_session_id', $session->id)
                ->pluck('student_id');

            $absentIds = $enrolledIds->diff($recordedIds);

            foreach ($absentIds as $studentId) {
                AttendanceRecord::create([
                    'attendance_session_id' => $session->id,
                    'student_id' => $studentId,
                    'group_id' => $group->id,
                    'attendance_type' => 'absent',
                    'scanned_at' => now(),
                ]);
            }

            $session->update([
                'status' => 'closed',
                'closed_at' => now(),
            ]);
        }

        TeacherActivity::log($this->teacherId(), 'attendance_session_closed', 'إنهاء حصة حضور', "تم إنهاء حصة حضور (بواسطة مساعد)");

        return redirect()->route('assistant.attendance.show', $session)
            ->with('success', 'تم إنهاء الحصة وتسجيل الغياب.');
    }

    private function recordAttendance(AttendanceSession $session, array $input): RedirectResponse
    {
        $group = $session->group;

        $student = ! empty($input['student_id'])
            ? \App\Models\User::where('role', 'student')->where('id', $input['student_id'])->first()
            : \App\Models\User::where('role', 'student')->where('student_code', $input['student_code'])->first();

        if (! $student) {
            return back()->with('error', 'كود الطالب غير صحيح.');
        }

        $enrollment = GroupEnrollment::where('student_id', $student->id)
            ->where('group_id', $group->id)
            ->where('status', 'enrolled')
            ->first();

        if (! $enrollment) {
            return back()->with('error', 'هذا الطالب غير مسجل في هذه المجموعة.');
        }

        if (! empty($input['qr_token']) && $enrollment->qr_token !== null) {
            if (! hash_equals((string) $enrollment->qr_token, (string) $input['qr_token'])) {
                return back()->with('error', 'كود الطالب غير صحيح.');
            }
        }

        $exists = AttendanceRecord::where('attendance_session_id', $session->id)
            ->where('student_id', $student->id)
            ->exists();

        if ($exists) {
            return back()->with('error', 'تم تسجيل حضور هذا الطالب مسبقاً.');
        }

        AttendanceRecord::create([
            'attendance_session_id' => $session->id,
            'student_id' => $student->id,
            'group_id' => $group->id,
            'attendance_type' => 'present',
            'scanned_at' => now(),
        ]);

        return back()->with('success', 'تم تسجيل الحضور.');
    }

    /* ============================================================= */
    /* RESOURCES (can_upload_resources)                             */
    /* ============================================================= */

    public function resourcesIndex(Request $request): View
    {
        $query = Resource::where('teacher_id', $this->teacherId())
            ->with(['targets', 'lesson']);

        // Search
        if ($search = $request->input('search')) {
            $query->where('title', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%");
        }

        // Filter by type
        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        // Filter by status
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $resources = $query->latest()->paginate(20);

        return view('assistant.resources.index', [
            'header' => 'المصادر',
            'resources' => $resources,
            'types' => self::RESOURCE_TYPES,
            'filters' => $request->only(['search', 'type', 'status']),
        ]);
    }

    public function resourcesCreate(): View
    {
        $groups = StudyGroup::where('teacher_id', $this->teacherId())
            ->orderBy('title')
            ->get();
        $lessons = \App\Models\Lesson::forTeacher($this->teacherId())
            ->with('group')
            ->ordered()
            ->get();

        return view('assistant.resources.create', [
            'header' => 'إنشاء مصدر',
            'groups' => $groups,
            'lessons' => $lessons,
            'types' => self::RESOURCE_TYPES,
        ]);
    }

    public function resourcesStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['required', 'in:' . implode(',', self::RESOURCE_TYPES)],
            'file' => ['nullable', 'file', 'max:20480'],
            'url' => ['nullable', 'string', 'max:1000'],
            'lesson_id' => ['nullable', 'exists:lessons,id'],
            'publish_date' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:publish_date'],
            'pinned' => ['nullable', 'boolean'],
            'target_scope' => ['required', 'in:group,stage,gender,all'],
            'group_ids' => ['nullable', 'array'],
            'group_ids.*' => ['integer', 'exists:study_groups,id'],
            'stage' => ['nullable', 'string'],
            'gender' => ['nullable', 'in:boys,girls,mixed'],
        ]);

        if (($validated['lesson_id'] ?? null) !== null) {
            $lesson = \App\Models\Lesson::where('id', $validated['lesson_id'])
                ->where('teacher_id', $this->teacherId())
                ->firstOrFail();
        }

        $filePath = null;
        if (in_array($validated['type'], ['pdf', 'image', 'zip', 'other']) && $request->hasFile('file')) {
            $filePath = $request->file('file')->store('resources', 'public');
        }

        $resource = Resource::create([
            'teacher_id' => $this->teacherId(),
            'academic_term_id' => $this->activeTermId(),
            'lesson_id' => $validated['lesson_id'] ?? null,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'type' => $validated['type'],
            'file_path' => $filePath,
            'url' => $validated['url'] ?? null,
            'publish_date' => $validated['publish_date'] ?? null,
            'expires_at' => $validated['expires_at'] ?? null,
            'pinned' => $request->boolean('pinned'),
            'status' => 'visible',
        ]);

        $this->syncResourceTargets($resource, $validated);
        $this->enforceResourcePinLimit();

        ActivityLog::log([
            'actor_id' => auth('assistant')->id(),
            'actor_type' => 'assistant',
            'actor_type_label' => 'مساعد',
            'action' => 'resource_created',
            'category' => 'resource',
            'description' => "تم إنشاء مصدر {$resource->title} (بواسطة مساعد)",
            'owner_id' => $this->teacherId(),
            'owner_type' => 'user',
        ]);

        TeacherActivity::log($this->teacherId(), 'resource_created', 'إنشاء مصدر', "تم إنشاء مصدر {$resource->title} (بواسطة مساعد)");

        return redirect()->route('assistant.resources.index')->with('success', 'تم نشر المصدر بنجاح.');
    }

    public function resourcesEdit(Resource $resource): View
    {
        $this->authorizeResource($resource);

        $groups = StudyGroup::where('teacher_id', $this->teacherId())
            ->orderBy('title')
            ->get();
        $lessons = \App\Models\Lesson::forTeacher($this->teacherId())
            ->with('group')
            ->ordered()
            ->get();

        return view('assistant.resources.edit', [
            'header' => 'تعديل مصدر',
            'resource' => $resource,
            'groups' => $groups,
            'lessons' => $lessons,
            'types' => self::RESOURCE_TYPES,
        ]);
    }

    public function resourcesUpdate(Request $request, Resource $resource): RedirectResponse
    {
        $this->authorizeResource($resource);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['required', 'in:' . implode(',', self::RESOURCE_TYPES)],
            'file' => ['nullable', 'file', 'max:20480'],
            'url' => ['nullable', 'string', 'max:1000'],
            'lesson_id' => ['nullable', 'exists:lessons,id'],
            'publish_date' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:publish_date'],
            'pinned' => ['nullable', 'boolean'],
            'status' => ['required', 'in:visible,hidden,archived'],
            'target_scope' => ['required', 'in:group,stage,gender,all'],
            'group_ids' => ['nullable', 'array'],
            'group_ids.*' => ['integer', 'exists:study_groups,id'],
            'stage' => ['nullable', 'string'],
            'gender' => ['nullable', 'in:boys,girls,mixed'],
        ]);

        if (($validated['lesson_id'] ?? null) !== null) {
            $lesson = \App\Models\Lesson::where('id', $validated['lesson_id'])
                ->where('teacher_id', $this->teacherId())
                ->firstOrFail();
        }

        $filePath = $resource->file_path;
        if (in_array($validated['type'], ['pdf', 'image', 'zip', 'other']) && $request->hasFile('file')) {
            $filePath = $request->file('file')->store('resources', 'public');
        }

        $resource->update([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'type' => $validated['type'],
            'file_path' => $filePath,
            'url' => $validated['url'] ?? null,
            'lesson_id' => $validated['lesson_id'] ?? null,
            'publish_date' => $validated['publish_date'] ?? null,
            'expires_at' => $validated['expires_at'] ?? null,
            'pinned' => $request->boolean('pinned'),
            'status' => $validated['status'],
        ]);

        $this->syncResourceTargets($resource, $validated);
        $this->enforceResourcePinLimit();

        ActivityLog::log([
            'actor_id' => auth('assistant')->id(),
            'actor_type' => 'assistant',
            'actor_type_label' => 'مساعد',
            'action' => 'resource_updated',
            'category' => 'resource',
            'description' => "تم تعديل مصدر {$resource->title} (بواسطة مساعد)",
            'owner_id' => $this->teacherId(),
            'owner_type' => 'user',
        ]);

        TeacherActivity::log($this->teacherId(), 'resource_updated', 'تعديل مصدر', "تم تعديل مصدر {$resource->title} (بواسطة مساعد)");

        return redirect()->route('assistant.resources.index')->with('success', 'تم تحديث المصدر بنجاح.');
    }

    public function resourcesDestroy(Resource $resource): RedirectResponse
    {
        $this->authorizeResource($resource);
        $resource->delete();

        ActivityLog::log([
            'actor_id' => auth('assistant')->id(),
            'actor_type' => 'assistant',
            'actor_type_label' => 'مساعد',
            'action' => 'resource_deleted',
            'category' => 'resource',
            'description' => "تم حذف مصدر {$resource->title} (بواسطة مساعد)",
            'owner_id' => $this->teacherId(),
            'owner_type' => 'user',
        ]);

        TeacherActivity::log($this->teacherId(), 'resource_deleted', 'حذف مصدر', "تم حذف مصدر {$resource->title} (بواسطة مساعد)");

        return redirect()->route('assistant.resources.index')->with('success', 'تم حذف المصدر بنجاح');
    }

    public function resourcesRecycleBin(): View
    {
        $deleted = Resource::onlyTrashed()
            ->where('teacher_id', $this->teacherId())
            ->latest()
            ->paginate(20);

        return view('assistant.recycle-bin.index', [
            'header' => 'سلة المحذوفات - المصادر',
            'items' => $deleted,
            'type' => 'resource',
        ]);
    }

    public function resourcesRestore(Resource $resource): RedirectResponse
    {
        $this->authorizeResource($resource);

        if ($resource->trashed()) {
            $resource->restore();
        }

        ActivityLog::log([
            'actor_id' => auth('assistant')->id(),
            'actor_type' => 'assistant',
            'actor_type_label' => 'مساعد',
            'action' => 'resource_restored',
            'category' => 'resource',
            'description' => "تم استرجاع مصدر {$resource->title} (بواسطة مساعد)",
            'owner_id' => $this->teacherId(),
            'owner_type' => 'user',
        ]);

        TeacherActivity::log($this->teacherId(), 'resource_restored', 'استرجاع مصدر', "تم استرجاع مصدر {$resource->title} (بواسطة مساعد)");

        return back()->with('success', 'تم استرجاع المصدر بنجاح');
    }

    public function resourcesForceDelete(Resource $resource): RedirectResponse
    {
        $this->authorizeResource($resource);

        if ($resource->trashed()) {
            $resource->forceDelete();
        }

        ActivityLog::log([
            'actor_id' => auth('assistant')->id(),
            'actor_type' => 'assistant',
            'actor_type_label' => 'مساعد',
            'action' => 'resource_permanent_deleted',
            'category' => 'resource',
            'description' => "تم حذف مصدر {$resource->title} نهائياً (بواسطة مساعد)",
            'owner_id' => $this->teacherId(),
            'owner_type' => 'user',
        ]);

        TeacherActivity::log($this->teacherId(), 'resource_permanent_deleted', 'حذف نهائي مصدر', "تم حذف مصدر {$resource->title} نهائياً (بواسطة مساعد)");

        return back()->with('success', 'تم الحذف النهائي بنجاح');
    }

    public function resourcesTogglePin(Resource $resource): RedirectResponse
    {
        $this->authorizeResource($resource);

        $resource->update(['pinned' => ! $resource->pinned]);
        $this->enforceResourcePinLimit();

        return back()->with('success', 'تم تحديث التثبيت');
    }

    public function resourcesDuplicate(Resource $resource): RedirectResponse
    {
        $this->authorizeResource($resource);

        $copy = Resource::create([
            'teacher_id' => $this->teacherId(),
            'academic_term_id' => $this->activeTermId(),
            'lesson_id' => $resource->lesson_id,
            'title' => $resource->title . ' (نسخة)',
            'description' => $resource->description,
            'type' => $resource->type,
            'file_path' => $resource->file_path,
            'url' => $resource->url,
            'publish_date' => $resource->publish_date,
            'expires_at' => $resource->expires_at,
            'pinned' => false,
            'status' => 'visible',
        ]);

        foreach ($resource->targets as $target) {
            \App\Models\ResourceTarget::create([
                'resource_id' => $copy->id,
                'scope' => $target->scope,
                'group_id' => $target->group_id,
                'stage' => $target->stage,
                'gender' => $target->gender,
            ]);
        }

        ActivityLog::log([
            'actor_id' => auth('assistant')->id(),
            'actor_type' => 'assistant',
            'actor_type_label' => 'مساعد',
            'action' => 'resource_duplicated',
            'category' => 'resource',
            'description' => "تم نسخ مصدر {$resource->title} (بواسطة مساعد)",
            'owner_id' => $this->teacherId(),
            'owner_type' => 'user',
        ]);

        TeacherActivity::log($this->teacherId(), 'resource_duplicated', 'نسخ مصدر', "تم نسخ مصدر {$resource->title} (بواسطة مساعد)");

        return redirect()->route('assistant.resources.edit', $copy)->with('success', 'تم نسخ المصدر. عدّل الهدف إن لزم.');
    }

    public function resourcesDownload(Resource $resource): RedirectResponse
    {
        $this->authorizeResource($resource);

        if (! $resource->file_path) {
            return back()->withErrors(['file' => 'لا يوجد ملف للتحميل']);
        }

        $path = storage_path('app/public/' . $resource->file_path);

        if (! file_exists($path)) {
            return back()->withErrors(['file' => 'الملف غير موجود']);
        }

        return response()->download($path, $resource->title . '.' . pathinfo($resource->file_path, PATHINFO_EXTENSION));
    }

    private function authorizeResource(Resource $resource): void
    {
        if ($resource->teacher_id !== $this->teacherId()) {
            abort(403, 'غير مصرح لك بالوصول إلى هذا المصدر');
        }
    }

    private function syncResourceTargets(Resource $resource, array $validated): void
    {
        $resource->targets()->delete();

        $scope = $validated['target_scope'];
        if ($scope === 'group') {
            foreach ($validated['group_ids'] ?? [] as $gid) {
                \App\Models\ResourceTarget::create([
                    'resource_id' => $resource->id,
                    'scope' => 'group',
                    'group_id' => $gid,
                ]);
            }
        } elseif ($scope === 'stage') {
            \App\Models\ResourceTarget::create([
                'resource_id' => $resource->id,
                'scope' => 'stage',
                'stage' => $validated['stage'],
            ]);
        } elseif ($scope === 'gender') {
            \App\Models\ResourceTarget::create([
                'resource_id' => $resource->id,
                'scope' => 'gender',
                'gender' => $validated['gender'],
            ]);
        } else {
            \App\Models\ResourceTarget::create([
                'resource_id' => $resource->id,
                'scope' => 'all',
            ]);
        }
    }

    private function enforceResourcePinLimit(): void
    {
        $pinned = Resource::where('teacher_id', $this->teacherId())
            ->where('pinned', true)
            ->orderBy('updated_at', 'desc')
            ->get();

        if ($pinned->count() > 5) {
            $pinned->slice(5)->each->update(['pinned' => false]);
        }
    }

    /* ============================================================= */
    /* RESERVATIONS (can_manage_reservations)                       */
    /* ============================================================= */

    public function reservationsCreate(): View
    {
        $groups = StudyGroup::where('teacher_id', $this->teacherId())
            ->orderBy('title')
            ->get();

        return view('assistant.reservations.create', [
            'header' => 'إنشاء حجز',
            'groups' => $groups,
            'types' => Reservation::types(),
        ]);
    }

    public function reservationsStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['required', 'string', 'in:' . implode(',', array_keys(Reservation::types()))],
            'quantity' => ['nullable', 'integer', 'min:1'],
            'open_date' => ['nullable', 'date'],
            'close_date' => ['nullable', 'date', 'after_or_equal:open_date'],
            'group_ids' => ['required', 'array', 'min:1'],
            'group_ids.*' => ['integer', 'exists:study_groups,id'],
        ]);

        $reservation = Reservation::create([
            'teacher_id' => $this->teacherId(),
            'academic_term_id' => $this->activeTermId(),
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'type' => $validated['type'],
            'quantity' => $validated['quantity'] ?? 0,
            'open_date' => $validated['open_date'] ?? null,
            'close_date' => $validated['close_date'] ?? null,
            'status' => 'draft',
        ]);

        $reservation->targetGroups()->sync($validated['group_ids']);

        ActivityLog::log([
            'actor_id' => auth('assistant')->id(),
            'actor_type' => 'assistant',
            'actor_type_label' => 'مساعد',
            'action' => 'reservation_created',
            'category' => 'reservation',
            'description' => "تم إنشاء حجز {$reservation->title} (بواسطة مساعد)",
            'owner_id' => $this->teacherId(),
            'owner_type' => 'user',
        ]);

        TeacherActivity::log($this->teacherId(), 'reservation_created', 'إنشاء حجز', "تم إنشاء حجز {$reservation->title} (بواسطة مساعد)");

        return redirect()->route('assistant.dashboard')
            ->with('success', 'تم إنشاء الحجز بنجاح. حالياً في حالة مسودة.');
    }

    /* ============================================================= */
    /* STUDENTS (can_manage_students)                               */
    /* ============================================================= */

    public function studentsIndex(): View
    {
        $teacherId = $this->teacherId();

        $enrollments = GroupEnrollment::query()
            ->whereHas('group', fn ($q) => $q->where('teacher_id', $teacherId))
            ->with(['student', 'group'])
            ->latest()
            ->get();

        $groups = StudyGroup::where('teacher_id', $teacherId)
            ->orderBy('title')
            ->get();

        return view('assistant.students.index', [
            'header' => 'طلاب المدرس',
            'enrollments' => $enrollments,
            'groups' => $groups,
        ]);
    }

    public function studentMove(Request $request, GroupEnrollment $enrollment): RedirectResponse
    {
        $this->authorizeEnrollment($enrollment);

        $validated = $request->validate([
            'group_id' => ['required', 'exists:study_groups,id'],
        ]);

        $target = StudyGroup::where('id', $validated['group_id'])
            ->where('teacher_id', $this->teacherId())
            ->firstOrFail();

        if ($target->status !== 'active') {
            return back()->withErrors(['group_id' => 'لا يمكن نقل الطالب إلى مجموعة غير نشطة']);
        }

        if ($target->isFull()) {
            return back()->withErrors(['group_id' => 'المجموعة الهدف ممتلئة']);
        }

        $enrollment->transferTo($target->id, 'moved');

        ActivityLog::log([
            'actor_id' => auth('assistant')->id(),
            'actor_type' => 'assistant',
            'actor_type_label' => 'مساعد',
            'action' => 'student_moved',
            'category' => 'student',
            'description' => "تم نقل الطالب {$enrollment->student->name} (بواسطة مساعد)",
            'owner_id' => $this->teacherId(),
            'owner_type' => 'user',
        ]);

        TeacherActivity::log($this->teacherId(), 'student_moved', 'نقل طالب', "تم نقل الطالب {$enrollment->student->name} (بواسطة مساعد)");

        return back()->with('success', 'تم نقل الطالب بنجاح');
    }

    public function studentRestore(GroupEnrollment $enrollment): RedirectResponse
    {
        $this->authorizeEnrollment($enrollment);

        if (! in_array($enrollment->status, ['removed', 'inactive_due_to_absence', 'inactive_due_to_teacher'])) {
            return back()->with('info', 'الطالب ليس مزالاً من المجموعة.');
        }

        $enrollment->restoreActive('مساعد');

        ActivityLog::log([
            'actor_id' => auth('assistant')->id(),
            'actor_type' => 'assistant',
            'actor_type_label' => 'مساعد',
            'action' => 'student_restored',
            'category' => 'student',
            'description' => "تم استرجاع الطالب {$enrollment->student->name} (بواسطة مساعد)",
            'owner_id' => $this->teacherId(),
            'owner_type' => 'user',
        ]);

        TeacherActivity::log($this->teacherId(), 'student_restored', 'استرجاع طالب', "تم استرجاع الطالب {$enrollment->student->name} (بواسطة مساعد)");

        return back()->with('success', 'تم استرجاع الطالب إلى المجموعة');
    }

    /* ============================================================= */
    /* GROUPS (can_view_groups)                                     */
    /* ============================================================= */

    public function groupsIndex(): View
    {
        $groups = StudyGroup::where('teacher_id', $this->teacherId())
            ->withCount('enrollments')
            ->latest()
            ->get();

        return view('assistant.groups.index', [
            'header' => 'مجموعات المدرس',
            'groups' => $groups,
        ]);
    }

    /* ============================================================= */
    /* LESSONS (can_manage_lessons)                                 */
    /* ============================================================= */

    public function lessonsIndex(): View
    {
        $lessons = \App\Models\Lesson::forTeacher($this->teacherId())
            ->with('group')
            ->ordered()
            ->get();

        return view('assistant.lessons.index', [
            'header' => 'الدروس',
            'lessons' => $lessons,
        ]);
    }

    public function lessonsCreate(): View
    {
        $groups = StudyGroup::where('teacher_id', $this->teacherId())
            ->orderBy('title')
            ->get();

        return view('assistant.lessons.create', [
            'header' => 'إضافة درس',
            'groups' => $groups,
        ]);
    }

    public function lessonsStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'group_id' => ['required', 'exists:study_groups,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'display_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $group = StudyGroup::where('id', $validated['group_id'])
            ->where('teacher_id', $this->teacherId())
            ->firstOrFail();

        $order = $validated['display_order'] ?? 0;
        $maxOrder = \App\Models\Lesson::where('teacher_id', $this->teacherId())
            ->where('group_id', $validated['group_id'])
            ->max('display_order');
        $order = max(0, $order, ($maxOrder ?? -1) + 1);

        $lesson = \App\Models\Lesson::create([
            'teacher_id' => $this->teacherId(),
            'group_id' => $validated['group_id'],
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'display_order' => $order,
        ]);

        ActivityLog::log([
            'actor_id' => auth('assistant')->id(),
            'actor_type' => 'assistant',
            'actor_type_label' => 'مساعد',
            'action' => 'lesson_created',
            'category' => 'lesson',
            'description' => "تم إنشاء درس {$lesson->title} (بواسطة مساعد)",
            'owner_id' => $this->teacherId(),
            'owner_type' => 'user',
        ]);

        TeacherActivity::log($this->teacherId(), 'lesson_created', 'إنشاء درس', "تم إنشاء درس {$lesson->title} (بواسطة مساعد)");

        return redirect()->route('assistant.lessons.index')->with('success', 'تم إنشاء الدرس بنجاح');
    }

    public function lessonsEdit(\App\Models\Lesson $lesson): View
    {
        $this->authorizeLesson($lesson);

        $groups = StudyGroup::where('teacher_id', $this->teacherId())
            ->orderBy('title')
            ->get();

        return view('assistant.lessons.edit', [
            'header' => 'تعديل درس',
            'lesson' => $lesson,
            'groups' => $groups,
        ]);
    }

    public function lessonsUpdate(Request $request, \App\Models\Lesson $lesson): RedirectResponse
    {
        $this->authorizeLesson($lesson);

        $validated = $request->validate([
            'group_id' => ['required', 'exists:study_groups,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'display_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $group = StudyGroup::where('id', $validated['group_id'])
            ->where('teacher_id', $this->teacherId())
            ->firstOrFail();

        $lesson->update([
            'group_id' => $validated['group_id'],
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'display_order' => $validated['display_order'] ?? 0,
            'is_active' => $request->boolean('is_active'),
        ]);

        ActivityLog::log([
            'actor_id' => auth('assistant')->id(),
            'actor_type' => 'assistant',
            'actor_type_label' => 'مساعد',
            'action' => 'lesson_updated',
            'category' => 'lesson',
            'description' => "تم تعديل درس {$lesson->title} (بواسطة مساعد)",
            'owner_id' => $this->teacherId(),
            'owner_type' => 'user',
        ]);

        TeacherActivity::log($this->teacherId(), 'lesson_updated', 'تعديل درس', "تم تعديل درس {$lesson->title} (بواسطة مساعد)");

        return redirect()->route('assistant.lessons.index')->with('success', 'تم تحديث الدرس بنجاح');
    }

    public function lessonsDestroy(\App\Models\Lesson $lesson): RedirectResponse
    {
        $this->authorizeLesson($lesson);

        $lesson->resources()->update(['lesson_id' => null]);
        $lesson->delete();

        ActivityLog::log([
            'actor_id' => auth('assistant')->id(),
            'actor_type' => 'assistant',
            'actor_type_label' => 'مساعد',
            'action' => 'lesson_deleted',
            'category' => 'lesson',
            'description' => "تم حذف درس {$lesson->title} (بواسطة مساعد)",
            'owner_id' => $this->teacherId(),
            'owner_type' => 'user',
        ]);

        TeacherActivity::log($this->teacherId(), 'lesson_deleted', 'حذف درس', "تم حذف درس {$lesson->title} (بواسطة مساعد)");

        return redirect()->route('assistant.lessons.index')->with('success', 'تم حذف الدرس بنجاح');
    }

    /* ============================================================= */
    /* PROFILE (assistant)                                          */
    /* ============================================================= */

    public function profileEdit(): View
    {
        $assistant = auth('assistant')->user();

        return view('assistant.profile.edit', [
            'header' => 'الملف الشخصي',
            'assistant' => $assistant,
        ]);
    }

    public function profileUpdate(Request $request): RedirectResponse
    {
        $assistant = auth('assistant')->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        $assistant->update($validated);

        ActivityLog::log([
            'actor_id' => $assistant->id,
            'actor_type' => 'assistant',
            'actor_type_label' => 'مساعد',
            'action' => 'profile_updated',
            'category' => 'profile',
            'description' => 'تم تحديث الملف الشخصي للمساعد',
            'owner_id' => $this->teacherId(),
            'owner_type' => 'user',
        ]);

        TeacherActivity::log($this->teacherId(), 'assistant_profile_updated', 'تعديل ملف مساعد', 'تم تحديث الملف الشخصي لمساعد');

        return back()->with('success', 'تم تحديث الملف الشخصي.');
    }

    public function passwordUpdate(Request $request): RedirectResponse
    {
        $assistant = auth('assistant')->user();

        $validated = $request->validate([
            'current_password' => ['required', 'current_password:assistant'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $assistant->update([
            'password' => $validated['password'],
        ]);

        ActivityLog::log([
            'actor_id' => $assistant->id,
            'actor_type' => 'assistant',
            'actor_type_label' => 'مساعد',
            'action' => 'password_changed',
            'category' => 'profile',
            'description' => 'تم تغيير كلمة المرور للمساعد',
            'owner_id' => $this->teacherId(),
            'owner_type' => 'user',
        ]);

        TeacherActivity::log($this->teacherId(), 'assistant_password_changed', 'تغيير كلمة مرور مساعد', 'تم تغيير كلمة المرور لمساعد');

        return back()->with('success', 'تم تغيير كلمة المرور بنجاح.');
    }

    /* ============================================================= */
    /* AUTHORIZATION HELPERS                                        */
    /* ============================================================= */

    private function authorizeSession(AttendanceSession $session): void
    {
        if ($session->teacher_id !== $this->teacherId()) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الجلسة');
        }
    }

    private function authorizeEnrollment(GroupEnrollment $enrollment): void
    {
        if ($enrollment->group->teacher_id !== $this->teacherId()) {
            abort(403, 'غير مصرح لك بالوصول إلى هذا الطالب');
        }
    }

    private function authorizeLesson(\App\Models\Lesson $lesson): void
    {
        if ($lesson->teacher_id !== $this->teacherId()) {
            abort(403, 'غير مصرح لك بالوصول إلى هذا الدرس');
        }
    }

    private function authorizeExam(Exam $exam): void
    {
        if ($exam->teacher_id !== $this->teacherId()) {
            abort(403, 'غير مصرح لك بالوصول إلى هذا الامتحان');
        }
    }

    private function authorizeQuestionInBank(QuestionBank $question): void
    {
        if ($question->teacher_id !== $this->teacherId()) {
            abort(403, 'غير مصرح لك بالوصول إلى هذا السؤال');
        }
    }
}