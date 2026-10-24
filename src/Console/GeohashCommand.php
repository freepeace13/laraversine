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
    /**
     * Degrees. The database rounds coordinates to the column's precision, so a
     * hash made from the value that was assigned can sit one cell over from a
     * hash made from the stored value. That is not a stale hash.
     */
    private const ROUNDING_TOLERANCE = 1e-6;

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
            ->chunkById(max(1, (int) $this->option('chunk')), function ($rows) use ($model, $verify, &$mismatched, &$total, &$count) {
                $pending = [];

                foreach ($rows as $row) {
                    $total++;
                    $coordinates = $this->coordinates($row, $model);
                    $expected = $coordinates === null ? null : Geohash::encode($coordinates->latitude, $coordinates->longitude);

                    if ($this->isCurrent($row->getAttribute($model->getGeohashColumn()), $expected, $coordinates)) {
                        continue;
                    }

                    $count++;

                    if (count($mismatched) < 10) {
                        $mismatched[] = $row->getKey();
                    }

                    $pending[$row->getKey()] = $expected;
                }

                if (! $verify) {
                    $this->write($model, $pending);
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

    /**
     * One UPDATE for the rows getting a hash and one for the rows losing it,
     * through the connection and not the model: no model events, no
     * updated_at change.
     *
     * @param  array<array-key, string|null>  $hashes  primary key => expected hash
     */
    private function write(Model $model, array $hashes): void
    {
        $connection = $model->getConnection();
        $grammar = $connection->getQueryGrammar();
        $table = $grammar->wrapTable($model->getTable());
        $column = $grammar->wrap($model->getGeohashColumn());
        $key = $grammar->wrap($model->getKeyName());

        $set = array_filter($hashes, fn (?string $hash) => $hash !== null);
        $clear = array_keys(array_diff_key($hashes, $set));

        if ($set !== []) {
            $bindings = [];

            foreach ($set as $id => $hash) {
                array_push($bindings, $id, $hash);
            }

            $connection->update(
                "update {$table} set {$column} = case {$key} ".implode(' ', array_fill(0, count($set), 'when ? then ?'))
                ." end where {$key} in (".implode(', ', array_fill(0, count($set), '?')).')',
                [...$bindings, ...array_keys($set)],
            );
        }

        if ($clear !== []) {
            $connection->update(
                "update {$table} set {$column} = null where {$key} in (".implode(', ', array_fill(0, count($clear), '?')).')',
                $clear,
            );
        }
    }

    private function coordinates(Model $row, Model $model): ?Coordinates
    {
        try {
            return Coordinates::from([
                $row->getAttribute($model->getLatitudeColumn()),
                $row->getAttribute($model->getLongitudeColumn()),
            ]);
        } catch (InvalidArgumentException) {
            return null;
        }
    }

    private function isCurrent(?string $stored, ?string $expected, ?Coordinates $coordinates): bool
    {
        if ($expected === null) {
            return $stored === null;
        }

        if ($stored === $expected) {
            return true;
        }

        // Only a full-length hash can match the index ranges, so a truncated one is stale.
        return $stored !== null
            && strlen($stored) === Geohash::LENGTH
            && Geohash::contains($stored, $coordinates->latitude, $coordinates->longitude, self::ROUNDING_TOLERANCE);
    }
}
