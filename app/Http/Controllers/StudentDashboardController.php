<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class StudentDashboardController extends Controller
{
    public function index(): View
    {
        $account = auth()->user()->activeStudentAccount();

        return view('student.dashboard', [
            'header' => 'لوحة الطالب',
            'account' => $account,
        ]);
    }
}