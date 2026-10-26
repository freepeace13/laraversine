<?php

namespace Laraversine;

use InvalidArgumentException;

/**
 * Geohash encoding: a string where a shared prefix means the same grid cell.
 * Bits alternate longitude first, five bits per base32 character.
 */
final class Geohash
{
    public const LENGTH = 12;

    private const ALPHABET = '0123456789bcdefghjkmnpqrstuvwxyz';

    public static function encode(float $latitude, float $longitude, int $length = self::LENGTH): string
    {
        $ranges = ['lat' => [-90.0, 90.0], 'lng' => [-180.0, 180.0]];
        $values = ['lat' => $latitude, 'lng' => $longitude];
        $hash = '';
        $axis = 'lng';
        $bit = 0;
        $index = 0;

        while (strlen($hash) < $length) {
            $middle = ($ranges[$axis][0] + $ranges[$axis][1]) / 2;

            // The upper half includes its midpoint, so 90 and 180 land in the last cell.
            if ($values[$axis] >= $middle) {
                $index = ($index << 1) | 1;
                $ranges[$axis][0] = $middle;
            } else {
                $index <<= 1;
                $ranges[$axis][1] = $middle;
            }

            $axis = $axis === 'lng' ? 'lat' : 'lng';

            if (++$bit === 5) {
                $hash .= self::ALPHABET[$index];
                $bit = 0;
                $index = 0;
            }
        }

        return $hash;
    }

    /**
     * @return array{0: float, 1: float, 2: float, 3: float} [minLat, maxLat, minLng, maxLng]
     */
    public static function bounds(string $hash): array
    {
        $ranges = ['lat' => [-90.0, 90.0], 'lng' => [-180.0, 180.0]];
        $axis = 'lng';

        foreach (str_split($hash) as $character) {
            $index = strpos(self::ALPHABET, $character);

            if ($index === false) {
                throw new InvalidArgumentException("\"{$character}\" is not a geohash character.");
            }

            for ($mask = 16; $mask > 0; $mask >>= 1) {
                $middle = ($ranges[$axis][0] + $ranges[$axis][1]) / 2;
                $ranges[$axis][$index & $mask ? 0 : 1] = $middle;
                $axis = $axis === 'lng' ? 'lat' : 'lng';
            }
        }

        return [$ranges['lat'][0], $ranges['lat'][1], $ranges['lng'][0], $ranges['lng'][1]];
    }

    /**
     * Whether $hash only uses geohash characters (lowercase, without a, i, l
     * and o), and has $length of them when a length is given.
     */
    public static function isValid(string $hash, ?int $length = null): bool
    {
        return preg_match('/^[0-9b-hjkmnp-z]+$/', $hash) === 1 && ($length === null || strlen($hash) === $length);
    }

    /**
     * Whether a point lies in the hash's cell, or within $tolerance degrees of it.
     */
    public static function contains(string $hash, float $latitude, float $longitude, float $tolerance = 0.0): bool
    {
        [$minLat, $maxLat, $minLng, $maxLng] = self::bounds($hash);

        return $latitude >= $minLat - $tolerance && $latitude <= $maxLat + $tolerance
            && $longitude >= $minLng - $tolerance && $longitude <= $maxLng + $tolerance;
    }

    /**
     * @return array{0: float, 1: float} [height in degrees, width in degrees]
     */
    public static function cellSize(int $length): array
    {
        $bits = 5 * $length;

        return [180.0 / (2 ** intdiv($bits, 2)), 360.0 / (2 ** (intdiv($bits, 2) + $bits % 2))];
    }

    /**
     * The adjacent cells of the same length. Longitude wraps across 180;
     * cells beyond a pole do not exist.
     *
     * @return list<string>
     */
    public static function neighbours(string $hash): array
    {
        [$minLat, $maxLat, $minLng, $maxLng] = self::bounds($hash);
        [$height, $width] = self::cellSize(strlen($hash));
        $centreLat = ($minLat + $maxLat) / 2;
        $centreLng = ($minLng + $maxLng) / 2;
        $neighbours = [];

        foreach ([-1, 0, 1] as $dLat) {
            foreach ([-1, 0, 1] as $dLng) {
                if ($dLat === 0 && $dLng === 0) {
                    continue;
                }

                $lat = $centreLat + $dLat * $height;

                if ($lat > 90.0 || $lat < -90.0) {
                    continue;
                }

                $lng = $centreLng + $dLng * $width;
                $lng = $lng > 180.0 ? $lng - 360.0 : ($lng < -180.0 ? $lng + 360.0 : $lng);

                $neighbours[] = self::encode($lat, $lng, strlen($hash));
            }
        }

        return array_values(array_unique(array_diff($neighbours, [$hash])));
    }
}
