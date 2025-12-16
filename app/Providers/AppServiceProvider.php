<?php

namespace App\Providers;

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
        // // Force JSON responses for all API requests
        // $this->app->bind(
        //     \Illuminate\Auth\Middleware\Authenticate::class,
        //     function ($app) {
        //         return new class extends \Illuminate\Auth\Middleware\Authenticate {
        //             protected function redirectTo($request)
        //             {
        //                 return null;
        //             }
        //         };
        //     }
        // );
    }
}
