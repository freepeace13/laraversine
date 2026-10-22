<?php

namespace Laraversine;

use Illuminate\Database\Query\Builder;
use WeakMap;

/**
 * Remembers the origin a query was built around, so nearestFirst() and
 * within() can refer back to it. Keyed on the query builder and weakly held,
 * so entries disappear with their queries.
 *
 * @internal
 */
final class Origins
{
    /** @var WeakMap<Builder, array{0: Coordinates, 1: string}>|null */
    private static ?WeakMap $origins = null;

    public static function remember(Builder $query, Coordinates $origin, string $unit): void
    {
        self::$origins ??= new WeakMap;
        self::$origins[$query] = [$origin, $unit];
    }

    /**
     * @return array{0: Coordinates, 1: string}|null
     */
    public static function get(Builder $query): ?array
    {
        return self::$origins[$query] ?? null;
    }
}
