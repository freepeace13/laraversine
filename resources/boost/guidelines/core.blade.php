## Laraversine

Haversine nearest/radius search for Eloquent, computed in SQL (MySQL, MariaDB, PostgreSQL, SQLite). No spatial extension needed.

- Add `Laraversine\Concerns\HasGeolocation` to a model with latitude/longitude columns. Override with `protected string $latitudeColumn` and `$longitudeColumn` (defaults: `latitude`, `longitude`).
- Coordinates are always `[latitude, longitude]`. Invalid coordinates throw `InvalidArgumentException`.
- `Model::query()->nearest($coordinates, take: 1, maxRadius: null)` returns the closest record (`?Model`), or a `Collection` when `take` is greater than 1. It widens the radius itself, so do not pass a guessed radius. It does not paginate.
- `Model::nearby($coordinates, radius: 5)` returns everything inside the radius and adds a `distance` column. The radius is required. Chain `->nearestFirst()` after it to sort by distance; calling `nearestFirst()` without `nearby()` throws `LogicException`.
- `within()` is deprecated; use `nearby()`.
- Read the distance with `$model->distance()`, which returns `?Laraversine\Distance` (`value`, `unit`, `toKm()`, `toMiles()`). `$model->distance` throws `LogicException` on a model that did not load the column.
- The unit is `config('laraversine.search_by')` (`km` or `mile`) and applies to every radius and distance.
- Add a composite index on the coordinate columns: `$table->index(['latitude', 'longitude']);`.
- Custom search drivers implement `Laraversine\Contracts\SearchDriver`, are registered with `Laraversine::extend('name', fn () => new MyDriver)` and selected with `config('laraversine.driver')` or `protected string $geolocationDriver`. A driver must finish with the exact haversine check and never return a row on a bounding box alone.
- The package ships a `destinations` migration (`php artisan vendor:publish --tag=laraversine-migrations`) for `Laraversine\Destination`; it is not loaded automatically.
