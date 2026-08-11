<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sprint 18 — Assistant authentication & permission guard.
 *
 * 1. `assistant` (no param): ensures an active assistant is logged in.
 * 2. `assistant:can_take_attendance` (param): additionally ensures the
 *    assistant holds the given permission. Unauthorized => 403.
 */
class AssistantMiddleware
{
    public function handle(Request $request, Closure $next, ?string $permission = null): Response
    {
        if (! auth('assistant')->check()) {
            return redirect()->route('assistant.login');
        }

        $assistant = auth('assistant')->user();

        // A removed (inactive) assistant can never perform any action.
        if (! $assistant->isActive()) {
            abort(403, 'لم يعد لديك صلاحيات للوصول إلى هذه المنصة. يرجى التواصل مع المدرس.');
        }

        // Optional explicit permission gate.
        if ($permission !== null && ! $assistant->canPerm($permission)) {
            abort(403, 'غير مصرح لك بتنفيذ هذا الإجراء');
        }

        return $next($request);
    }
}