<?php

namespace Laraversine;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use LogicException;

/**
 * Checks that a model's table has its geohash column, once per process.
 *
 * @internal
 */
final class GeohashColumn
{
    /** @var array<string, true> */
    private static array $known = [];

    public static function exists(Model $model): bool
    {
        $key = $model->getConnectionName().'|'.$model->getTable().'|'.$model->getGeohashColumn();

        if (isset(self::$known[$key])) {
            return true;
        }

        if (! Schema::connection($model->getConnectionName())->hasColumn($model->getTable(), $model->getGeohashColumn())) {
            return false;
        }

        return self::$known[$key] = true;
    }

    public static function assertFor(Model $model): void
    {
        if (! self::exists($model)) {
            throw new LogicException(sprintf(
                '%s uses the geohash driver, but its table "%s" has no "%s" column. Add the column (see the README) or use another driver.',
                $model::class,
                $model->getTable(),
                $model->getGeohashColumn(),
            ));
        }
    }

    public static function forget(): void
    {
        self::$known = [];
    }
}
