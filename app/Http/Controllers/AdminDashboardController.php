<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function index(): View
    {
        $totalUsers = User::count();
        $pendingUsers = User::where('status', 'pending')->count();
        $activeUsers = User::where('status', 'active')->count();

        return view('admin.dashboard', [
            'header' => 'لوحة الأدمن',
            'totalUsers' => $totalUsers,
            'pendingUsers' => $pendingUsers,
            'activeUsers' => $activeUsers,
        ]);
    }
}
