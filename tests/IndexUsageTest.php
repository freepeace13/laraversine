<?php

namespace Laraversine\Test;

use Illuminate\Support\Facades\DB;
use Laraversine\Test\Support\Store;
use PHPUnit\Framework\Attributes\Test;

class IndexUsageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (DB::connection()->getDriverName() === 'sqlite') {
            $this->markTestSkipped('Index usage is asserted on MySQL, MariaDB and PostgreSQL.');
        }
    }

    #[Test]
    public function a_small_radius_search_uses_the_latitude_longitude_index(): void
    {
        mt_srand(99);
        $rows = [];

        for ($i = 0; $i < 20000; $i++) {
            $rows[] = [
                'name' => "s{$i}",
                'lat' => round(rad2deg(asin(2 * mt_rand() / mt_getrandmax() - 1)), 6),
                'lng' => round(360 * mt_rand() / mt_getrandmax() - 180, 6),
            ];
        }

        foreach (array_chunk($rows, 1000) as $chunk) {
            Store::insert($chunk);
        }

        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('ANALYZE stores');
            // Let the planner prefer the index even though the table is small.
            DB::statement('SET enable_seqscan = off');
        } else {
            DB::select('ANALYZE TABLE stores');
        }

        $query = Store::nearby([51.5, -0.12], 1.0);
        $plan = DB::select('EXPLAIN '.($driver === 'pgsql' ? '(FORMAT JSON) ' : '').$query->toSql(), $query->getBindings());

        if ($driver === 'pgsql') {
            $this->assertMatchesRegularExpression('/Index (Only )?Scan|Bitmap Index Scan/', json_encode($plan));

            return;
        }

        $row = (array) $plan[0];
        $this->assertSame('range', $row['type']);
        $this->assertNotNull($row['key']);
    }
}
