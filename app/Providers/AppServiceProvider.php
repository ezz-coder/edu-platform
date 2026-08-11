<?php

namespace App\Providers;

use App\Models\Assistant;
use App\Models\User;
use App\View\Composers\CurrentUserComposer;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (request()->header('X-Forwarded-Proto') === 'https') {
            URL::forceScheme('https');
        }

        View::composer('*', CurrentUserComposer::class);

        Relation::morphMap([
            'assistant' => Assistant::class,
            'teacher' => User::class,
        ]);
    }
}
