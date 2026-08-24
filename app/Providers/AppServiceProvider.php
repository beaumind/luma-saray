<?php

namespace App\Providers;

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
        // Behind the TLS-terminating ingress the app receives plain HTTP, so
        // Laravel would otherwise generate http:// asset/endpoint URLs. On an
        // https page the browser blocks those (mixed content) — which breaks
        // Livewire's script and makes forms fall back to a native POST (405).
        // Force https whenever the configured app URL is https.
        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }
    }
}
