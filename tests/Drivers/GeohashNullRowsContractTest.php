<?php

namespace Laraversine\Test\Drivers;

use Laraversine\Test\Support\DatasetFactory;

/**
 * The same contract with every third row missing its hash, as if it had been
 * written behind the model hook.
 */
final class GeohashNullRowsContractTest extends SearchDriverContract
{
    protected function driverName(): string
    {
        return 'geohash';
    }

    protected function seedDataset(array $rows): void
    {
        $hashed = [];
        $unhashed = [];

        foreach ($rows as $index => $row) {
            if ($index % 3 === 2) {
                $unhashed[] = $row;
            } else {
                $hashed[] = $row;
            }
        }

        DatasetFactory::seed($hashed, true);
        DatasetFactory::seed($unhashed);
    }
}
