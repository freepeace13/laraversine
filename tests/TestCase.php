<?php

namespace Laraversine\Test;

use Laraversine\Destination;
use Laraversine\LaraversineServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [LaraversineServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }

    protected function setUp(): void
    {
        parent::setUp();

        // SQLite has no math functions unless compiled with them; register them.
        $pdo = $this->app['db']->connection()->getPdo();
        foreach (['radians' => 'deg2rad', 'cos' => 'cos', 'sin' => 'sin', 'acos' => 'acos'] as $name => $fn) {
            $pdo->sqliteCreateFunction($name, $fn, 1);
        }

        $this->insertDummyData();
    }

    protected function insertDummyData(): void
    {
        Destination::insert([
            ['name' => 'Dest 1', 'latitude' => 7.127341, 'longitude' => 125.629270],
            ['name' => 'Dest 2', 'latitude' => 7.126788, 'longitude' => 125.628540],
            ['name' => 'Dest 3', 'latitude' => 7.127725, 'longitude' => 125.627467],
            ['name' => 'Dest 4', 'latitude' => 7.126873, 'longitude' => 125.629871],
            ['name' => 'Dest 5', 'latitude' => 7.125042, 'longitude' => 125.634033],
            ['name' => 'Dest 6', 'latitude' => 7.128023, 'longitude' => 125.637467],
        ]);
    }
}
