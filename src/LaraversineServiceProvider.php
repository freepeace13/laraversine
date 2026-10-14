<?php

namespace Laraversine;

use Illuminate\Support\ServiceProvider;

class LaraversineServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/laraversine.php' => config_path('laraversine.php'),
            ], 'laraversine-config');

            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'laraversine-migrations');
        }
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/laraversine.php', 'laraversine');
    }
}
