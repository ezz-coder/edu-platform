<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

/**
 * Sprint 68 — Two-Factor Authentication (Email OTP) for teachers.
 *
 * Flow:
 *  - Teacher enables 2FA from settings (requires current password).
 *  - On login, after password verification, an OTP is generated and
 *    "emailed" to the teacher's email address; the code is stored hashed
 *    in cache for 10 minutes and never logged or displayed in UI.
 *  - The user is redirected to the challenge screen; correct OTP completes
 *    the login. Wrong/expired OTP is rejected.
 *  - Disable requires the current password while logged in.
 */
class TwoFactorController extends Controller
{
    private const TTL_MINUTES = 10;

    public static function issueCode(User $user): void
    {
        $code = (string) random_int(100000, 999999);

        Cache::put(
            self::key($user->id),
            Hash::make($code),
            now()->addMinutes(self::TTL_MINUTES)
        );

        Mail::to($user->email)->send(new \App\Mail\TwoFactorCode($code, self::TTL_MINUTES));
    }

    private static function key(int $userId): string
    {
        return "2fa:otp:{$userId}";
    }

    public function showChallenge(): View|RedirectResponse
    {
        if (! session('two_factor:id')) {
            return redirect()->route('login');
        }

        return view('auth.two-factor-challenge');
    }

    public function verifyChallenge(Request $request): RedirectResponse
    {
        $userId = session('two_factor:id');
        if (! $userId) {
            return redirect()->route('login');
        }

        $data = $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        $cached = Cache::pull(self::key((int) $userId));

        if (! $cached || ! Hash::check($data['code'], $cached)) {
            // Wrong or expired code: force full re-login.
            $request->session()->forget('two_factor:id');

            return back()->withErrors(['code' => 'كود التحقق غير صحيح أو منتهي الصلاحية، يرجى تسجيل الدخول مرة أخرى']);
        }

        $user = User::find($userId);
        if (! $user || $user->role !== 'teacher' || $user->status !== 'active') {
            $request->session()->forget('two_factor:id');

            return redirect()->route('login');
        }

        $request->session()->forget('two_factor:id');
        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('teacher.dashboard'));
    }

    public function enable(Request $request): RedirectResponse
    {
        $user = auth()->user();
        abort_unless($user && $user->role === 'teacher', 403);

        $data = $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user->update(['two_factor_enabled' => true]);

        return back()->with('success', 'تم تفعيل التحقق بخطوتين (رمز يُرسل إلى بريدك عند كل دخول)');
    }

    public function disable(Request $request): RedirectResponse
    {
        $user = auth()->user();
        abort_unless($user && $user->role === 'teacher', 403);

        $data = $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user->update(['two_factor_enabled' => false]);
        Cache::forget(self::key($user->id));

        return back()->with('success', 'تم تعطيل التحقق بخطوتين');
    }
}