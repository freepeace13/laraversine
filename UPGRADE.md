# Upgrading

## Adopting the geohash driver (2.1, optional)

Nothing changes when you update to 2.1: the default driver, config and queries are the same. The `geohash` driver is opt-in and was not faster than the `sql` driver in our benchmarks, so measure before you switch. To try it:

1. Add a nullable, indexed `geohash` string column (see the README).
2. Run `php artisan laraversine:geohash "App\Models\YourModel"` to fill it.
3. Set `'driver' => 'geohash'`, or `protected string $geolocationDriver = 'geohash';` on one model.

Publish the config again to see the new `geohash.null_limit` key; the default works without publishing.

## From 1.x to 2.0

### Requirements

Laravel 11 and PHP 8.2 are no longer supported. Upgrade to PHP 8.3+ and Laravel 12 or 13.

### `nearby()` requires a radius

`nearby($coordinates)` without a radius calculated the distance to every row. The radius is now required, and omitting it throws an `ArgumentCountError`. A radius of `0` or less now throws an `InvalidArgumentException`; in 1.x `nearby($c, 0)` ran and returned exact matches.

| Before | After |
|---|---|
| `Destination::nearby($c)->nearestFirst()->first()` | `Destination::nearest($c)` |
| `Destination::nearby($c)->nearestFirst()->limit(5)->get()` | `Destination::nearest($c, take: 5)` |
| `Destination::nearby($c)->get()` | `Destination::nearby($c, radius: 10)->get()` (pick the radius you need) |

### `within()` is deprecated

`within($radius)` still works in 2.x but triggers `E_USER_DEPRECATED`. It is removed in 3.0. Pass the radius to `nearby()` instead:

```php
// Before
Destination::nearby($c)->within(2)->get();
// After
Destination::nearby($c, 2)->get();
```

If your test suite turns deprecations into errors, `within()` will fail it until you make that change.

### The migration is no longer loaded automatically

A fresh install no longer creates a `destinations` table. If you use the bundled `Destination` model, publish the migration:

```bash
php artisan vendor:publish --tag=laraversine-migrations
php artisan migrate
```

An existing `destinations` table is unaffected. If you already have the table, do not publish the migration: it would be copied with a new timestamp and `php artisan migrate` would fail with "table already exists". Note that the old auto-loaded migration is no longer seen by `migrate:status` or `migrate:rollback`.

### Rows without coordinates are never returned

In 1.x, `nearby($c)` without a radius returned every row, including rows with a `NULL` latitude or longitude (their `distance` was `NULL`). `nearby()` now always applies a radius, and rows with a `NULL` latitude or longitude are excluded. If you relied on those rows, query them separately.

### Distances change slightly

2.0 uses the true haversine formula instead of the spherical law of cosines. Results differ by tiny amounts (and are more accurate for short distances). No action is needed.

### `$model->distance` on a model that did not select it

Previously `$model->distance` returned `null` when the model was not loaded through `nearby()`. It now throws a `LogicException`, because Eloquent tries to resolve `distance()` as a relationship. Use `$model->distance()`, which still returns `null`, or check `array_key_exists('distance', $model->getAttributes())`.

### Using the package on your own models

You no longer need to extend `Destination`. Use the trait and, if your columns are not `latitude` and `longitude`, name them:

```php
use Laraversine\Concerns\HasGeolocation;

class Store extends Model
{
    use HasGeolocation;

    protected string $latitudeColumn = 'lat';
    protected string $longitudeColumn = 'lng';
}
```

Models that extend `Destination` keep working with the changes above, with two exceptions:

- The protected `static $origins`, `unit()` and `earthRadius()` members were removed. Subclasses that override or call them must switch to `Haversine::unit()` and `Haversine::earthRadius()` (the origin map is now internal).
- The scopes now live in the `HasGeolocation` trait. A subclass that overrides `scopeNearby()` or `scopeWithin()` with its 1.x body replaces the new implementation, so it bypasses the search driver and may call the removed members above. Remove the override, or port it to the 2.0 signature `scopeNearby(Builder $query, array $coordinates, float $radius)`.

### Stricter coordinate validation

Coordinates containing `NaN` or `INF` are now rejected, in addition to the existing latitude and longitude range check.

### Recommended index

Add a composite index so the bounding-box pre-filter can use it:

```php
$table->index(['latitude', 'longitude']);
```

### `nearest()` is a macro on every Eloquent builder

`nearest()` is registered with `Builder::macro()`, because a scope cannot return `null`. If your application or another package already defines a `nearest` macro on the Eloquent builder, one of them will override the other. Rename yours.

### New configuration

Publish the config again to see the new `driver` and `nearest.*` keys. Defaults work without publishing.
