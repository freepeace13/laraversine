# Changelog

All notable changes to `freepeace13/laraversine` are documented here.

## [1.1.0](https://github.com/freepeace13/laraversine/compare/v1.0.0...v1.1.0) (2026-10-14)


### Features

* add Distance value object via distance() accessor ([#4](https://github.com/freepeace13/laraversine/issues/4)) ([48ddc30](https://github.com/freepeace13/laraversine/commit/48ddc30448e5149861ede93418c88307212a78c5))


### Bug Fixes

* throw clear error when nearestFirst() is called before nearby() ([#3](https://github.com/freepeace13/laraversine/issues/3)) ([ed180c0](https://github.com/freepeace13/laraversine/commit/ed180c01a7b8ee21ca8b630abcb5196280940c88))

## 1.0.0

- First stable release.
- `nearby()`, `within()` and `nearestFirst()` Eloquent scopes on `Laraversine\Destination`.
- Distances in kilometres or miles via `config('laraversine.search_by')`.
- Works on MySQL/MariaDB, PostgreSQL and SQLite.
- Supports PHP 8.2+ and Laravel 11 / 12.
