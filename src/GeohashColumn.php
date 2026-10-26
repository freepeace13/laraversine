<?php

namespace Laraversine;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use LogicException;

/**
 * Checks that a model's table has its geohash column. A column that exists is
 * remembered for the life of the process; a miss is remembered for a minute, so
 * a column added by a migration is picked up by workers that keep running.
 *
 * @internal
 */
final class GeohashColumn
{
    private const MISS_SECONDS = 60;

    /** @var array<string, true|int> true when found, else the timestamp of the miss */
    private static array $known = [];

    public static function exists(Model $model): bool
    {
        $key = $model->getConnectionName().'|'.$model->getTable().'|'.$model->getGeohashColumn();
        $known = self::$known[$key] ?? null;

        if ($known === true) {
            return true;
        }

        if ($known !== null && Carbon::now()->getTimestamp() - $known < self::MISS_SECONDS) {
            return false;
        }

        if (Schema::connection($model->getConnectionName())->hasColumn($model->getTable(), $model->getGeohashColumn())) {
            self::$known[$key] = true;

            return true;
        }

        self::$known[$key] = Carbon::now()->getTimestamp();

        return false;
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
