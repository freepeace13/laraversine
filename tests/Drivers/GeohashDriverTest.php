<?php

namespace Laraversine\Test\Drivers;

use Illuminate\Database\Eloquent\Model;
use Laraversine\Concerns\HasGeolocation;
use Laraversine\Destination;
use Laraversine\Test\Support\BruteForce;
use Laraversine\Test\Support\DatasetFactory;
use Laraversine\Test\TestCase;
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
}
