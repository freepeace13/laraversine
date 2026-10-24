<?php

namespace Laraversine\Drivers;

use Illuminate\Database\Eloquent\Builder;
use Laraversine\Contracts\SearchDriver;
use Laraversine\Coordinates;
use Laraversine\Haversine;

/**
 * Narrows candidates with prefix ranges on a stored geohash column, then
 * finishes with the exact haversine check. Searches the cover cannot handle
 * reliably go to the fallback driver.
 */
final class GeohashDriver implements SearchDriver
{
    public function __construct(private SqlDriver $fallback) {}

    public function applyRadius(Builder $query, Coordinates $origin, float $radius, string $unit): Builder
    {
        $cover = GeohashCover::around($origin, $radius, $unit);

        if ($cover === null) {
            return $this->fallback->applyRadius($query, $origin, $radius, $unit);
        }

        Haversine::applyExact($query, $origin, $radius, $unit);

        $column = $query->getQuery()->getGrammar()->wrap($query->getModel()->getQualifiedGeohashColumn());
        $clauses = array_fill(0, count($cover->ranges), "{$column} BETWEEN ? AND ?");

        return $query->whereRaw('('.implode(' OR ', $clauses).')', array_merge(...$cover->ranges));
    }
}
