<?php

namespace App\View\Composers;

use App\Models\Assistant;
use Illuminate\View\View;

class AssistantComposer
{
    public function compose(View $view): void
    {
        $assistant = auth('assistant')->user();

        if ($assistant) {
            $teacher = $assistant->teacher;
            $view->with(compact('assistant', 'teacher', 'currentUser'));
        }
    }
}