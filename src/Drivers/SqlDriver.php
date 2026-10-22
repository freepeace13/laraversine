<?php

namespace Laraversine\Drivers;

use Illuminate\Database\Eloquent\Builder;
use Laraversine\Contracts\SearchDriver;
use Laraversine\Coordinates;
use Laraversine\Haversine;

final class SqlDriver implements SearchDriver
{
    public function applyRadius(Builder $query, Coordinates $origin, float $radius, string $unit): Builder
    {
        [$expression, $bindings] = Haversine::forQuery($query, $origin, $unit);

        if (empty($query->getQuery()->columns)) {
            $query->select($query->getModel()->qualifyColumn('*'));
        }

        $query->selectRaw("{$expression} AS distance", $bindings);

        $box = BoundingBox::around($origin, $radius, $unit);

        if ($box !== null) {
            $this->applyBox($query, $box);
        } else {
            // Half the circumference or more: every row with coordinates
            // qualifies. The extra kilometre keeps float rounding from dropping
            // the antipode. NULLs are excluded explicitly because PostgreSQL's
            // LEAST ignores NULL arguments, which would turn them into matches.
            $radius = Haversine::halfCircumference($unit) + 1.0;

            [$lat, $lng] = Haversine::columns($query);
            $query->whereRaw("{$lat} IS NOT NULL AND {$lng} IS NOT NULL");
        }

        // Inlined (it is a typed float): PDO would bind it as a string, which
        // SQLite compares as greater than any number.
        return $query->whereRaw("{$expression} <= ".$this->literal($radius), $bindings);
    }

    private function applyBox(Builder $query, BoundingBox $box): void
    {
        [$lat, $lng] = Haversine::columns($query);

        $query->whereRaw("{$lat} BETWEEN {$this->literal($box->minLatitude)} AND {$this->literal($box->maxLatitude)}");

        if ($box->longitudeRanges === null) {
            return;
        }

        $clauses = array_map(
            fn (array $range) => "{$lng} BETWEEN {$this->literal($range[0])} AND {$this->literal($range[1])}",
            $box->longitudeRanges,
        );

        $query->whereRaw(count($clauses) === 1 ? $clauses[0] : '('.implode(' OR ', $clauses).')');
    }

    private function literal(float $value): string
    {
        return sprintf('%.8F', $value);
    }
}
