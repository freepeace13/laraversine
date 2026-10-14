<?php

namespace Laraversine;

use InvalidArgumentException;
use JsonSerializable;
use Stringable;

final class Distance implements JsonSerializable, Stringable
{
    public const KM = 'km';

    public const MILE = 'mile';

    private const KM_PER_MILE = 1.609344;

    public function __construct(
        public readonly float $value,
        public readonly string $unit = self::KM,
    ) {
        if (! in_array($unit, [self::KM, self::MILE], true)) {
            throw new InvalidArgumentException("Unit must be 'km' or 'mile', got '{$unit}'.");
        }
    }

    public function toKm(): float
    {
        return $this->unit === self::KM ? $this->value : $this->value * self::KM_PER_MILE;
    }

    public function toMiles(): float
    {
        return $this->unit === self::MILE ? $this->value : $this->value / self::KM_PER_MILE;
    }

    /**
     * @return array{value: float, unit: string}
     */
    public function toArray(): array
    {
        return ['value' => $this->value, 'unit' => $this->unit];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function __toString(): string
    {
        return sprintf('%s %s', round($this->value, 2), $this->unit === self::MILE ? 'mi' : 'km');
    }
}
