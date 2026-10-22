<?php

namespace Laraversine\Contracts;

use Illuminate\Database\Eloquent\Builder;
use Laraversine\Coordinates;

interface SearchDriver
{
    /**
     * Add a "distance" select (in $unit) and restrict the query to rows within
     * $radius of the origin.
     *
     * An implementation may pre-filter with an index, but must finish with an
     * exact distance check: it must never return a row only because it falls
     * inside a bounding box or grid cell.
     */
    public function applyRadius(Builder $query, Coordinates $origin, float $radius, string $unit): Builder;
}
