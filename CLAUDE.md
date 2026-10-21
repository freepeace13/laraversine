# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

Laraversine is a Laravel package (PHP 8.2+, Laravel 11/12) that adds haversine-based nearby/radius search to Eloquent, computed in SQL on MySQL, MariaDB, PostgreSQL and SQLite.

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

- `src/Destination.php` holds nearly all logic: the `nearby()`, `within()` and `nearestFirst()` local scopes plus the `distance()` accessor.
- `nearby()` builds the haversine SQL expression and adds it as a `distance` select. It also records the expression and bindings in a static `WeakMap` keyed by the underlying query builder. `within()` and `nearestFirst()` read that map, which is how they know a `nearby()` call came first and reuse the same expression. Calling either one first throws `LogicException`.
- SQLite lacks `LEAST`/`GREATEST`, so the expression switches to `min`/`max` based on the driver. The `LEAST/GREATEST` clamp keeps `acos` in range.
- `within()` inlines the radius as a formatted float instead of binding it, because PDO binds it as a string and SQLite then compares it as greater than any number. Keep it inlined.
- `isRelation()` is overridden so `$model->distance` returns the raw attribute rather than resolving the `distance()` method as a relationship.
- `src/Distance.php` is the value object returned by `distance()`. The unit comes from `config('laraversine.search_by')` (`km` or `mile`), which also selects the earth radius.
- `src/LaraversineServiceProvider.php` merges the config and loads and publishes the `destinations` migration (tags `laraversine-config` and `laraversine-migrations`).
- Coordinates are always `[latitude, longitude]`.

## Workflow

- PRs target `main` and are squash-merged. The PR title must follow Conventional Commits, because it drives release-please versioning and the changelog.
- Do not edit `CHANGELOG.md` or tag versions by hand; release-please owns them.
- CI runs the suite across a PHP/Laravel matrix on SQLite, plus MySQL, MariaDB and PostgreSQL jobs, along with lint.
