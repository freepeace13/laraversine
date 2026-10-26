<?php

namespace Laraversine\Test\Drivers;

use Laraversine\Test\Support\DatasetFactory;

final class GeohashDriverContractTest extends SearchDriverContract
{
    protected function driverName(): string
    {
        return 'geohash';
    }

    protected function seedDataset(array $rows): void
    {
        DatasetFactory::seed($rows, true);
    }
}
