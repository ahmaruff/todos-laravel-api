<?php

namespace App\Providers;

use App\Services\AgentService;
use App\Services\ExceptionHandlerService;
use App\Services\LogService;
use App\Services\TodoService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(LogService::class, function ($app) {
            return new LogService(
                agentService: $app->make(AgentService::class)
            );
        });

        $this->app->bind(ExceptionHandlerService::class, function ($app) {
            return new ExceptionHandlerService(
                logService: $app->make(LogService::class)
            );
        });

        $this->app->singleton(TodoService::class, function($app) {
            return new TodoService(
                logService: $app->make(LogService::class)
            );
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
