<?php

namespace Laraversine\Benchmarks;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laraversine\Destination;
use Laraversine\Geohash;
use Laraversine\Test\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Seeds data and times v1.1-style brute force against the v2 SQL driver.
 *
 *   DB_DRIVER=mysql composer bench
 *
 * Results are written to benchmarks/results/<driver>.md.
 */
class Benchmark extends TestCase
{
    /** @var list<array{0: string, 1: array}> */
    private array $captured = [];

    private bool $capturing = false;

    #[Test]
    public function run_benchmarks(): void
    {
        $driver = DB::connection()->getDriverName();
        $sizes = array_map('intval', explode(',', getenv('BENCH_SIZES') ?: '10000,100000,1000000'));
        $originCount = (int) (getenv('BENCH_ORIGINS') ?: 10);

        DB::listen(function ($query) {
            if ($this->capturing) {
                $this->captured[] = [$query->sql, $query->bindings];
            }
        });

        $lines = [];

        foreach ($sizes as $size) {
            foreach (['uniform', 'dense'] as $layout) {
                $this->seedRows($size, $layout);
                $origins = $this->origins($originCount);

                foreach ($this->scenarios() as [$label, $v1, $v2, $v3]) {
                    [$v1Ms, $v1Rows] = $this->measure($v1, $origins);
                    [$v2Ms, $v2Rows] = $this->measure($v2, $origins);
                    [$v3Ms, $v3Rows] = $this->measure($v3, $origins);

                    $lines[] = sprintf(
                        '| %s | %s | %s | %.1f | %.1f | %.1f | %s | %s | %s |',
                        number_format($size), $layout, $label, $v1Ms, $v2Ms, $v3Ms,
                        $v1Rows === null ? 'n/a' : number_format($v1Rows),
                        $v2Rows === null ? 'n/a' : number_format($v2Rows),
                        $v3Rows === null ? 'n/a' : number_format($v3Rows),
                    );
                    fwrite(STDERR, end($lines)."\n");
                }
            }
        }

        $report = "# Benchmark results: {$driver}\n\n"
            ."Median latency over {$originCount} random origins, in milliseconds. Rows examined is approximate.\n\n"
            ."| Rows | Layout | Query | v1 brute force (ms) | v2 SQL driver (ms) | v2 geohash driver (ms) | v1 rows examined | v2 rows examined | geohash rows examined |\n"
            ."|---|---|---|---|---|---|---|---|---|\n"
            .implode("\n", $lines)."\n";

        file_put_contents(__DIR__."/results/{$driver}.md", $report);

        $this->addToAssertionCount(1);
    }

    /**
     * @return list<array{0: string, 1: callable(array): mixed, 2: callable(array): mixed, 3: callable(array): mixed}>
     */
    private function scenarios(): array
    {
        $scenarios = [];

        foreach ([1, 10] as $take) {
            $scenarios[] = [
                "nearest(take: {$take})",
                fn (array $o) => $this->v1Nearest($o, $take),
                fn (array $o) => Destination::nearest($o, take: $take),
                fn (array $o) => $this->withDriver('geohash', fn () => Destination::nearest($o, take: $take)),
            ];
        }

        foreach ([1, 5, 50] as $radius) {
            $scenarios[] = [
                "nearby({$radius} km)",
                fn (array $o) => $this->v1Nearby($o, $radius),
                fn (array $o) => Destination::nearby($o, $radius)->nearestFirst()->get(),
                fn (array $o) => $this->withDriver('geohash', fn () => Destination::nearby($o, $radius)->nearestFirst()->get()),
            ];
        }

        return $scenarios;
    }

    private function withDriver(string $driver, callable $run): mixed
    {
        $previous = config('laraversine.driver');
        config(['laraversine.driver' => $driver]);

        try {
            return $run();
        } finally {
            config(['laraversine.driver' => $previous]);
        }
    }

    /** The v1.1 query: the law-of-cosines distance for every row, then sort. */
    private function v1Expression(array $o): array
    {
        $sqlite = DB::connection()->getDriverName() === 'sqlite';
        [$least, $greatest] = $sqlite ? ['min', 'max'] : ['least', 'greatest'];

        $expression = "(6371.0 * acos({$least}(1.0, {$greatest}(-1.0, "
            .'cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) '
            .'+ sin(radians(?)) * sin(radians(latitude))))))';

        return [$expression, [$o[0], $o[1], $o[0]]];
    }

