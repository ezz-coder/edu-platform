<?php

namespace App\Http\Controllers;

use App\Models\StudyGroup;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentGroupController extends Controller
{
    public function index(Request $request): View
    {
        // Students can only see active groups.
        // Sprint 69: optional filtering by study stage and gender type.
        $stage = trim((string) $request->query('stage', ''));
        $gender = trim((string) $request->query('gender', ''));

        $groups = StudyGroup::where('status', 'active')
            ->with('teacher')
            ->when($stage !== '', fn ($q) => $q->where('grade_level', $stage))
            ->when($gender !== '', fn ($q) => $q->where('gender_type', $gender))
            ->latest()
            ->get();

        $stages = StudyGroup::where('status', 'active')
            ->select('grade_level')
            ->distinct()
            ->orderBy('grade_level')
            ->pluck('grade_level');

        return view('student.groups.index', [
            'header' => 'المجموعات المتاحة',
            'groups' => $groups,
            'stages' => $stages,
            'filterStage' => $stage,
            'filterGender' => $gender,
        ]);
    }
}
