<?php

namespace Laraversine\Concerns;

use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;
use Laraversine\Contracts\SearchDriver;
use Laraversine\Coordinates;
use Laraversine\Distance;
use Laraversine\Haversine;
use Laraversine\Origins;
use Laraversine\SearchManager;
use LogicException;

trait HasGeolocation
{
    public function getLatitudeColumn(): string
    {
        return property_exists($this, 'latitudeColumn') ? $this->latitudeColumn : 'latitude';
    }

    public function getLongitudeColumn(): string
    {
        return property_exists($this, 'longitudeColumn') ? $this->longitudeColumn : 'longitude';
    }

    public function getQualifiedLatitudeColumn(): string
    {
        return $this->qualifyColumn($this->getLatitudeColumn());
    }

    public function getQualifiedLongitudeColumn(): string
    {
        return $this->qualifyColumn($this->getLongitudeColumn());
    }

    public function getGeohashColumn(): string
    {
        return property_exists($this, 'geohashColumn') ? $this->geohashColumn : 'geohash';
    }

    public function getQualifiedGeohashColumn(): string
    {
        return $this->qualifyColumn($this->getGeohashColumn());
    }

    public function getGeolocationDriverName(): ?string
    {
        return property_exists($this, 'geolocationDriver') ? $this->geolocationDriver : null;
    }

    /**
     * Whether this model's searches run on the geohash driver, whether it is
     * named on the model or inherited from the config.
     */
    public function usesGeohash(): bool
    {
        return ($this->getGeolocationDriverName() ?? config('laraversine.driver', 'sql')) === 'geohash';
    }

    /**
     * Add a computed "distance" column (in the configured unit) from the given
     * [latitude, longitude] and restrict the query to the radius.
     */
    public function scopeNearby(Builder $query, array $coordinates, float $radius): Builder
    {
        if ($radius <= 0) {
            throw new InvalidArgumentException('Radius must be greater than 0.');
        }

        $origin = Coordinates::from($coordinates);
        $unit = Haversine::unit();

        $this->searchDriver()->applyRadius($query, $origin, $radius, $unit);

        Origins::remember($query->getQuery(), $origin, $unit);

        return $query;
    }

    /**
     * Sort nearest first. Use after nearby().
     */
    public function scopeNearestFirst(Builder $query): Builder
    {
        if (Origins::get($query->getQuery()) === null) {
            throw new LogicException('nearestFirst() must be called after nearby().');
        }

        return $query->orderBy('distance');
    }

    /**
     * @deprecated Pass the radius to nearby() instead. Removed in 3.0.
     */
    public function scopeWithin(Builder $query, float $radius): Builder
    {
        $remembered = Origins::get($query->getQuery());

        if ($remembered === null) {
            throw new LogicException('within() must be called after nearby().');
        }

        trigger_error('within() is deprecated and will be removed in 3.0. Pass the radius to nearby() instead.', E_USER_DEPRECATED);

        [$origin, $unit] = $remembered;
        [$expression, $bindings] = Haversine::forQuery($query, $origin, $unit);

        // Inlined (it is a typed float): PDO would bind it as a string, which
        // SQLite compares as greater than any number.
        return $query->whereRaw("{$expression} <= ".sprintf('%.8F', $radius), $bindings);
    }

    /**
     * The "distance" attribute as a Distance carrying its unit, or null when
     * the query did not select it.
     */
    public function distance(): ?Distance
    {
        $value = $this->getAttributes()['distance'] ?? null;

        return $value === null ? null : new Distance((float) $value, Haversine::unit());
    }

    protected function searchDriver(): SearchDriver
    {
        return app(SearchManager::class)->driver($this->getGeolocationDriverName());
    }
}
