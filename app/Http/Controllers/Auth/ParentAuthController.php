<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;

use App\Models\ParentAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

/**
 * Sprint 68 — Parent authentication.
 *
 * A parent registers with his mobile number; he is automatically linked
 * to every student whose parent_mobile matches. Login is mobile + password
 * on a dedicated guard. Parent access is strictly read-only.
 */
class ParentAuthController extends Controller
{
    public function showLogin(): View
    {
        return view('parent.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'mobile' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $parent = ParentAccount::where('mobile', $credentials['mobile'])->first();

        if (! $parent || ! Hash::check($credentials['password'], $parent->password)) {
            return back()->withErrors(['mobile' => 'بيانات الدخول غير صحيحة'])->onlyInput('mobile');
        }

        if ($parent->status !== 'active') {
            return back()->withErrors(['mobile' => 'الحساب غير مفعل'])->onlyInput('mobile');
        }

        Auth::guard('parent')->login($parent, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->route('parent.dashboard');
    }

    public function showRegister(): View
    {
        return view('parent.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'mobile' => ['required', 'string', 'max:20'],
            'password' => ['required', 'confirmed', \Illuminate\Validation\Rules\Password::defaults()],
        ]);

        if (ParentAccount::where('mobile', $data['mobile'])->exists()) {
            return back()->withErrors(['mobile' => 'يوجد حساب بهذا الرقم بالفعل'])->onlyInput('name');
        }

        // Linking rule: at least one student must carry this parent_mobile.
        $linkedStudents = \App\Models\User::where('role', 'student')
            ->where('parent_mobile', $data['mobile'])
            ->count();

        if ($linkedStudents === 0) {
            return back()->withErrors(['mobile' => 'لا يوجد طلاب مرتبطون برقم ولي الأمر هذا'])
                ->onlyInput('name');
        }

        $parent = ParentAccount::create($data);
        Auth::guard('parent')->login($parent);
        $request->session()->regenerate();

        return redirect()->route('parent.dashboard')->with('success', 'تم إنشاء حساب ولي الأمر');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('parent')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('parent.login');
    }
}