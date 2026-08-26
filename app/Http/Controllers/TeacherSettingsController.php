<?php

namespace App\Http\Controllers;

use App\Models\AcademicTerm;
use Illuminate\View\View;

/**
 * Sprint 67 — Teacher Settings.
 *
 * The specification defines NO custom teacher settings module
 * ("الإعدادات العامة تكون عبر الأدمن فقط" — Section 14). This page therefore
 * reuses existing logic only:
 *  - Account info (name/email editable via the existing Profile page)
 *  - Password change via the existing authenticated password route
 *    (Workflow 20: Current → New → Confirm)
 *  - Read-only identity fields: mobile, teacher_code
 */
class TeacherSettingsController extends Controller
{
    public function index(): View
    {
        $teacher = auth()->user();

        $activeTerm = AcademicTerm::where('teacher_id', $teacher->id)
            ->where('status', 'active')
            ->first();

        return view('teacher.settings.index', [
            'header' => 'الإعدادات',
            'teacher' => $teacher,
            'activeTerm' => $activeTerm,
        ]);
    }
}