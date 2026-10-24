<?php

namespace Laraversine\Console;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Laraversine\Concerns\HasGeolocation;
use Laraversine\Coordinates;
use Laraversine\Geohash;
use Laraversine\GeohashColumn;

/**
 * Recomputes a model's geohash column from its coordinates: the backfill for
 * existing rows, and the repair for rows changed without model events.
 */
final class GeohashCommand extends Command
{
    protected $signature = 'laraversine:geohash
        {model : The model class, for example App\\Models\\Store}
        {--verify : Report missing or stale geohashes without writing}
        {--chunk=1000 : Rows read per batch}';

    protected $description = 'Backfill or verify the geohash column of a model';

    public function handle(): int
    {
        $class = (string) $this->argument('model');

        if (! is_a($class, Model::class, true) || ! in_array(HasGeolocation::class, class_uses_recursive($class), true)) {
            $this->error("{$class} must be an Eloquent model that uses ".HasGeolocation::class.'.');

            return self::FAILURE;
        }

        /** @var Model&HasGeolocation $model */
        $model = new $class;

        if (! GeohashColumn::exists($model)) {
            $this->error("The \"{$model->getTable()}\" table has no \"{$model->getGeohashColumn()}\" column.");

            return self::FAILURE;
        }

        $verify = (bool) $this->option('verify');
        $key = $model->getKeyName();
        $mismatched = [];
        $total = 0;
        $count = 0;

        $model->newQueryWithoutScopes()
            ->select([$key, $model->getLatitudeColumn(), $model->getLongitudeColumn(), $model->getGeohashColumn()])
            ->chunkById(max(1, (int) $this->option('chunk')), function ($rows) use ($model, $key, $verify, &$mismatched, &$total, &$count) {
                foreach ($rows as $row) {
                    $total++;
                    $expected = $this->expectedHash($row, $model);

                    if ($row->getAttribute($model->getGeohashColumn()) === $expected) {
                        continue;
                    }

                    $count++;

                    if (count($mismatched) < 10) {
                        $mismatched[] = $row->getKey();
                    }

                    if (! $verify) {
                        // Through the query builder: no model events, no updated_at change.
                        $model->newQueryWithoutScopes()->toBase()
                            ->where($key, $row->getKey())
                            ->update([$model->getGeohashColumn() => $expected]);
                    }
                }
            }, $key);

        if ($verify) {
            if ($count === 0) {
                $this->info("All {$total} geohashes match their coordinates.");

                return self::SUCCESS;
            }

            $this->error("{$count} rows have a missing or stale geohash (of {$total}). First ids: ".implode(', ', $mismatched));

            return self::FAILURE;
        }

        $this->info("Repaired {$count} of {$total} rows.");

        return self::SUCCESS;
    }

    private function expectedHash(Model $row, Model $model): ?string
    {
        try {
            $coordinates = Coordinates::from([
                $row->getAttribute($model->getLatitudeColumn()),
                $row->getAttribute($model->getLongitudeColumn()),
            ]);
        } catch (InvalidArgumentException) {
            return null;
        }

        return Geohash::encode($coordinates->latitude, $coordinates->longitude);
    }
}
