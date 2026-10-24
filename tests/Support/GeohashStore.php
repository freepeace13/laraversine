<?php

namespace Laraversine\Test\Support;

/**
 * A store with custom coordinate and geohash columns that opts in to the
 * geohash driver on its own.
 */
class GeohashStore extends Store
{
    protected string $geolocationDriver = 'geohash';

    protected string $geohashColumn = 'hash';
}
