<?php

namespace Namnb\Authorization\Middlewares;

use Illuminate\Support\ServiceProvider;

class CheckAdmin extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/config/permission.php', 'permission');
        // nếu chạy trong console thì n vào đây, không chạy thì false
        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
            $this->publicFile();
        }
    }

    public function boot()
    {
    }

    public function publicFile()
    {
        $this->publishes([
            __DIR__ . '/database/migrations' => base_path('database/migrations')
        ], 'permission');

        $this->publishes([
            __DIR__ . '/database/migrations' => base_path('database/migrations')
        ], 'laravel-assets');
    }
}
