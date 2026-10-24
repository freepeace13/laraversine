<?php

namespace Laraversine\Test\Drivers;

use Laraversine\Coordinates;
use Laraversine\Distance;
use Laraversine\Drivers\GeohashCover;
use Laraversine\Geohash;
use Laraversine\Test\Support\BruteForce;
use Laraversine\Test\Support\DatasetFactory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class GeohashCoverTest extends TestCase
{
    #[Test]
    public function it_picks_the_longest_prefix_at_least_the_radius(): void
    {
        $small = GeohashCover::around(new Coordinates(7.1, 125.6), 1.0, Distance::KM);
        $larger = GeohashCover::around(new Coordinates(7.1, 125.6), 5.0, Distance::KM);

        $this->assertSame(5, $small->precision);
        $this->assertSame(4, $larger->precision);

        foreach ([$small, $larger] as $cover) {
            $this->assertGreaterThanOrEqual(1, count($cover->ranges));
            $this->assertLessThanOrEqual(9, count($cover->ranges));

            foreach ($cover->ranges as [$low, $high]) {
                $this->assertSame(12, strlen($low));
                $this->assertSame(12, strlen($high));
                $this->assertLessThan($high, $low);
            }
        }
    }

    #[Test]
    public function it_returns_null_when_the_radius_is_too_large(): void
    {
        $this->assertNull(GeohashCover::around(new Coordinates(0, 0), 6000.0, Distance::KM));
    }

    #[Test]
    public function it_returns_null_near_a_pole(): void
    {
        $this->assertNull(GeohashCover::around(new Coordinates(89.95, 10), 5.0, Distance::KM));
    }

    #[Test]
    public function it_returns_null_across_the_antimeridian(): void
    {
        $this->assertNull(GeohashCover::around(new Coordinates(0, 179.99), 5.0, Distance::KM));
    }

    #[Test]
    public function it_uses_the_configured_unit(): void
    {
        $km = GeohashCover::around(new Coordinates(7.1, 125.6), 1.0, Distance::KM);
        $mile = GeohashCover::around(new Coordinates(7.1, 125.6), 1.0, Distance::MILE);

        $this->assertLessThanOrEqual($km->precision, $mile->precision);
    }

    #[Test]
    public function every_point_within_the_radius_falls_in_a_range(): void
    {
        $rows = array_merge(
            DatasetFactory::uniform(2000, 9),
            DatasetFactory::cluster(2000, 8, 7.1, 125.6, 0.5),
        );

        $stored = [];
        $hashes = [];

        foreach ($rows as $id => $row) {
            $stored[] = ['id' => $id, 'latitude' => $row['latitude'], 'longitude' => $row['longitude']];
            $hashes[$id] = Geohash::encode($row['latitude'], $row['longitude']);
        }

        $checked = 0;

        foreach (array_slice($rows, 2000, 100) as $origin) {
            foreach ([0.5, 5.0, 50.0, 500.0] as $radius) {
                $cover = GeohashCover::around(new Coordinates($origin['latitude'], $origin['longitude']), $radius, Distance::KM);

                if ($cover === null) {
                    continue;
                }

                $inside = BruteForce::within($stored, [$origin['latitude'], $origin['longitude']], $radius);

                foreach (array_keys($inside) as $id) {
                    $covered = array_filter($cover->ranges, fn (array $range) => $hashes[$id] >= $range[0] && $hashes[$id] <= $range[1]);

                    $this->assertNotEmpty($covered, "row {$id} within {$radius} km of ".json_encode([$origin['latitude'], $origin['longitude']]));
                    $checked++;
                }
            }
        }

        $this->assertGreaterThan(100, $checked);
    }
}
