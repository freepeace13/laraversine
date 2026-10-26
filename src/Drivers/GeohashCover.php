<?php

namespace Laraversine\Drivers;

use Laraversine\Coordinates;
use Laraversine\Geohash;

/**
 * The geohash prefix ranges that contain every point within a radius of an
 * origin: the longest prefix whose cell is at least as large as the search
 * circle's extent, and that cell's neighbours. Like BoundingBox it only
 * narrows candidates for an index; the exact haversine check still decides
 * membership.
 */
final class GeohashCover
{
    /**
     * @param  list<array{0: string, 1: string}>  $ranges  inclusive [low, high] pairs of full-length hashes
     */
    private function __construct(
        public readonly int $precision,
        public readonly array $ranges,
    ) {}

    /**
     * Null when the cover is unreliable (a radius too large for any cell, a
     * pole inside the circle, or a circle crossing the antimeridian) and the
     * caller must search without it.
     */
    public static function around(Coordinates $origin, float $radius, string $unit): ?self
    {
        $box = BoundingBox::around($origin, $radius, $unit);

        if ($box === null || $box->longitudeRanges === null || count($box->longitudeRanges) > 1) {
            return null;
        }

        // The circle's exact reach from the origin, in degrees. A cell at least
        // this large on both axes puts the whole circle inside the origin's
        // cell and its neighbours.
        $reachLat = max($box->maxLatitude - $origin->latitude, $origin->latitude - $box->minLatitude);
        $reachLng = ($box->longitudeRanges[0][1] - $box->longitudeRanges[0][0]) / 2;

        for ($precision = Geohash::LENGTH; $precision >= 1; $precision--) {
            [$height, $width] = Geohash::cellSize($precision);

            if ($height >= $reachLat && $width >= $reachLng) {
                return new self($precision, self::ranges($origin, $precision));
            }
        }

        return null;
    }

    /**
     * @return list<array{0: string, 1: string}>
     */
    private static function ranges(Coordinates $origin, int $precision): array
    {
        $cell = Geohash::encode($origin->latitude, $origin->longitude, $precision);
        $padding = Geohash::LENGTH - $precision;

        return array_map(
            fn (string $hash) => [$hash.str_repeat('0', $padding), $hash.str_repeat('z', $padding)],
            [$cell, ...Geohash::neighbours($cell)],
        );
    }
}
