<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AcademicTerm;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class TeacherRegistrationController extends Controller
{
    /**
     * Show the teacher registration form.
     */
    public function create(): View
    {
        return view('auth.register-teacher');
    }

    /**
     * Handle a teacher registration request.
     * New teachers are created with role=teacher and status=pending,
     * and a unique, non-editable teacher code is generated.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'mobile' => ['required', 'string', 'max:20', 'unique:'.User::class.',mobile'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'mobile' => $validated['mobile'],
            'email' => 'teacher_' . $validated['mobile'] . '@placeholder.local',
            'password' => Hash::make($validated['password']),
            'role' => 'teacher',
            'status' => 'pending',
            'teacher_code' => User::generateTeacherCode(),
        ]);

        // Create the default first term for this teacher (2026 / First Term / active).
        AcademicTerm::createDefaultFor($user->id);

        event(new Registered($user));

        return redirect()->route('login')->with('status',
            'تم تسجيل طلبك بنجاح، سيتم تفعيل حسابك بعد مراجعته من الإدارة');
    }
}