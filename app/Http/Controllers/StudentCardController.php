<?php

namespace App\Http\Controllers;

use App\Models\GroupEnrollment;
use App\Models\StudyGroup;
use App\Utilities\QrCodeGenerator;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentCardController extends Controller
{
    /**
     * Student's own card page (View / Print actions).
     * The QR permanently identifies the student account only.
     */
    public function studentShow(Request $request): View
    {
        $account = auth()->user()->activeStudentAccount();
        $enrollment = $account->enrollments()
            ->with(['group', 'group.teacher', 'term'])
            ->where('status', '!=', 'inactive_due_to_teacher')
            ->first();

        if (! $enrollment) {
            abort(404, 'لا يوجد تسجيل نشط لعرض البطاقة');
        }

        $qr = $this->qrSvg($enrollment);

        return view('student.card', [
            'header' => 'بطاقة الطالب',
            'account' => $account,
            'enrollment' => $enrollment,
            'qr' => $qr,
        ]);
    }

    /**
     * Teacher view of a single student card (printable).
     */
    public function teacherShow(GroupEnrollment $enrollment): View
    {
        $this->authorizeEnrollment($enrollment);

        $enrollment->load(['student', 'group', 'group.teacher', 'term']);
        $qr = $this->qrSvg($enrollment);

        return view('teacher.students.card', [
            'enrollment' => $enrollment,
            'qr' => $qr,
        ]);
    }

    /**
     * Print all student cards for a teacher's group (ordered like the list).
     */
    public function printGroup(StudyGroup $group): View
    {
        $this->authorizeGroup($group);

        $enrollments = $group->enrollments()
            ->with(['student', 'group', 'group.teacher', 'term'])
            ->orderBy('created_at')
            ->get();

        $cards = $enrollments->map(function (GroupEnrollment $e) {
            return [
                'enrollment' => $e,
                'qr' => $this->qrSvg($e),
            ];
        });

        return view('teacher.students.cards-print', [
            'group' => $group,
            'cards' => $cards,
        ]);
    }

    private function qrSvg(GroupEnrollment $enrollment): string
    {
        // The QR encodes the permanent token identifying the student account.
        return (new QrCodeGenerator())->renderSvg($enrollment->qr_token, 320, 4);
    }

    private function authorizeEnrollment(GroupEnrollment $enrollment): void
    {
        if ($enrollment->group->teacher_id !== auth()->id()) {
            abort(403, 'غير مصرح لك بالوصول إلى هذا الطالب');
        }
    }

    private function authorizeGroup(StudyGroup $group): void
    {
        if ($group->teacher_id !== auth()->id()) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه المجموعة');
        }
    }
}