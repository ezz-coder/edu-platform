<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    public function index(): View
    {
        $users = User::latest()->get();

        return view('admin.users.index', [
            'header' => 'إدارة المستخدمين',
            'users' => $users,
        ]);
    }

    public function approve(User $user): RedirectResponse
    {
        $user->update(['status' => 'active']);

        return back()->with('success', 'تم تفعيل المستخدم بنجاح');
    }

    public function reject(User $user): RedirectResponse
    {
        $user->update(['status' => 'rejected']);

        return back()->with('success', 'تم رفض المستخدم بنجاح');
    }

    public function suspend(User $user): RedirectResponse
    {
        $user->update(['status' => 'suspended']);

        return back()->with('success', 'تم تعليق المستخدم بنجاح');
    }
}
