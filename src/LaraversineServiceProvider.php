<?php

namespace Laraversine;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\ServiceProvider;
use Laraversine\Concerns\HasGeolocation;
use Laraversine\Console\GeohashCommand;
use LogicException;

class LaraversineServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Builder::macro('nearest', function (array $coordinates, int $take = 1, ?float $maxRadius = null) {
            /** @var Builder $this */
            if (! in_array(HasGeolocation::class, class_uses_recursive($this->getModel()), true)) {
                throw new LogicException(get_class($this->getModel()).' must use '.HasGeolocation::class.' to call nearest().');
            }

            return (new Nearest($this, Coordinates::from($coordinates), $take, $maxRadius))->get();
        });

        if ($this->app->runningInConsole()) {
            $this->commands([GeohashCommand::class]);

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

        $this->app->singleton(SearchManager::class, fn ($app) => new SearchManager($app));
    }
}
