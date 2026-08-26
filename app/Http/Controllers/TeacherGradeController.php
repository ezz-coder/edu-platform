<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\GroupEnrollment;
use App\Models\StudyGroup;
use App\Models\TeacherActivity;
use App\Utilities\NotificationHelper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\View\View;

/**
 * Sprint 61 — Teacher Grades Module.
 *
 * Grades are the exam scores (score/total_marks) stored on ExamAttempt.
 * The teacher can view and edit grades of students in his own groups only.
 * Editing is allowed even after publishing (publishing does not make grades final).
 */
class TeacherGradeController extends Controller
{
    /**
     * List all exams (with finished attempts) for the teacher's grades page.
     */
    public function index(): View
    {
        $exams = Exam::where('teacher_id', auth()->id())
            ->withCount(['attempts as finished_attempts' => fn ($q) => $q->where('status', 'finished')])
            ->withCount(['attempts as total_attempts'])
            ->latest()
            ->paginate(15);

        $totalExams = Exam::where('teacher_id', auth()->id())->count();
        $totalGrades = ExamAttempt::where('status', 'finished')
            ->whereHas('exam', fn ($q) => $q->where('teacher_id', auth()->id()))
            ->count();

        return view('teacher.grades.index', [
            'header' => 'الدرجات',
            'exams' => $exams,
            'totalExams' => $totalExams,
            'totalGrades' => $totalGrades,
        ]);
    }

    /**
     * Show all students with their grades for a specific exam, with inline editing.
     */
    public function show(Request $request, Exam $exam): View
    {
        $this->authorizeExam($exam);

        $groups = StudyGroup::where('teacher_id', auth()->id())->orderBy('title')->get();

        // Targeted students = students enrolled in any target group of this exam.
        $targetGroupIds = $exam->targetGroups()->pluck('group_id');
        $targetedStudentIds = GroupEnrollment::whereIn('group_id', $targetGroupIds)
            ->where('status', 'enrolled')
            ->distinct('student_id')
            ->pluck('student_id');

        // Finished attempts (grades) for this exam.
        $query = ExamAttempt::where('exam_id', $exam->id)
            ->where('status', 'finished')
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

        $attempts = $query->orderByDesc('finished_at')->paginate(20)->withQueryString();

        // Stats.
        $stats = [
            'targeted' => $targetedStudentIds->count(),
            'graded' => ExamAttempt::where('exam_id', $exam->id)->where('status', 'finished')->count(),
            'avg' => ExamAttempt::where('exam_id', $exam->id)->where('status', 'finished')->avg('score'),
            'max' => ExamAttempt::where('exam_id', $exam->id)->where('status', 'finished')->max('score'),
        ];

        return view('teacher.grades.show', [
            'header' => $exam->title,
            'exam' => $exam,
            'groups' => $groups,
            'attempts' => $attempts,
            'stats' => $stats,
            'filters' => [
                'search' => $request->input('search'),
                'group_id' => $request->input('group_id'),
            ],
        ]);
    }

    /**
     * Update a student's grade (score) for an exam.
     * Editing is allowed even after publishing.
     */
    public function update(Request $request, ExamAttempt $attempt): RedirectResponse
    {
        $this->authorizeExam($attempt->exam);

        $validated = $request->validate([
            'score' => ['required', 'integer', 'min:0', 'max:' . ($attempt->total_marks ?: 100)],
        ]);

        $attempt->update([
            'score' => $validated['score'],
        ]);

        // Sprint 65: notify only the affected student that his grade was edited.
        NotificationHelper::notifyStudents(
            auth()->id(),
            [$attempt->student_id],
            'grade_updated',
            'تم تعديل درجتك',
            "تم تعديل درجتك في امتحان {$attempt->exam->title} إلى {$validated['score']}/{$attempt->total_marks}.",
            route('student.grades')
        );

        TeacherActivity::log(
            auth()->id(),
            'grade_updated',
            'تعديل درجة',
            "تم تعديل درجة الطالب {$attempt->student->name} في امتحان {$attempt->exam->title} إلى {$validated['score']}/{$attempt->total_marks}"
        );

        return back()->with('success', 'تم تحديث الدرجة بنجاح.');
    }

    /**
     * Sprint 66 — Export the exam's grades to CSV.
     * Only the teacher's own exam data is exported. The same filters as the
     * grades page (search / group) are respected, and values match the
     * Grades Module exactly (score/total_marks from finished attempts).
     */
    public function export(Request $request, Exam $exam): StreamedResponse
    {
        $this->authorizeExam($exam);

        $targetGroupIds = $exam->targetGroups()->pluck('group_id');

        // Only students enrolled in the exam's target groups (teacher's own
        // groups) are exported — no out-of-scope student data.
        $query = ExamAttempt::where('exam_id', $exam->id)
            ->where('status', 'finished')
            ->whereHas('student.enrollments', function ($q) use ($targetGroupIds) {
                $q->whereIn('group_id', $targetGroupIds)->where('status', 'enrolled');
            })
            ->with(['student', 'student.enrollments' => fn ($q) => $q->where('status', 'enrolled')->whereIn('group_id', $targetGroupIds)]);

        if ($search = $request->input('search')) {
            $query->whereHas('student', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('student_code', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%");
            });
        }

        if ($groupId = $request->input('group_id')) {
            $query->whereHas('student', fn ($q) => $q->whereHas('enrollments', fn ($q2) => $q2->where('group_id', $groupId)->where('status', 'enrolled')));
        }

        $attempts = $query->orderByDesc('finished_at')->get();

        $filename = 'grades-' . preg_replace('/[^\p{L}\p{N}]+/u', '-', $exam->title) . '.csv';

        return Response::streamDownload(function () use ($attempts) {
            $out = fopen('php://output', 'w');
            // UTF-8 BOM so Excel renders Arabic correctly.
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['كود الطالب', 'الاسم', 'الجوال', 'المجموعة', 'الدرجة', 'الدرجة الكلية', 'النسبة %', 'تاريخ الإنهاء']);

            foreach ($attempts as $a) {
                $enr = $a->student->enrollments->first();
                $pct = $a->total_marks > 0 ? round(($a->score / $a->total_marks) * 100, 1) : 0;

                fputcsv($out, [
                    $a->student->student_code,
                    $a->student->name,
                    $a->student->mobile,
                    $enr?->group?->title ?? '',
                    $a->score,
                    $a->total_marks,
                    $pct,
                    optional($a->finished_at)->format('Y-m-d H:i'),
                ]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function authorizeExam(Exam $exam): void
    {
        if ($exam->teacher_id !== auth()->id()) {
            abort(403);
        }
    }
}