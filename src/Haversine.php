<?php

namespace Laraversine;

final class Haversine
{
    public const EARTH_RADIUS_KM = 6371.0;

    public const EARTH_RADIUS_MILES = 3958.8;

    /**
     * The configured unit, normalised to a Distance constant.
     */
    public static function unit(): string
    {
        return config('laraversine.search_by', 'km') === 'mile' ? Distance::MILE : Distance::KM;
    }

    public static function earthRadius(string $unit): float
    {
        return $unit === Distance::MILE ? self::EARTH_RADIUS_MILES : self::EARTH_RADIUS_KM;
    }

    /**
     * The largest possible great-circle distance (to the antipode).
     */
    public static function halfCircumference(string $unit): float
    {
        return M_PI * self::earthRadius($unit);
    }

    /**
     * SQL for the haversine distance between a row's coordinate columns and
     * the origin, in the given unit. The five "?" placeholders take the
     * returned bindings: [lat, lat, lat, lng, lng].
     *
     * @return array{0: string, 1: array<int, float>}
     */
    public static function sql(string $latColumn, string $lngColumn, Coordinates $origin, string $unit, bool $sqlite = false): array
    {
        $least = $sqlite ? 'min' : 'least';
        $radius = self::earthRadius($unit);

        $dLat = "((radians({$latColumn}) - radians(?)) / 2.0)";
        $dLng = "((radians({$lngColumn}) - radians(?)) / 2.0)";

        $expression = "(2.0 * {$radius} * asin(sqrt({$least}(1.0, "
            ."sin({$dLat}) * sin({$dLat}) "
            ."+ cos(radians(?)) * cos(radians({$latColumn})) * sin({$dLng}) * sin({$dLng})"
            .'))))';

        return [
            $expression,
            [$origin->latitude, $origin->latitude, $origin->latitude, $origin->longitude, $origin->longitude],
        ];
    }
}
