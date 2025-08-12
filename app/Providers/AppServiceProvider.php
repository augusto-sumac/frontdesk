<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

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
        // Forçar HTTPS em produção para evitar mixed content atrás de proxy (Railway)
        if (env('APP_ENV') === 'production') {
            $appUrl = config('app.url');
            if ($appUrl) {
                URL::forceRootUrl($appUrl);
            }
            URL::forceScheme('https');
        }
    }
}
