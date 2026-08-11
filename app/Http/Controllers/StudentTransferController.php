<?php

namespace App\Http\Controllers;

use App\Models\GroupEnrollment;
use App\Models\StudyGroup;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StudentTransferController extends Controller
{
    /**
     * Resolve the active student account from the session.
     * The session account is the single source of truth.
     */
    protected function account(): User
    {
        return auth()->user()->activeStudentAccount();
    }

    /**
     * Show the transfer request form for the current student's enrollment
     * under the authenticated teacher.
     */
    public function create(StudyGroup $group)
    {
        $account = $this->account();
        $enrollment = $account->activeEnrollmentForTeacher($group->teacher_id);

        if (! $enrollment || $enrollment->group_id !== $group->id) {
            abort(403);
        }

        $otherGroups = StudyGroup::where('teacher_id', $group->teacher_id)
            ->where('id', '!=', $group->id)
            ->where('status', 'active')
            ->get();

        return view('student.transfer', [
            'header' => 'طلب نقل مجموعة',
            'enrollment' => $enrollment,
            'otherGroups' => $otherGroups,
        ]);
    }

    /**
     * Store a transfer request. The transfer is NOT performed immediately;
     * it waits for teacher approval.
     */
    public function store(Request $request, StudyGroup $group): RedirectResponse
    {
        $account = $this->account();
        $enrollment = $account->activeEnrollmentForTeacher($group->teacher_id);

        if (! $enrollment || $enrollment->group_id !== $group->id) {
            abort(403);
        }

        $validated = $request->validate([
            'requested_group_id' => ['required', 'exists:study_groups,id'],
        ]);

        $target = StudyGroup::where('id', $validated['requested_group_id'])
            ->where('teacher_id', $group->teacher_id)
            ->where('status', 'active')
            ->firstOrFail();

        $enrollment->update([
            'requested_group_id' => $target->id,
            'status' => 'pending_transfer',
        ]);

        return redirect()->route('student.dashboard')
            ->with('success', 'تم إرسال طلب النقل، بانتظار موافقة المدرس');
    }
}