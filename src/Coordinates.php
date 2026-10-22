<?php

namespace Laraversine;

use InvalidArgumentException;

final class Coordinates
{
    public function __construct(
        public readonly float $latitude,
        public readonly float $longitude,
    ) {
        if (! is_finite($latitude) || ! is_finite($longitude) || abs($latitude) > 90 || abs($longitude) > 180) {
            throw new InvalidArgumentException('Latitude must be within -90..90 and longitude within -180..180.');
        }
    }

    /**
     * @param  array<array-key, mixed>  $coordinates  [latitude, longitude]
     */
    public static function from(array $coordinates): self
    {
        $coordinates = array_values($coordinates);

        if (count($coordinates) !== 2 || ! is_numeric($coordinates[0]) || ! is_numeric($coordinates[1])) {
            throw new InvalidArgumentException('Coordinates must be [latitude, longitude].');
        }

        return new self((float) $coordinates[0], (float) $coordinates[1]);
    }

    /**
     * @return array{0: float, 1: float}
     */
    public function toArray(): array
    {
        return [$this->latitude, $this->longitude];
    }
}
