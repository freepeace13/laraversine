<?php

namespace Laraversine\Test;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laraversine\Destination;
use Laraversine\LaraversineServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return [LaraversineServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $driver = getenv('DB_DRIVER') ?: 'sqlite';

        $connections = [
            'sqlite' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''],
            'mysql' => [
                'driver' => 'mysql',
                'host' => getenv('DB_HOST') ?: '127.0.0.1',
                'port' => getenv('DB_PORT') ?: '3306',
                'database' => getenv('DB_DATABASE') ?: 'laraversine_test',
                'username' => getenv('DB_USERNAME') ?: 'root',
                'password' => getenv('DB_PASSWORD') ?: 'root',
            ],
            'mariadb' => [
                'driver' => 'mariadb',
                'host' => getenv('DB_HOST') ?: '127.0.0.1',
                'port' => getenv('DB_PORT') ?: '3306',
                'database' => getenv('DB_DATABASE') ?: 'laraversine_test',
                'username' => getenv('DB_USERNAME') ?: 'root',
                'password' => getenv('DB_PASSWORD') ?: 'root',
            ],
            'pgsql' => [
                'driver' => 'pgsql',
                'host' => getenv('DB_HOST') ?: '127.0.0.1',
                'port' => getenv('DB_PORT') ?: '5432',
                'database' => getenv('DB_DATABASE') ?: 'laraversine_test',
                'username' => getenv('DB_USERNAME') ?: 'postgres',
                'password' => getenv('DB_PASSWORD') ?: 'root',
            ],
        ];

        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', $connections[$driver]);
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }

    protected function setUp(): void
    {
        parent::setUp();

        // SQLite has no math functions unless compiled with them; register them.
        $connection = $this->app['db']->connection();
        if ($connection->getDriverName() === 'sqlite') {
            foreach (['radians' => 'deg2rad', 'cos' => 'cos', 'sin' => 'sin', 'acos' => 'acos', 'asin' => 'asin', 'sqrt' => 'sqrt'] as $name => $fn) {
                $connection->getPdo()->sqliteCreateFunction($name, $fn, 1);
            }
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
