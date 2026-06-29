<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class TeacherDashboardController extends Controller
{
    public function index(): View
    {
        return view('teacher.dashboard', [
            'header' => 'لوحة المدرس',
        ]);
    }
}
