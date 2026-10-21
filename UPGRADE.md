# Upgrading

## From 1.x to 2.0

### Requirements

Laravel 11 and PHP 8.2 are no longer supported. Upgrade to PHP 8.3+ and Laravel 12 or 13.

### `nearby()` requires a radius

`nearby($coordinates)` without a radius calculated the distance to every row. The radius is now required, and omitting it throws an `ArgumentCountError`.

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

### The migration is no longer loaded automatically

A fresh install no longer creates a `destinations` table. If you use the bundled `Destination` model, publish the migration:

```bash
php artisan vendor:publish --tag=laraversine-migrations
php artisan migrate
```

An existing `destinations` table is unaffected.

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

Models that already extend `Destination` keep working with only the changes above.

### Recommended index

Add a composite index so the bounding-box pre-filter can use it:

```php
$table->index(['latitude', 'longitude']);
```

### New configuration

Publish the config again to see the new `driver` and `nearest.*` keys. Defaults work without publishing.
