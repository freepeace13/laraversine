<?php

namespace Laraversine\Test;

use Laraversine\Geohash;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class GeohashTest extends TestCase
{
    private const EPSILON = 1e-9;

    #[Test]
    public function it_encodes_known_vectors(): void
    {
        $this->assertSame('u4pruydqqvj', Geohash::encode(57.64911, 10.40744, 11));
        $this->assertSame('ezs42', Geohash::encode(42.6, -5.6, 5));
    }

    #[Test]
    public function it_encodes_the_extreme_corners(): void
    {
        $this->assertSame('000000000000', Geohash::encode(-90, -180));
        $this->assertSame(str_repeat('z', 12), Geohash::encode(90, 180));
    }

    #[Test]
    public function bounds_contain_the_encoded_point(): void
    {
        mt_srand(11);

        for ($i = 0; $i < 500; $i++) {
            $lat = -90 + 180 * mt_rand() / mt_getrandmax();
            $lng = -180 + 360 * mt_rand() / mt_getrandmax();

            for ($length = 1; $length <= 12; $length++) {
                [$minLat, $maxLat, $minLng, $maxLng] = Geohash::bounds(Geohash::encode($lat, $lng, $length));

                $this->assertGreaterThanOrEqual($minLat, $lat, "lat length {$length}");
                $this->assertLessThanOrEqual($maxLat, $lat, "lat length {$length}");
                $this->assertGreaterThanOrEqual($minLng, $lng, "lng length {$length}");
                $this->assertLessThanOrEqual($maxLng, $lng, "lng length {$length}");
            }
        }
    }

    #[Test]
    public function cell_size_matches_the_bounds(): void
    {
        foreach ([1, 4, 5, 12] as $length) {
            [$height, $width] = Geohash::cellSize($length);
            [$minLat, $maxLat, $minLng, $maxLng] = Geohash::bounds(Geohash::encode(7.1, 125.6, $length));

            $this->assertEqualsWithDelta($height, $maxLat - $minLat, self::EPSILON);
            $this->assertEqualsWithDelta($width, $maxLng - $minLng, self::EPSILON);
        }
    }

    #[Test]
    public function neighbours_are_adjacent_distinct_cells(): void
    {
        $neighbours = Geohash::neighbours('ezs42');

        $this->assertCount(8, $neighbours);
        $this->assertCount(8, array_unique($neighbours));
        $this->assertNotContains('ezs42', $neighbours);

        [$minLat, $maxLat, $minLng, $maxLng] = Geohash::bounds('ezs42');

        foreach ($neighbours as $neighbour) {
            $this->assertSame(5, strlen($neighbour));

            [$nMinLat, $nMaxLat, $nMinLng, $nMaxLng] = Geohash::bounds($neighbour);

            $this->assertTrue(
                $nMinLat <= $maxLat + self::EPSILON && $nMaxLat >= $minLat - self::EPSILON
                && $nMinLng <= $maxLng + self::EPSILON && $nMaxLng >= $minLng - self::EPSILON,
                "{$neighbour} does not touch ezs42",
            );
        }
    }

    #[Test]
    public function neighbours_wrap_the_antimeridian(): void
    {
        $east = Geohash::encode(0.5, 179.99, 5);

        $this->assertEqualsWithDelta(180.0, Geohash::bounds($east)[3], self::EPSILON);

        $wrapped = array_filter(
            Geohash::neighbours($east),
            fn (string $hash) => abs(Geohash::bounds($hash)[2] + 180.0) < self::EPSILON,
        );

        $this->assertNotEmpty($wrapped);
    }

    #[Test]
    public function neighbours_stop_at_the_poles(): void
    {
        $top = Geohash::encode(89.99, 10, 5);

        $this->assertEqualsWithDelta(90.0, Geohash::bounds($top)[1], self::EPSILON);
        $this->assertCount(5, Geohash::neighbours($top));
    }

    #[Test]
    public function contains_checks_a_point_against_a_cell_with_a_tolerance(): void
    {
        $hash = Geohash::encode(7.1, 125.6);
        [$minLat, $maxLat, $minLng] = Geohash::bounds($hash);
        $lng = $minLng + 1e-9;

        $this->assertTrue(Geohash::contains($hash, 7.1, 125.6));
        $this->assertFalse(Geohash::contains($hash, $maxLat + 1e-8, $lng));
        $this->assertTrue(Geohash::contains($hash, $maxLat + 1e-8, $lng, 1e-7));
        $this->assertFalse(Geohash::contains($hash, $minLat - 1e-3, $lng, 1e-7));
    }

    #[Test]
    public function bounds_rejects_characters_outside_the_alphabet(): void
    {
        foreach (['ezs4a', 'EZS42', 'ezs4i', 'ezs4 '] as $hash) {
            try {
                Geohash::bounds($hash);
                $this->fail("{$hash} should be rejected");
            } catch (\InvalidArgumentException $e) {
                $this->assertStringContainsString('geohash', strtolower($e->getMessage()));
            }
        }
    }

    #[Test]
    public function is_valid_requires_the_alphabet_and_the_length(): void
    {
        $this->assertTrue(Geohash::isValid('ezs42'));
        $this->assertTrue(Geohash::isValid(Geohash::encode(7.1, 125.6), Geohash::LENGTH));
        $this->assertFalse(Geohash::isValid('ezs42', Geohash::LENGTH));
        $this->assertFalse(Geohash::isValid('ezs4a'));
        $this->assertFalse(Geohash::isValid('EZS42'));
        $this->assertFalse(Geohash::isValid(''));
    }
}
