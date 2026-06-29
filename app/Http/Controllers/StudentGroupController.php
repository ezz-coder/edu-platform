<?php

namespace App\Http\Controllers;

use App\Models\StudyGroup;
use Illuminate\View\View;

class StudentGroupController extends Controller
{
    public function index(): View
    {
        // Students can only see active groups
        // In Sprint 4, we'll add filtering based on student's grade_level and gender_type
        // For now, show all active groups
        $groups = StudyGroup::where('status', 'active')
            ->with('teacher')
            ->latest()
            ->get();

        return view('student.groups.index', [
            'header' => 'المجموعات المتاحة',
            'groups' => $groups,
        ]);
    }
}
