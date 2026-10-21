# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

Laraversine is a Laravel package (PHP 8.3+, Laravel 12/13) that adds haversine-based nearest/radius search to Eloquent, computed in SQL on MySQL, MariaDB, PostgreSQL and SQLite.

## Commands

```bash
composer test                                   # vendor/bin/phpunit (SQLite in-memory by default)
vendor/bin/phpunit --filter testName            # single test
vendor/bin/phpunit tests/DestinationTest.php    # single file
composer lint                                   # pint (fix)
composer lint:check                             # pint --test (what CI runs)
```

Other databases: set `DB_DRIVER` (`sqlite` | `mysql` | `mariadb` | `pgsql`); `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` are optional overrides (see `tests/TestCase.php` for defaults).

## Architecture

- `src/Concerns/HasGeolocation.php` is the trait models use. It provides the `nearby($coordinates, $radius)`, `nearestFirst()` and deprecated `within()` scopes, the `distance()` accessor and the column/driver getters (`latitudeColumn`, `longitudeColumn`, `geolocationDriver` properties, read with `property_exists()` so the trait declares none). `Destination` is just a model that uses it.
- `nearby()` delegates to a `SearchDriver` (`src/Contracts`) resolved by `SearchManager` (`Laraversine::extend()` adds drivers; default from `config('laraversine.driver')`). The default `Drivers/SqlDriver` adds a `Drivers/BoundingBox` pre-filter and always finishes with the exact haversine check. A driver must never return a row on a bounding box alone.
- `nearest()` is an Eloquent `Builder` macro registered in the service provider (scopes cannot return `null`). `src/Nearest.php` runs `nearby()` in rounds of doubling radius until it has `take` rows; each round resets the base query's order, limit and offset.
- `src/Origins.php` is a static `WeakMap` keyed by the underlying query builder. `nearby()` records the origin there, which is how `nearestFirst()` and `within()` know a `nearby()` call came first (otherwise `LogicException`).
- `src/Haversine.php` builds the true haversine SQL (`least` becomes `min` on SQLite) and owns the earth radii and the unit from `config('laraversine.search_by')` (`km` or `mile`). Radius and bounding-box bounds are inlined as `%.8F` literals because PDO binds them as strings and SQLite then compares them as greater than any number. Keep them inlined.
- `$model->distance` on a model that did not load the column throws `LogicException` (Eloquent resolves `distance()` as a relationship); use `distance()`, which returns `?Distance` (`src/Distance.php`).
- `src/LaraversineServiceProvider.php` merges the config, registers the manager and the `nearest` macro, and only publishes the `destinations` migration (tags `laraversine-config`, `laraversine-migrations`); it does not load it.
- Coordinates are always `[latitude, longitude]` (`src/Coordinates.php` validates them).
- Tests: `tests/Drivers/SearchDriverContract.php` checks every driver against a brute-force oracle (`tests/Support/BruteForce.php`); `composer bench` runs `benchmarks/`.

## Workflow

- PRs target `main` and are squash-merged. The PR title must follow Conventional Commits, because it drives release-please versioning and the changelog.
- Do not edit `CHANGELOG.md` or tag versions by hand; release-please owns them.
- CI runs the suite across a PHP/Laravel matrix on SQLite, plus MySQL, MariaDB and PostgreSQL jobs, along with lint.
