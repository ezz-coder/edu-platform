<?php

namespace App\Http\Controllers;

use App\Models\Assistant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Sprint 18 — Assistant authentication (separate from teacher/student login).
 */
class AssistantAuthController extends Controller
{
    
    /**
     * Show the assistant login form.
     */
    public function showLogin(): View
    {
        return view('assistant.login');
    }

    /**
     * Handle an assistant login attempt (username + password).
     */
    public function login(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $credentials = [
            'username' => $validated['username'],
            'password' => $validated['password'],
        ];

        if (! Auth::guard('assistant')->attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withInput($request->only('username'))
                ->withErrors(['username' => 'بيانات الدخول غير صحيحة.']);
        }

        $assistant = Auth::guard('assistant')->user();

        // Removed (inactive) assistants are blocked from the platform.
        if (! $assistant->isActive()) {
            Auth::guard('assistant')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()
                ->withInput($request->only('username'))
                ->withErrors([
                    'username' => 'لم يعد لديك صلاحيات للوصول إلى هذه المنصة. يرجى التواصل مع المدرس.',
                ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('assistant.dashboard'));
    }

    /**
     * Assistant dashboard — shows only allowed sections.
     */
    public function dashboard(): View
    {
        $assistant = Auth::guard('assistant')->user();
        $teacher = $assistant->teacher;

        return view('assistant.dashboard', [
            'assistant' => $assistant,
            'teacher' => $teacher,
        ]);
    }

    /**
     * Log the assistant out.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('assistant')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('assistant.login');
    }
}