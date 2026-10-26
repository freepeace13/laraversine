<?php

namespace Laraversine\Drivers;

use Illuminate\Database\Eloquent\Builder;
use Laraversine\Contracts\SearchDriver;
use Laraversine\Coordinates;
use Laraversine\GeohashColumn;
use Laraversine\Haversine;
use Laraversine\SearchMemo;

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

        $clauses[] = "{$column} IS NULL";

        // The coordinate checks cover every branch, not only the NULL hash: a
        // row moved to NULL coordinates without the hook keeps its old hash, and
        // PostgreSQL's LEAST ignores NULL arguments, which would turn it into a match.
        return $query->whereRaw("{$lat} IS NOT NULL AND {$lng} IS NOT NULL AND (".implode(' OR ', $clauses).')', array_merge(...$cover->ranges));
    }

    /**
     * More than $nullLimit rows the query could return have coordinates but no
     * hash. The probe starts from the query's own constraints and scopes, so
     * other tenants' or deleted rows do not count, and it stops counting one
     * row past the limit, so it stays cheap on a large table.
     */
    private function hasTooManyNullHashes(Builder $query): bool
    {
        $model = $query->getModel();

        $probe = (clone $query)->applyScopes()->getQuery()
            ->cloneWithout(['columns', 'orders', 'groups', 'havings', 'limit', 'offset', 'unions', 'unionLimit', 'unionOffset', 'unionOrders'])
            ->cloneWithoutBindings(['select', 'order', 'groupBy', 'having', 'union', 'unionOrder']);

        // Group the caller's wheres, so an orWhere in them cannot take over the
        // conditions added below.
        $callers = $probe->forNestedWhere();
        $callers->wheres = $probe->wheres;
        $callers->setBindings($probe->getRawBindings()['where'], 'where');
        $probe->wheres = [];
        $probe->setBindings([], 'where');
        $probe->addNestedWhereQuery($callers);

        $probe->select($model->qualifyColumn($model->getKeyName()))
            ->whereNull($model->getQualifiedGeohashColumn())
            ->whereNotNull($model->getQualifiedLatitudeColumn())
            ->whereNotNull($model->getQualifiedLongitudeColumn())
            ->limit($this->nullLimit + 1);

        $connection = $query->getConnection();
        $key = implode('|', [$connection->getName(), $probe->toSql(), json_encode($probe->getBindings())]);

        return SearchMemo::remember(
            $key,
            fn () => $connection->query()->fromSub($probe, 'null_hashes')->count() > $this->nullLimit,
        );
    }
}
