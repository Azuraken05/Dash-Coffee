<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL; // <-- 1. Add this import at the top

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // 2. Add this inside boot() so asset() generates https:// links on Vercel
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}