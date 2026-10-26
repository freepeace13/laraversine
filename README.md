# Laraversine — Nearest Location Search for Laravel (Haversine Formula)

[![Tests](https://github.com/freepeace13/laraversine/actions/workflows/tests.yml/badge.svg)](https://github.com/freepeace13/laraversine/actions/workflows/tests.yml)
[![Lint](https://github.com/freepeace13/laraversine/actions/workflows/lint.yml/badge.svg)](https://github.com/freepeace13/laraversine/actions/workflows/lint.yml)
[![Latest Version](https://img.shields.io/packagist/v/freepeace13/laraversine.svg)](https://packagist.org/packages/freepeace13/laraversine)
[![License](https://img.shields.io/packagist/l/freepeace13/laraversine.svg)](LICENSE)

**Laraversine** adds geolocation search to Laravel Eloquent. Find the nearest
locations, filter by radius, and sort by distance using the
[haversine formula](https://en.wikipedia.org/wiki/Haversine_formula), computed
in SQL, with no PostGIS or spatial extension required.

- `nearest()`: the closest record(s) to a point, with no radius to guess
- `nearby()`: everything inside a radius, with a computed `distance` attribute
- Works on any Eloquent model through a trait, with your own column names
- Kilometres or miles
- MySQL, MariaDB, PostgreSQL and SQLite
- Composable Eloquent scopes: chain with `where`, `paginate`, `with`, etc.
- Pluggable search drivers

**See it in action:** [laraversine-demo](https://github.com/freepeace13/laraversine-demo),
a one-page Laravel app with a radius slider, a km/mile toggle and a Leaflet map.

## Requirements

- PHP 8.3+
- Laravel 12 or 13

## Installation

```bash
composer require freepeace13/laraversine
```

The service provider is auto-discovered. Laraversine adds no tables on its own.
If you want the bundled `destinations` table (used by `Laraversine\Destination`),
publish and run its migration; to customise the config, publish that too:

```bash
php artisan vendor:publish --tag=laraversine-migrations
php artisan migrate
php artisan vendor:publish --tag=laraversine-config
```

## Usage

Add the trait to any Eloquent model that has latitude and longitude columns:

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

Coordinates are always `[latitude, longitude]`.

### The closest record

`nearest()` finds the closest record(s) without you choosing a radius. It
searches a small radius first and widens it until it has enough rows, so every
query it runs is bounded. The result is exact.

```php
$store = Store::where('is_open', true)->nearest([7.127512, 125.637049]);          // ?Store
$stores = Store::where('is_open', true)->nearest([7.127512, 125.637049], take: 5); // Collection

$store->distance;      // e.g. 0.0421
$store->distance();    // Laraversine\Distance
```

`nearest()` respects the constraints already on the query. Pass `maxRadius:` to
stop widening (it returns `null`, or fewer rows, if nothing is that close).
It does not paginate; use `nearby()` for that.

### Everything inside an area

`nearby()` requires a radius, so a query can never silently scan the whole table:

```php
Store::nearby([7.127512, 125.637049], radius: 5)->nearestFirst()->paginate();
```

`nearestFirst()` must come after `nearby()` and throws a `LogicException`
otherwise. Invalid coordinates (wrong shape, latitude outside -90..90 or
longitude outside -180..180) throw an `InvalidArgumentException`.

### Distance as a value object

`$model->distance` is a plain number. Call `distance()` when you also need the
unit:

```php
$distance = $store->distance(); // Laraversine\Distance, or null without nearby()/nearest()

$distance->value;       // 0.0421
$distance->unit;        // 'km' or 'mile', from config('laraversine.search_by')
$distance->toKm();      // float
$distance->toMiles();   // float
(string) $distance;     // "0.04 km"
json_encode($distance); // {"value":0.0421,"unit":"km"}
```

On a model that did not load the `distance` column, `$model->distance` throws a
`LogicException` (Eloquent tries to resolve `distance()` as a relationship).

### Bundled model

`Laraversine\Destination` is a ready-made model for the published migration
(`name`, `latitude`, `longitude`). It simply uses the trait.

### Drivers

A driver decides how the radius search runs. The default `sql` driver needs no
setup. Choose another globally with `config('laraversine.driver')`, or per model:

```php
protected string $geolocationDriver = 'my-driver';
```

Register your own by implementing `Laraversine\Contracts\SearchDriver`:

```php
use Laraversine\Facades\Laraversine;

Laraversine::extend('my-driver', fn () => new MyDriver);
```

A driver must finish with an exact distance check and never return a row only
because it fell inside a bounding box.

#### The `geohash` driver (opt-in)

The `geohash` driver narrows a search with prefix ranges on a stored geohash
column instead of a latitude/longitude box. **In our benchmarks it was not
faster than the default `sql` driver** (see [BENCHMARKS.md](BENCHMARKS.md)), so
enable it only after measuring a win on your own data.

1. Add a nullable, indexed column to the table (any name; `geohash` is the default):

   ```php
   Schema::table('destinations', function (Blueprint $table) {
       $table->string('geohash', 12)->nullable()->index();
   });
   ```

   The search compares hashes with `BETWEEN`, so the column's collation has to
   order digits and the letters `a` to `z` the usual way. The default collations
   on MySQL 8, MariaDB 11, PostgreSQL 16 and SQLite do (the test suite runs on
   them). A few locale collations do not; Estonian, for example, sorts `z`
   between `s` and `t`. If yours is one of them, give the column a binary
   collation:

   ```php
   $table->string('geohash', 12)->collation('utf8mb4_bin')->nullable()->index(); // MySQL, MariaDB
   $table->string('geohash', 12)->collation('C')->nullable()->index();           // PostgreSQL
   ```

2. Fill it for existing rows, and run the same command whenever you want to check it:

   ```bash
   php artisan laraversine:geohash "App\Models\Destination"           # backfill and repair
   php artisan laraversine:geohash "App\Models\Destination" --verify  # report only; exits 1 on a mismatch
   ```

3. Switch the driver, globally with `'driver' => 'geohash'` or per model:

   ```php
   protected string $geolocationDriver = 'geohash';
   protected string $geohashColumn = 'hash'; // optional, defaults to "geohash"
   ```

While a model uses the geohash driver, saving it keeps the column in sync. Rows
changed without model events (`Model::query()->update()`, `insert()`, raw SQL,
imports) keep their old hash and are not seen as moved, so run the command
after bulk changes. Rows with coordinates but no hash are still found; when more
than `geohash.null_limit` of them exist, the search uses the `sql` driver. The
`sql` driver also runs for a very large radius, near a pole and across the
antimeridian. A model that uses the driver but has no column saves as before and throws a
`LogicException` when it is searched.

A moved row whose hash was not updated is silently left out of results until the
command repairs it, so treat a bulk write to the coordinates as a reason to run it.
Switching an existing table to the driver needs a first backfill, and nothing warns
you until the `null_limit` fallback kicks in. After you add the column with a
migration, a worker that already saw it missing waits up to a minute before it
hashes saved rows, so restart workers or run the command after deploying.

Each search outside `nearest()` also runs one extra counting query for the rows
without a hash, before the real query.

Rows without a hash cost something on every search: the query also has to read
each row whose hash is `NULL`, including rows with no coordinates at all, so a
table where most rows have no location pays a cost that grows with that count.
`nearest()` pays it once per round.

### Laravel Boost

The package ships [Laravel Boost](https://laravel.com/docs/boost) guidelines and a
`laraversine-search` skill in `resources/boost`. Run `php artisan boost:install`
to load them into your AI coding assistant.

## Configuration

`config/laraversine.php`:

| Key | Default | Description |
|-----|---------|-------------|
| `search_by` | `km` | `km` or `mile`. Applies to `distance` and to every radius. |
| `driver` | `sql` | The search driver: `sql` or `geohash`. |
| `geohash.null_limit` | `1000` | With the `geohash` driver, the most rows without a hash a search tolerates before it uses the `sql` driver. |
| `nearest.initial_radius` | `1` | Radius of the first `nearest()` round, in the configured unit. |
| `nearest.max_radius` | `null` | Where `nearest()` stops widening. `null` means the whole globe. |

## How it works

Laraversine computes the great-circle distance in SQL with the
[haversine formula](https://en.wikipedia.org/wiki/Haversine_formula), with
`R = 6371` km (or `3958.8` miles):

```
distance = 2 * R * asin( sqrt( sin²(Δlat/2) + cos(lat1) * cos(lat2) * sin²(Δlng/2) ) )
```

Before that exact check, the `sql` driver adds a latitude/longitude bounding box
to the query. Those conditions can use an ordinary index, so add one:

```php
$table->index(['latitude', 'longitude']);
```

Every query is bounded by a radius and can use that index. The box is only a
filter; the exact distance check decides which rows are returned. `nearest()`
runs `nearby()` in rounds of doubling radius and stops as soon as a round
returns enough rows, which makes the result exact.

A B-tree index narrows well on latitude only, so when most of a very large
table sits in one region the latitude band can still hold many rows. See
[BENCHMARKS.md](BENCHMARKS.md) for measurements.

The opt-in `geohash` driver instead picks the longest geohash prefix whose cell is
at least as large as the radius, and looks up that cell and its eight neighbours
as index ranges, then applies the same exact distance check.

On SQLite the `radians`, `cos`, `sin`, `asin` and `sqrt` functions must be
registered on the PDO connection (see `tests/TestCase.php` for an example).

## Testing

```bash
composer install
composer test                                        # SQLite (default)
DB_DRIVER=mysql vendor/bin/phpunit                   # needs a laraversine_test DB, root/root
DB_DRIVER=mariadb vendor/bin/phpunit                 # needs a laraversine_test DB, root/root
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
