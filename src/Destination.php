<?php

namespace Laraversine;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use LogicException;
use WeakMap;

class Destination extends Model
{
    protected $fillable = ['name', 'latitude', 'longitude'];

    /** @var WeakMap<\Illuminate\Database\Query\Builder, array>|null */
    protected static ?WeakMap $origins = null;

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    /**
     * Add a computed "distance" column (in the configured unit) from the given
     * [latitude, longitude]. Optionally restrict to a radius.
     *
     * The radius filter is a WHERE on the distance expression, so it works on
     * MySQL/MariaDB, PostgreSQL and SQLite.
     */
    public function scopeNearby(Builder $query, array $coordinates, ?float $radius = null): Builder
    {
        $coordinates = array_values($coordinates);

        if (count($coordinates) !== 2 || ! is_numeric($coordinates[0]) || ! is_numeric($coordinates[1])) {
            throw new InvalidArgumentException('Coordinates must be [latitude, longitude].');
        }

        [$lat, $lng] = $coordinates;

        if (abs($lat) > 90 || abs($lng) > 180) {
            throw new InvalidArgumentException('Latitude must be within -90..90 and longitude within -180..180.');
        }

        $table = $this->getTable();
        $earthRadius = static::earthRadius();
        $sqlite = $query->getConnection()->getDriverName() === 'sqlite';
        [$least, $greatest] = $sqlite ? ['min', 'max'] : ['least', 'greatest'];

        if (empty($query->getQuery()->columns)) {
            $query->select("{$table}.*");
        }

        $expression = "({$earthRadius} * acos({$least}(1.0, {$greatest}(-1.0,
                cos(radians(?)) * cos(radians({$table}.latitude))
                * cos(radians({$table}.longitude) - radians(?))
                + sin(radians(?)) * sin(radians({$table}.latitude))
            ))))";
        $bindings = [(float) $lat, (float) $lng, (float) $lat];

        $query->selectRaw("{$expression} AS distance", $bindings);

        static::$origins ??= new WeakMap;
        static::$origins[$query->getQuery()] = [$expression, $bindings];

        if ($radius !== null) {
            $query->within($radius);
        }

        return $query;
    }

    /**
     * Restrict a nearby() query to destinations within the radius.
     */
    public function scopeWithin(Builder $query, float $radius): Builder
    {
        $origin = static::$origins[$query->getQuery()] ?? null;

        if ($origin === null) {
            throw new LogicException('within() must be called after nearby().');
        }

        [$expression, $bindings] = $origin;

        // Inlined (it is a typed float): PDO would bind it as a string, which
        // SQLite compares as greater than any number.
        $radius = sprintf('%.8F', $radius);

        return $query->whereRaw("{$expression} <= {$radius}", $bindings);
    }

    /**
     * Sort nearest first. Use after nearby().
     */
    public function scopeNearestFirst(Builder $query): Builder
    {
        return $query->orderBy('distance');
    }

    protected static function earthRadius(): float
    {
        return config('laraversine.search_by', 'km') === 'mile' ? 3958.8 : 6371.0;
    }
}
