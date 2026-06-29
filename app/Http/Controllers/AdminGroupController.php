<?php

namespace App\Http\Controllers;

use App\Models\StudyGroup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminGroupController extends Controller
{
    public function index(Request $request): View
    {
        $query = StudyGroup::with('teacher')->latest();

        if ($request->has('teacher_id') && $request->teacher_id) {
            $query->where('teacher_id', $request->teacher_id);
        }

        $groups = $query->get();
        $teachers = \App\Models\User::where('role', 'teacher')->where('status', 'active')->get();

        return view('admin.groups.index', [
            'header' => 'إدارة المجموعات',
            'groups' => $groups,
            'teachers' => $teachers,
            'selectedTeacher' => $request->teacher_id,
        ]);
    }

    public function toggleStatus(StudyGroup $group): RedirectResponse
    {
        $group->update([
            'status' => $group->status === 'active' ? 'inactive' : 'active',
        ]);

        return back()->with('success', 'تم تحديث حالة المجموعة بنجاح');
    }
}
