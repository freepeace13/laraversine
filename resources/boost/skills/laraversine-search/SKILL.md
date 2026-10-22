---
name: laraversine-search
description: Find the nearest records or everything within a radius of a latitude/longitude with Laraversine on Eloquent. Use when adding location search, "near me" queries, distance sorting or distance display to a Laravel model.
---

# Laraversine search

## Setup

```php
use Illuminate\Database\Eloquent\Model;
use Laraversine\Concerns\HasGeolocation;

class Store extends Model
{
    use HasGeolocation;

    protected string $latitudeColumn = 'lat';   // optional, default "latitude"
    protected string $longitudeColumn = 'lng';  // optional, default "longitude"
}
```

Coordinates are always `[latitude, longitude]`. Index the pair: `$table->index(['latitude', 'longitude']);`.

## Pick the method

| Need | Use |
|---|---|
| The closest record(s), radius unknown | `->nearest($coords, take: 5)` |
| Everything inside a known radius | `::nearby($coords, radius: 5)` |
| That, ordered by distance, paginated | `::nearby($coords, radius: 5)->nearestFirst()->paginate()` |

```php
$store = Store::where('is_open', true)->nearest([7.127512, 125.637049]);           // ?Store
$stores = Store::where('is_open', true)->nearest([7.127512, 125.637049], take: 5); // Collection
```

`nearest()` honours existing constraints, accepts `maxRadius:` to stop widening, and cannot paginate.

## Distance

```php
$store->distance();          // ?Laraversine\Distance
$store->distance()->value;   // float
$store->distance()->unit;    // 'km' or 'mile'
```

Use `distance()`, not `$store->distance`: the property throws `LogicException` when the column was not loaded.

## Pitfalls

- `nearestFirst()` before `nearby()` throws `LogicException`.
- `nearby()` has no default radius; omitting it is an error.
- `within()` is deprecated.
- Unit comes from `config('laraversine.search_by')`; do not convert radii yourself.
- On SQLite, `radians`, `cos`, `sin`, `asin` and `sqrt` must be registered on the PDO connection.
