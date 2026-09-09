<?php

namespace App\Providers;

use App\Models\Locker;
use App\Policies\LockerPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
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
        Gate::policy(Locker::class, LockerPolicy::class);

        if ($appUrl = config('app.url')) {
            URL::forceRootUrl($appUrl);
            URL::forceScheme(parse_url($appUrl, PHP_URL_SCHEME) ?: 'http');
        }
    }
}
