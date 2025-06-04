<?php

namespace App\Providers;

use App\Services\AgentService;
use App\Services\ExceptionHandlerService;
use App\Services\LogService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(LogService::class, function ($app) {
            return new LogService(
                agentService: $app->make(AgentService::class)
            );
        });

        $this->app->singleton(ExceptionHandlerService::class, function($app) {
            return new ExceptionHandlerService($app->make(LogService::class));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
