<?php

namespace Laraversine\Test\Support;

use Laraversine\Destination;
use Laraversine\Geohash;

final class DatasetFactory
{
    /** @return list<array{name: string, latitude: float, longitude: float}> */
    public static function uniform(int $count, int $seed): array
    {
        mt_srand($seed);
        $rows = [];

        for ($i = 0; $i < $count; $i++) {
            $rows[] = self::row($i, rad2deg(asin(self::random(-1, 1))), self::random(-180, 180));
        }

        return $rows;
    }

    /** @return list<array{name: string, latitude: float, longitude: float}> */
    public static function cluster(int $count, int $seed, float $lat, float $lng, float $spread): array
    {
        mt_srand($seed);
        $rows = [];

        for ($i = 0; $i < $count; $i++) {
            $rows[] = self::row(
                $i,
                max(-90, min(90, $lat + self::random(-$spread, $spread))),
                max(-180, min(180, $lng + self::random(-$spread, $spread))),
            );
        }

        return $rows;
    }

    /** Points within 0.3 degrees of the antimeridian, on both sides. */
    public static function antimeridian(int $count, int $seed): array
    {
        mt_srand($seed);
        $rows = [];

        for ($i = 0; $i < $count; $i++) {
            $offset = self::random(0, 0.3);
            $rows[] = self::row($i, self::random(-1, 1), $i % 2 === 0 ? 180 - $offset : -180 + $offset);
        }

        return $rows;
    }

    /** Points within 0.6 degrees of either pole, at any longitude. */
    public static function poles(int $count, int $seed): array
    {
        mt_srand($seed);
        $rows = [];

        for ($i = 0; $i < $count; $i++) {
            $offset = self::random(0, 0.6);
            $rows[] = self::row($i, $i % 2 === 0 ? 90 - $offset : -90 + $offset, self::random(-180, 180));
        }

        return $rows;
    }

    public static function seed(array $rows, bool $geohash = false): void
    {
        if ($geohash) {
            $rows = array_map(fn (array $row) => $row + ['geohash' => Geohash::encode($row['latitude'], $row['longitude'])], $rows);
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            Destination::insert($chunk);
        }
    }

    /** @return list<array{id: int, latitude: float, longitude: float}> */
    public static function stored(): array
    {
        return Destination::query()->get(['id', 'latitude', 'longitude'])
            ->map(fn ($d) => ['id' => $d->id, 'latitude' => (float) $d->latitude, 'longitude' => (float) $d->longitude])
            ->all();
    }

    private static function row(int $i, float $lat, float $lng): array
    {
        return ['name' => "p{$i}", 'latitude' => round($lat, 6), 'longitude' => round($lng, 6)];
    }

    private static function random(float $min, float $max): float
    {
        return $min + (mt_rand() / mt_getrandmax()) * ($max - $min);
    }
}
