<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\ExamQuestion;
use App\Models\QuestionBank;
use App\Models\StudyGroup;
use App\Models\TeacherActivity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QuestionBankController extends Controller
{
    public function index(Request $request): View
    {
        $teacherId = auth()->id();

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

        return view('teacher.question-bank.index', [
            'header' => 'بنك الأسئلة',
            'questions' => $questions,
            'lessons' => $lessons,
            'groups' => $groups,
            'filters' => $request->only(['type', 'difficulty', 'lesson_id', 'group_id', 'search']),
        ]);
    }

    public function create(): View
    {
        $lessons = \App\Models\Lesson::forTeacher(auth()->id())->orderBy('display_order')->get();
        $groups = StudyGroup::where('teacher_id', auth()->id())->orderBy('title')->get();

        return view('teacher.question-bank.create', [
            'header' => 'إضافة سؤال جديد',
            'lessons' => $lessons,
            'groups' => $groups,
        ]);
    }

    public function store(Request $request): RedirectResponse
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

        $validated['teacher_id'] = auth()->id();
        $validated['status'] = 'active';

        $question = QuestionBank::create($validated);

        TeacherActivity::log(auth()->id(), 'question_created', 'إنشاء سؤال', "تم إنشاء سؤال في بنك الأسئلة");

        return redirect()->route('teacher.question-bank.index')
            ->with('success', 'تمت إضافة السؤال إلى بنك الأسئلة.');
    }

    public function edit(QuestionBank $question): View
    {
        $this->authorizeQuestion($question);

        $lessons = \App\Models\Lesson::forTeacher(auth()->id())->orderBy('display_order')->get();
        $groups = StudyGroup::where('teacher_id', auth()->id())->orderBy('title')->get();

        return view('teacher.question-bank.edit', [
            'header' => 'تعديل السؤال',
            'question' => $question,
            'lessons' => $lessons,
            'groups' => $groups,
        ]);
    }

    public function update(Request $request, QuestionBank $question): RedirectResponse
    {
        $this->authorizeQuestion($question);

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

        TeacherActivity::log(auth()->id(), 'question_updated', 'تعديل سؤال', "تم تعديل سؤال في بنك الأسئلة");

        return redirect()->route('teacher.question-bank.index')
            ->with('success', 'تم تحديث السؤال بنجاح.');
    }

    public function destroy(QuestionBank $question): RedirectResponse
    {
        $this->authorizeQuestion($question);

        $question->delete();

        TeacherActivity::log(auth()->id(), 'question_deleted', 'حذف سؤال', "تم حذف سؤال من بنك الأسئلة");

        return back()->with('success', 'تم حذف السؤال.');
    }

    public function recycleBin(): View
    {
        $deleted = QuestionBank::onlyTrashed()
            ->where('teacher_id', auth()->id())
            ->latest()
            ->paginate(20);

        return view('teacher.recycle-bin.index', [
            'header' => 'سلة المحذوفات - بنك الأسئلة',
            'items' => $deleted,
            'type' => 'question_bank',
        ]);
    }

    public function restore(QuestionBank $question): RedirectResponse
    {
        $this->authorizeQuestion($question);

        if ($question->trashed()) {
            $question->restore();
        }

        TeacherActivity::log(auth()->id(), 'question_restored', 'استرجاع سؤال', "تم استرجاع سؤال من بنك الأسئلة");

        return back()->with('success', 'تم استرجاع السؤال بنجاح');
    }

    public function forceDelete(QuestionBank $question): RedirectResponse
    {
        $this->authorizeQuestion($question);

        if ($question->trashed()) {
            $question->forceDelete();
        }

        TeacherActivity::log(auth()->id(), 'question_permanent_deleted', 'حذف نهائي سؤال', "تم حذف سؤال نهائياً من بنك الأسئلة");

        return back()->with('success', 'تم الحذف النهائي بنجاح');
    }

    public function pickForExam(Request $request, Exam $exam): View
    {
        $this->authorizeExam($exam);

        $teacherId = auth()->id();
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

        return view('teacher.question-bank.picker', [
            'header' => 'اختيار سؤال من البنك',
            'exam' => $exam,
            'questions' => $questions,
            'lessons' => $lessons,
            'groups' => $groups,
            'filters' => $request->only(['type', 'difficulty', 'lesson_id', 'group_id', 'search']),
        ]);
    }

    public function attachToExam(Request $request, Exam $exam): RedirectResponse
    {
        $this->authorizeExam($exam);

        $validated = $request->validate([
            'question_id' => ['required', 'exists:question_bank,id'],
            'points' => ['nullable', 'integer', 'min:1'],
        ]);

        $bankQuestion = QuestionBank::findOrFail($validated['question_id']);

        $this->authorizeQuestion($bankQuestion);

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

        return back()->with('success', 'تمت إضافة السؤال إلى الامتحان.');
    }

    private function authorizeQuestion(QuestionBank $question): void
    {
        if ($question->teacher_id !== auth()->id()) {
            abort(403);
        }
    }

    private function authorizeExam(Exam $exam): void
    {
        if ($exam->teacher_id !== auth()->id()) {
            abort(403);
        }
    }
}