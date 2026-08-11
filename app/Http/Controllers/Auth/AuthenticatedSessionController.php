<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = auth()->user();

        if ($user->status !== 'active') {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors([
                'email' => 'حسابك غير مفعل، برجاء انتظار الموافقة',
            ]);
        }

        // Students may have multiple independent accounts sharing the same mobile.
        if ($user->role === 'student') {
            $accounts = User::where('role', 'student')
                ->where('mobile', $user->mobile)
                ->count();

            if ($accounts > 1) {
                // Do not enter a dashboard; let the student pick an account.
                return redirect()->route('student.accounts');
            }

            // Single account: set it as the active account and continue.
            session(['active_student_id' => $user->id]);
        }

        return redirect()->intended(match ($user->role) {
            'admin' => route('admin.dashboard'),
            'teacher' => route('teacher.dashboard'),
            'student' => route('student.resources.home'),
            default => route('student.resources.home'),
        });
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->forget('active_student_id');
        $request->session()->regenerateToken();

        return redirect('/');
    }
}