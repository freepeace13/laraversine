<?php

namespace Laraversine\Test\Drivers;

final class SqlDriverContractTest extends SearchDriverContract
{
    protected function driverName(): string
    {
        return 'sql';
    }
}
