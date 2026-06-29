<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class StudentDashboardController extends Controller
{
    public function index(): View
    {
        return view('student.dashboard', [
            'header' => 'لوحة الطالب',
        ]);
    }
}
