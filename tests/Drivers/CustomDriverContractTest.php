<?php

namespace Laraversine\Test\Drivers;

use Laraversine\Destination;
use Laraversine\Drivers\SqlDriver;
use Laraversine\Facades\Laraversine;
use Laraversine\Test\Support\CountingDriver;
use PHPUnit\Framework\Attributes\Test;

/**
 * Proves a third-party driver registered with Laraversine::extend() is used,
 * and passes the same contract, without any change to the package.
 */
final class CustomDriverContractTest extends SearchDriverContract
{
    protected function driverName(): string
    {
        return 'delegating';
    }

    protected function registerDrivers(): void
    {
        Laraversine::extend('delegating', fn () => new CountingDriver(new SqlDriver));
    }

    #[Test]
    public function the_custom_driver_is_the_one_in_use(): void
    {
        CountingDriver::$calls = 0;

        Destination::nearby([0, 0], 1)->get();

        $this->assertSame(1, CountingDriver::$calls);
    }
}
