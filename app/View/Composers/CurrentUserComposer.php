<?php

namespace App\View\Composers;

use Illuminate\View\View;

class CurrentUserComposer
{
    public function compose(View $view): void
    {
        $webUser = auth()->user();
        $assistantUser = auth('assistant')->user();
        
        $currentUser = null;
        $currentGuard = null;
        
        if ($webUser) {
            $currentUser = $webUser;
            $currentGuard = 'web';
        } elseif ($assistantUser) {
            $currentUser = $assistantUser;
            $currentGuard = 'assistant';
        }
        
        $view->with(compact('currentUser', 'currentGuard'));
    }
}
