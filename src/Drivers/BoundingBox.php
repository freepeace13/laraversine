<?php

namespace Laraversine\Drivers;

use Laraversine\Coordinates;
use Laraversine\Haversine;

/**
 * The smallest latitude/longitude box that contains every point within a
 * radius of an origin. It only narrows candidates for an index; the exact
 * haversine check still decides membership.
 */
final class BoundingBox
{
    /** Degrees added to every edge so float rounding can never drop a boundary row. */
    private const PAD = 1e-7;

    /**
     * @param  list<array{0: float, 1: float}>|null  $longitudeRanges  null means every longitude
     */
    private function __construct(
        public readonly float $minLatitude,
        public readonly float $maxLatitude,
        public readonly ?array $longitudeRanges,
    ) {}

    /**
     * Null when the radius is at least half the earth's circumference, where
     * no box can exclude anything.
     */
    public static function around(Coordinates $origin, float $radius, string $unit): ?self
    {
        $earth = Haversine::earthRadius($unit);

        if ($radius >= M_PI * $earth) {
            return null;
        }

        $angular = $radius / $earth;
        $latitude = deg2rad($origin->latitude);

        $minLatitude = max(-90.0, rad2deg($latitude - $angular) - self::PAD);
        $maxLatitude = min(90.0, rad2deg($latitude + $angular) + self::PAD);

        // A pole inside the circle means every longitude is reachable.
        if (abs($latitude) + $angular >= M_PI_2) {
            return new self($minLatitude, $maxLatitude, null);
        }

        $ratio = min(1.0, sin($angular) / cos($latitude));
        $delta = rad2deg(asin($ratio)) + self::PAD;
        $west = $origin->longitude - $delta;
        $east = $origin->longitude + $delta;

        $ranges = match (true) {
            $west < -180.0 => [[$west + 360.0, 180.0], [-180.0, $east]],
            $east > 180.0 => [[$west, 180.0], [-180.0, $east - 360.0]],
            default => [[$west, $east]],
        };

        return new self($minLatitude, $maxLatitude, $ranges);
    }
}