    private function v1Nearest(array $o, int $take): array
    {
        [$expression, $bindings] = $this->v1Expression($o);

        return DB::select("SELECT destinations.*, {$expression} AS distance FROM destinations ORDER BY distance LIMIT {$take}", $bindings);
    }

    private function v1Nearby(array $o, float $radius): array
    {
        [$expression, $bindings] = $this->v1Expression($o);

        return DB::select(
            "SELECT destinations.*, {$expression} AS distance FROM destinations WHERE {$expression} <= {$radius} ORDER BY distance",
            array_merge($bindings, $bindings),
        );
    }

    /**
     * @param  callable(array): mixed  $run
     * @param  list<array{0: float, 1: float}>  $origins
     * @return array{0: float, 1: int|null} median milliseconds, rows examined (first origin)
     */
    private function measure(callable $run, array $origins): array
    {
        $times = [];

        foreach ($origins as $origin) {
            $start = hrtime(true);
            $run($origin);
            $times[] = (hrtime(true) - $start) / 1e6;
        }

        sort($times);

        return [$times[intdiv(count($times), 2)], $this->rowsExamined(fn () => $run($origins[0]))];
    }

    private function rowsExamined(callable $run): ?int
    {
        $driver = DB::connection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement('FLUSH STATUS');
            $run();

            $status = DB::select("SHOW SESSION STATUS WHERE Variable_name IN ('Handler_read_first','Handler_read_key','Handler_read_next','Handler_read_prev','Handler_read_rnd','Handler_read_rnd_next')");

            return (int) collect($status)->sum(fn ($row) => (int) $row->Value);
        }

        if ($driver === 'pgsql') {
            $this->captured = [];
            $this->capturing = true;
            $run();
            $this->capturing = false;

            $total = 0;
            foreach ($this->captured as [$sql, $bindings]) {
                $plan = json_decode(DB::selectOne('EXPLAIN (ANALYZE, FORMAT JSON) '.$sql, $bindings)->{'QUERY PLAN'}, true);
                $total += $this->scannedRows($plan[0]['Plan']);
            }

            return $total;
        }

        return null;
    }

    private function scannedRows(array $node): int
    {
        $rows = str_contains($node['Node Type'], 'Scan')
            ? (int) (($node['Actual Rows'] + ($node['Rows Removed by Filter'] ?? 0)) * $node['Actual Loops'])
            : 0;

        foreach ($node['Plans'] ?? [] as $child) {
            $rows += $this->scannedRows($child);
        }

        return $rows;
    }

    private function seedRows(int $size, string $layout): void
    {
        DB::table('destinations')->truncate();

        if (! $this->hasIndex()) {
            Schema::table('destinations', fn ($table) => $table->index(['latitude', 'longitude']));
        }

        mt_srand(2026);

        foreach (array_chunk(range(1, $size), 1000) as $chunk) {
            $rows = [];

            foreach ($chunk as $i) {
                [$lat, $lng] = $layout === 'uniform'
                    ? [rad2deg(asin(2 * mt_rand() / mt_getrandmax() - 1)), 360 * mt_rand() / mt_getrandmax() - 180]
                    : [33 + 5 * mt_rand() / mt_getrandmax(), 126 + 3.5 * mt_rand() / mt_getrandmax()];

                $lat = round($lat, 6);
                $lng = round($lng, 6);
                $rows[] = ['name' => "p{$i}", 'latitude' => $lat, 'longitude' => $lng, 'geohash' => Geohash::encode($lat, $lng)];
            }

            DB::table('destinations')->insert($rows);
        }

        if (in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement('ANALYZE TABLE destinations');
        } elseif (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ANALYZE destinations');
        }
    }

    private function hasIndex(): bool
    {
        return collect(Schema::getIndexes('destinations'))
            ->contains(fn ($index) => $index['columns'] === ['latitude', 'longitude']);
    }

    /** @return list<array{0: float, 1: float}> existing points, so every query finds something */
    private function origins(int $count): array
    {
        return DB::table('destinations')->inRandomOrder()->limit($count)->get(['latitude', 'longitude'])
            ->map(fn ($row) => [(float) $row->latitude, (float) $row->longitude])
            ->all();
    }
}
