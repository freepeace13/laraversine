# Laraversine — Nearest Location Search for Laravel (Haversine Formula)

[![Tests](https://github.com/freepeace13/laraversine/actions/workflows/tests.yml/badge.svg)](https://github.com/freepeace13/laraversine/actions/workflows/tests.yml)
[![Lint](https://github.com/freepeace13/laraversine/actions/workflows/lint.yml/badge.svg)](https://github.com/freepeace13/laraversine/actions/workflows/lint.yml)
[![Latest Version](https://img.shields.io/packagist/v/freepeace13/laraversine.svg)](https://packagist.org/packages/freepeace13/laraversine)
[![License](https://img.shields.io/packagist/l/freepeace13/laraversine.svg)](LICENSE)

**Laraversine** adds geolocation search to Laravel Eloquent. Find the nearest
locations, filter by radius, and sort by distance using the
[haversine formula](https://en.wikipedia.org/wiki/Haversine_formula), computed
in SQL, with no PostGIS or spatial extension required.

- Nearby search by latitude/longitude with a computed `distance` attribute
- Radius filtering (`within`) and nearest-first sorting
- Kilometres or miles
- MySQL, MariaDB, PostgreSQL and SQLite
- Composable Eloquent scopes: chain with `where`, `paginate`, `with`, etc.

**See it in action:** [laraversine-demo](https://github.com/freepeace13/laraversine-demo),
a one-page Laravel app with a radius slider, a km/mile toggle and a Leaflet map.

## Requirements

- PHP 8.2+
- Laravel 11 or 12

## Installation

```bash
composer require freepeace13/laraversine
php artisan migrate
```

The service provider is auto-discovered and loads its own `destinations`
migration. To customise the config or migration, publish them:

```bash
php artisan vendor:publish --tag=laraversine-config
php artisan vendor:publish --tag=laraversine-migrations
```

## Usage

Coordinates are always `[latitude, longitude]`. Every result gets a `distance`
attribute in the configured unit.

```php
use Laraversine\Destination;

// All destinations, each with a distance attribute
Destination::nearby([7.127512, 125.637049])->get();

// Within 2 km, nearest first
Destination::nearby([7.127512, 125.637049])->within(2)->nearestFirst()->get();

// Same, with the radius as an argument
Destination::nearby([7.127512, 125.637049], 2)->get();

$nearest = Destination::nearby([7.127512, 125.637049])->nearestFirst()->first();
$nearest->name;
$nearest->distance; // e.g. 0.0421
```

### Distance as a value object

`$model->distance` stays a plain number. Call `distance()` when you also need
the unit:

```php
$distance = $nearest->distance(); // Laraversine\Distance, or null without nearby()

$distance->value;       // 0.0421
$distance->unit;        // 'km' or 'mile', from config('laraversine.search_by')
$distance->toKm();      // float
$distance->toMiles();   // float
(string) $distance;     // "0.04 km"
json_encode($distance); // {"value":0.0421,"unit":"km"}
```

`within()` and `nearestFirst()` must come after `nearby()`; `within()` throws a
`LogicException` otherwise. Invalid coordinates (wrong shape, latitude outside
-90..90 or longitude outside -180..180) throw an `InvalidArgumentException`.

### Using it on your own model

The scopes live on `Laraversine\Destination`, which expects `latitude` and
`longitude` columns. Extend it to reuse them with your own table:

```php
use Laraversine\Destination;

class Store extends Destination
{
    protected $table = 'stores';
}
```

## Configuration

`config/laraversine.php`:

| Key         | Default | Description                                                    |
|-------------|---------|----------------------------------------------------------------|
| `search_by` | `km`    | `km` or `mile`. Applies to both `distance` and the radius unit. |

## How it works

Laraversine adds the great-circle distance to your query as a raw SQL
expression, so the database does the filtering and sorting:

```
distance = R * acos( cos(lat1) * cos(lat2) * cos(lng2 - lng1) + sin(lat1) * sin(lat2) )
```

with `R = 6371` km (or `3958.8` miles). Because the expression is computed per
row, it cannot use a spatial index. That is fine for thousands to hundreds of
thousands of rows; for millions, pre-filter with a bounding box or use native
spatial types.

On SQLite the `radians`, `cos`, `sin` and `acos` functions must be registered
on the PDO connection (see `tests/TestCase.php` for an example).

## Testing

```bash
composer install
composer test                                        # SQLite (default)
DB_DRIVER=mysql vendor/bin/phpunit                   # needs a laraversine_test DB, root/root
DB_DRIVER=pgsql vendor/bin/phpunit                   # needs a laraversine_test DB, postgres/root
```

Override `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME` and `DB_PASSWORD` as needed.

Code style is enforced with [Laravel Pint](https://laravel.com/docs/pint):

```bash
composer lint        # fix
composer lint:check  # check only (CI)
```

## Changelog

See [CHANGELOG.md](CHANGELOG.md).

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md). PRs target `main` and use Conventional Commit titles; releases are automated.

## License

The MIT License (MIT). See [LICENSE](LICENSE).
