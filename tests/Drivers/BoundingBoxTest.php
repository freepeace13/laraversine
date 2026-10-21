<?php

namespace Laraversine\Test\Drivers;

use Laraversine\Coordinates;
use Laraversine\Drivers\BoundingBox;
use Laraversine\Test\Support\BruteForce;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class BoundingBoxTest extends TestCase
{
    private const KM_PER_DEGREE = 111.19492664;

    private function box(float $lat, float $lng, float $radius): ?BoundingBox
    {
        return BoundingBox::around(Coordinates::from([$lat, $lng]), $radius, 'km');
    }

    #[Test]
    public function builds_a_symmetric_box_at_the_equator(): void
    {
        $box = $this->box(0, 0, self::KM_PER_DEGREE);

        $this->assertEqualsWithDelta(-1.0, $box->minLatitude, 0.001);
        $this->assertEqualsWithDelta(1.0, $box->maxLatitude, 0.001);
        $this->assertCount(1, $box->longitudeRanges);
        $this->assertEqualsWithDelta(-1.0, $box->longitudeRanges[0][0], 0.001);
        $this->assertEqualsWithDelta(1.0, $box->longitudeRanges[0][1], 0.001);
    }

    #[Test]
    public function returns_null_at_or_above_half_the_circumference(): void
    {
        $this->assertNull($this->box(10, 10, M_PI * 6371.0));
        $this->assertNull($this->box(10, 10, 25000));
    }

    #[Test]
    public function drops_the_longitude_bound_when_a_pole_is_inside(): void
    {
        $box = $this->box(89.9, 10, 50);

        $this->assertNull($box->longitudeRanges);
        $this->assertSame(90.0, $box->maxLatitude);
    }

    #[Test]
    public function drops_the_longitude_bound_for_an_origin_exactly_on_a_pole(): void
    {
        $box = $this->box(-90, 0, 10);

        $this->assertNull($box->longitudeRanges);
        $this->assertSame(-90.0, $box->minLatitude);
    }

    #[Test]
    public function uses_the_exact_longitude_extent_at_high_latitude(): void
    {
        // The naive r / (R cos phi) gives 51.8 degrees here; the true extent is 64.3.
        $box = $this->box(80, 0, 1000);

        $this->assertCount(1, $box->longitudeRanges);
        $this->assertGreaterThan(64.0, $box->longitudeRanges[0][1]);
        $this->assertLessThan(65.0, $box->longitudeRanges[0][1]);
    }

    #[Test]
    public function splits_the_range_when_crossing_the_antimeridian_eastwards(): void
    {
        $box = $this->box(0, 179.5, self::KM_PER_DEGREE);

        $this->assertCount(2, $box->longitudeRanges);
        $this->assertEqualsWithDelta(178.5, $box->longitudeRanges[0][0], 0.001);
        $this->assertSame(180.0, $box->longitudeRanges[0][1]);
        $this->assertSame(-180.0, $box->longitudeRanges[1][0]);
        $this->assertEqualsWithDelta(-179.5, $box->longitudeRanges[1][1], 0.001);
    }

    #[Test]
    public function splits_the_range_when_crossing_the_antimeridian_westwards(): void
    {
        $box = $this->box(0, -179.5, self::KM_PER_DEGREE);

        $this->assertCount(2, $box->longitudeRanges);
        $this->assertEqualsWithDelta(179.5, $box->longitudeRanges[0][0], 0.001);
        $this->assertSame(180.0, $box->longitudeRanges[0][1]);
        $this->assertSame(-180.0, $box->longitudeRanges[1][0]);
        $this->assertEqualsWithDelta(-178.5, $box->longitudeRanges[1][1], 0.001);
    }

    #[Test]
    public function every_point_within_the_radius_is_inside_the_box(): void
    {
        mt_srand(11);

        for ($i = 0; $i < 400; $i++) {
            $lat = $this->random(-89.9, 89.9);
            $lng = $this->random(-180, 180);
            $radius = $this->random(1, 6000);
            $box = $this->box($lat, $lng, $radius);

            if ($box === null) {
                continue;
            }

            $spread = min(180, ($radius / 111.0 / max(cos(deg2rad($lat)), 0.05)) * 1.3);

            for ($j = 0; $j < 60; $j++) {
                $pointLat = max(-90, min(90, $lat + $this->random(-$spread, $spread)));
                $pointLng = fmod($lng + $this->random(-$spread, $spread) + 540, 360) - 180;

                if (BruteForce::distance($lat, $lng, $pointLat, $pointLng) <= $radius) {
                    $this->assertTrue(
                        $this->inside($box, $pointLat, $pointLng),
                        "({$pointLat}, {$pointLng}) is within {$radius} km of ({$lat}, {$lng}) but outside its box"
                    );
                }
            }
        }
    }

    private function inside(BoundingBox $box, float $lat, float $lng): bool
    {
        if ($lat < $box->minLatitude || $lat > $box->maxLatitude) {
            return false;
        }

        if ($box->longitudeRanges === null) {
            return true;
        }

        foreach ($box->longitudeRanges as [$min, $max]) {
            if ($lng >= $min && $lng <= $max) {
                return true;
            }
        }

        return false;
    }

    private function random(float $min, float $max): float
    {
        return $min + (mt_rand() / mt_getrandmax()) * ($max - $min);
    }
}
