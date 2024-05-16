<?php

namespace Namnb\Authorization\Providers;

use Illuminate\Support\ServiceProvider;

class AuthorizationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        dd('??');
        $this->mergeConfigFrom(__DIR__ . '/config/authorization.php', 'authorization');
        // nếu chạy trong console thì n vào đây, không chạy thì false
        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
            $this->publicFile();
        }
    }

    public function boot(): void
    {
        // Command
        if ($this->app->runningInConsole()) {
            $this->commands([
                UpdateRoleComman::class,
            ]);
        }
    }

    public function publicFile(): void
    {
        $this->publishes([
            __DIR__ . '/database/migrations' => database_path('database/migrations'),
            __DIR__ . '/database/migrations' => database_path('database/migrations')
        ]);
    }
}
