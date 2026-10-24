<?php

namespace Laraversine\Test\Drivers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Laraversine\Concerns\HasGeolocation;
use Laraversine\Destination;
use Laraversine\Geohash;
use Laraversine\SearchManager;
use Laraversine\Test\Support\BruteForce;
use Laraversine\Test\Support\DatasetFactory;
use Laraversine\Test\Support\GeohashStore;
use Laraversine\Test\TestCase;
use LogicException;
use PHPUnit\Framework\Attributes\Test;

final class GeohashDriverTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('laraversine.driver', 'geohash');
    }

    protected function setUp(): void
    {
        parent::setUp();

        Destination::query()->delete();
    }

    #[Test]
    public function it_narrows_with_geohash_prefix_ranges(): void
    {
        $sql = Destination::nearby([7.1, 125.6], 1)->toRawSql();

        $this->assertStringContainsString('geohash', $sql);
        $this->assertStringContainsStringIgnoringCase('between', $sql);
    }

    #[Test]
    public function it_uses_miles_when_configured(): void
    {
        config(['laraversine.search_by' => 'mile']);

        DatasetFactory::seed(DatasetFactory::cluster(300, 5, 7.1, 125.6, 0.2), true);
        $stored = DatasetFactory::stored();

        foreach ([[7.1, 125.6], [7.2, 125.5]] as $origin) {
            foreach ([1.0, 3.0, 12.0] as $radius) {
                $expected = BruteForce::within($stored, $origin, $radius, 'mile');
                $actual = Destination::nearby($origin, $radius)->pluck('id')->all();

                $this->assertEqualsCanonicalizing(array_keys($expected), $actual, 'origin '.json_encode($origin)." radius {$radius}");
            }
        }
    }

    #[Test]
    public function it_falls_back_to_the_sql_driver_for_huge_radii(): void
    {
        $sql = Destination::nearby([7.1, 125.6], 6000)->toRawSql();

        $this->assertStringNotContainsString('geohash', $sql);
    }

    #[Test]
    public function the_geohash_column_defaults_and_honours_the_property(): void
    {
        $default = new class extends Model
        {
            use HasGeolocation;

            protected $table = 'destinations';
        };

        $custom = new class extends Model
        {
            use HasGeolocation;

            protected $table = 'destinations';

            protected $geohashColumn = 'hash';
        };

        $this->assertSame('geohash', $default->getGeohashColumn());
        $this->assertSame('destinations.geohash', $default->getQualifiedGeohashColumn());
        $this->assertSame('hash', $custom->getGeohashColumn());
    }

    #[Test]
    public function uses_geohash_follows_the_effective_driver(): void
    {
        $inherits = new class extends Model
        {
            use HasGeolocation;
        };

        $pinned = new class extends Model
        {
            use HasGeolocation;

            protected $geolocationDriver = 'sql';
        };

        $this->assertTrue($inherits->usesGeohash());
        $this->assertFalse($pinned->usesGeohash());

        config(['laraversine.driver' => 'sql']);

        $this->assertFalse($inherits->usesGeohash());
    }

    #[Test]
    public function rows_with_null_coordinates_are_never_returned(): void
    {
        GeohashStore::insert([
            ['name' => 'hashed', 'lat' => 7.1, 'lng' => 125.6, 'hash' => Geohash::encode(7.1, 125.6)],
            ['name' => 'nowhere', 'lat' => null, 'lng' => null, 'hash' => null],
        ]);

        foreach ([1.0, 5000.0] as $radius) {
            $names = GeohashStore::nearby([7.1, 125.6], $radius)->pluck('name')->all();

            $this->assertContains('hashed', $names, "radius {$radius}");
            $this->assertNotContains('nowhere', $names, "radius {$radius}");
        }
    }

    #[Test]
    public function null_coordinates_do_not_count_toward_the_null_limit(): void
    {
        $this->nullLimit(2);

        GeohashStore::insert(array_merge(
            [['name' => 'hashed', 'lat' => 7.1, 'lng' => 125.6, 'hash' => Geohash::encode(7.1, 125.6)]],
            array_fill(0, 5, ['name' => 'nowhere', 'lat' => null, 'lng' => null, 'hash' => null]),
        ));

        $this->assertStringContainsString('hash', GeohashStore::nearby([7.1, 125.6], 1)->toRawSql());
    }

    #[Test]
    public function too_many_null_hashes_fall_back(): void
    {
        $this->nullLimit(2);

        DatasetFactory::seed(DatasetFactory::cluster(30, 6, 7.1, 125.6, 0.05));
        $stored = DatasetFactory::stored();
        $origin = [7.1, 125.6];

        $expected = BruteForce::within($stored, $origin, 3.0);
        $query = Destination::nearby($origin, 3.0);

        $this->assertStringNotContainsString('geohash', $query->toRawSql());
        $this->assertEqualsCanonicalizing(array_keys($expected), $query->pluck('id')->all());
    }

    #[Test]
    public function the_null_probe_only_counts_rows_the_query_could_return(): void
    {
        $this->nullLimit(2);

        // Another tenant's rows, none of them hashed.
        DatasetFactory::seed(DatasetFactory::cluster(30, 6, 7.1, 125.6, 0.05));
        Destination::query()->update(['name' => 'tenant-b']);
        DatasetFactory::seed([['name' => 'tenant-a', 'latitude' => 7.1, 'longitude' => 125.6]], true);

        $sql = Destination::where('name', 'tenant-a')->nearby([7.1, 125.6], 3.0)->toRawSql();

        $this->assertStringContainsString('geohash', $sql);
    }

    #[Test]
    public function nearest_runs_the_null_probe_once_for_all_its_rounds(): void
    {
        DatasetFactory::seed([['name' => 'lonely', 'latitude' => -45.0, 'longitude' => 170.0]], true);

        $probes = 0;
        DB::listen(function ($query) use (&$probes) {
            $probes += str_contains($query->sql, 'null_hashes') ? 1 : 0;
        });

        $this->assertSame('lonely', Destination::nearest([60.0, -20.0])->name);
        $this->assertSame(1, $probes);
    }

    #[Test]
    public function a_missing_geohash_column_throws(): void
    {
        $regions = new class extends Model
        {
            use HasGeolocation;

            protected $table = 'regions';

            protected $geolocationDriver = 'geohash';
        };

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('geohash');

        $regions->newQuery()->nearby([7.1, 125.6], 1);
    }

    private function nullLimit(int $limit): void
    {
        config(['laraversine.geohash.null_limit' => $limit]);
        app(SearchManager::class)->forgetDrivers();
    }
}
