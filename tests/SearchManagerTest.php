<?php

namespace Laraversine\Test;

use InvalidArgumentException;
use Laraversine\Drivers\SqlDriver;
use Laraversine\Facades\Laraversine;
use Laraversine\SearchManager;
use Laraversine\Test\Support\CountingDriver;
use PHPUnit\Framework\Attributes\Test;

class SearchManagerTest extends TestCase
{
    #[Test]
    public function the_default_driver_is_sql(): void
    {
        $this->assertSame('sql', config('laraversine.driver'));
        $this->assertInstanceOf(SqlDriver::class, $this->app->make(SearchManager::class)->driver());
    }

    #[Test]
    public function the_manager_is_a_singleton(): void
    {
        $this->assertSame($this->app->make(SearchManager::class), $this->app->make(SearchManager::class));
    }

    #[Test]
    public function custom_drivers_can_be_registered_through_the_facade(): void
    {
        Laraversine::extend('counting', fn () => new CountingDriver(new SqlDriver));
        config(['laraversine.driver' => 'counting']);

        $this->assertInstanceOf(CountingDriver::class, $this->app->make(SearchManager::class)->driver());
    }

    #[Test]
    public function a_driver_can_be_requested_by_name(): void
    {
        $this->assertInstanceOf(SqlDriver::class, $this->app->make(SearchManager::class)->driver('sql'));
    }

    #[Test]
    public function an_unknown_driver_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->app->make(SearchManager::class)->driver('nope');
    }

    #[Test]
    public function nearest_defaults_are_configured(): void
    {
        $this->assertSame(1, config('laraversine.nearest.initial_radius'));
        $this->assertNull(config('laraversine.nearest.max_radius'));
    }
}
