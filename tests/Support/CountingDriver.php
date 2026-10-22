<?php

namespace Laraversine\Test\Support;

use Illuminate\Database\Eloquent\Builder;
use Laraversine\Contracts\SearchDriver;
use Laraversine\Coordinates;

final class CountingDriver implements SearchDriver
{
    public static int $calls = 0;

    public function __construct(private SearchDriver $inner) {}

    public function applyRadius(Builder $query, Coordinates $origin, float $radius, string $unit): Builder
    {
        self::$calls++;

        return $this->inner->applyRadius($query, $origin, $radius, $unit);
    }
}
