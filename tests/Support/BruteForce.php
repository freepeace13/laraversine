<?php

namespace Laraversine\Test\Support;

use Laraversine\Haversine;

/**
 * Plain PHP reference implementation used as the oracle for the SQL results.
 */
final class BruteForce
{
    public static function distance(float $lat1, float $lng1, float $lat2, float $lng2, float $radius = Haversine::EARTH_RADIUS_KM): float
    {
        $dLat = (deg2rad($lat2) - deg2rad($lat1)) / 2.0;
        $dLng = (deg2rad($lng2) - deg2rad($lng1)) / 2.0;

        $a = sin($dLat) * sin($dLat)
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng) * sin($dLng);

        return 2.0 * $radius * asin(sqrt(min(1.0, $a)));
    }

    /**
     * @param  list<array{id: int, latitude: float, longitude: float}>  $rows
     * @param  array{0: float, 1: float}  $origin
     * @return array<int, float> id => distance, nearest first
     */
    public static function within(array $rows, array $origin, float $radius, string $unit = 'km'): array
    {
        $distances = self::allDistances($rows, $origin, $unit);

        return array_filter($distances, fn (float $distance) => $distance <= $radius);
    }

    /**
     * @param  list<array{id: int, latitude: float, longitude: float}>  $rows
     * @param  array{0: float, 1: float}  $origin
     * @return list<int>
     */
    public static function nearestIds(array $rows, array $origin, int $take, string $unit = 'km'): array
    {
        return array_slice(array_keys(self::allDistances($rows, $origin, $unit)), 0, $take);
    }

    /**
     * @return array<int, float>
     */
    private static function allDistances(array $rows, array $origin, string $unit): array
    {
        $radius = Haversine::earthRadius($unit);
        $distances = [];

        foreach ($rows as $row) {
            $distances[$row['id']] = self::distance($origin[0], $origin[1], $row['latitude'], $row['longitude'], $radius);
        }

        asort($distances);

        return $distances;
    }
}
