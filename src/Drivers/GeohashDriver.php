<?php

namespace Laraversine\Drivers;

use Illuminate\Database\Eloquent\Builder;
use Laraversine\Contracts\SearchDriver;
use Laraversine\Coordinates;
use Laraversine\GeohashColumn;
use Laraversine\Haversine;

/**
 * Narrows candidates with prefix ranges on a stored geohash column, then
 * finishes with the exact haversine check. Rows with coordinates but no hash
 * stay candidates, so a stale column never hides a nearby row; when there are
 * too many of them, and for searches the cover cannot handle reliably, the
 * fallback driver runs instead.
 */
final class GeohashDriver implements SearchDriver
{
    public function __construct(private SqlDriver $fallback, private int $nullLimit) {}

    public function applyRadius(Builder $query, Coordinates $origin, float $radius, string $unit): Builder
    {
        GeohashColumn::assertFor($query->getModel());

        $cover = GeohashCover::around($origin, $radius, $unit);

        if ($cover === null || $this->hasTooManyNullHashes($query)) {
            return $this->fallback->applyRadius($query, $origin, $radius, $unit);
        }

        Haversine::applyExact($query, $origin, $radius, $unit);

        $grammar = $query->getQuery()->getGrammar();
        $column = $grammar->wrap($query->getModel()->getQualifiedGeohashColumn());
        [$lat, $lng] = Haversine::columns($query);

        $clauses = array_fill(0, count($cover->ranges), "{$column} BETWEEN ? AND ?");

        // The coordinate checks keep rows without coordinates out: PostgreSQL's
        // LEAST ignores NULL arguments, which would turn them into matches.
        $clauses[] = "({$column} IS NULL AND {$lat} IS NOT NULL AND {$lng} IS NOT NULL)";

        return $query->whereRaw('('.implode(' OR ', $clauses).')', array_merge(...$cover->ranges));
    }

    /**
     * More than $nullLimit rows have coordinates but no hash. The probe stops
     * counting one row past the limit, so it stays cheap on a large table.
     */
    private function hasTooManyNullHashes(Builder $query): bool
    {
        $model = $query->getModel();
        $connection = $query->getConnection();

        $probe = $connection->table($model->getTable())
            ->select($model->getKeyName())
            ->whereNull($model->getGeohashColumn())
            ->whereNotNull($model->getLatitudeColumn())
            ->whereNotNull($model->getLongitudeColumn())
            ->limit($this->nullLimit + 1);

        return $connection->query()->fromSub($probe, 'null_hashes')->count() > $this->nullLimit;
    }
}
