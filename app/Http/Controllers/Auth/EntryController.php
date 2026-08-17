<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class EntryController extends Controller
{
    /**
     * Show the authentication entry page.
     *
     * The entry page is "auth-aware":
     * - If the user is already authenticated (web guard), redirect to their dashboard.
     * - If guest, show the role selector (Teacher / Student / Assistant).
     * Admin is NOT part of this entry — admin login is separate.
     */
    public function create(Request $request)
    {
        // Auth-aware: redirect authenticated users away from registration paths
        if ($request->user()) {
            $user = $request->user();

            return match ($user->role) {
                'teacher'  => redirect()->route('teacher.dashboard'),
                'student'  => redirect()->route('student.dashboard'),
                'assistant' => redirect()->route('assistant.dashboard'),
                default    => redirect()->route('auth.entry'),
            };
        }

        return view('auth.entry');
    }
}