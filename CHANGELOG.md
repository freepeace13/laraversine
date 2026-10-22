# Changelog

All notable changes to `freepeace13/laraversine` are documented here.

## [1.1.1](https://github.com/freepeace13/laraversine/compare/v1.1.0...v1.1.1) (2026-10-22)


### Miscellaneous Chores

* add 1.x branch alias and release-please target branch ([93e23ab](https://github.com/freepeace13/laraversine/commit/93e23abafca256bee6ec546d6153cf10b47b549f))
* add 1.x release config and merge-up workflow ([1020ced](https://github.com/freepeace13/laraversine/commit/1020ced512710e417a6269711a2cc63cf04cd177))
* add CLAUDE.md and ignore .superpowers ([#8](https://github.com/freepeace13/laraversine/issues/8)) ([57ff197](https://github.com/freepeace13/laraversine/commit/57ff197b1fddcaf81990cf4d6d902e8f631c2f17))
* add laraversine skills, agents and bin scripts ([#21](https://github.com/freepeace13/laraversine/issues/21)) ([333622a](https://github.com/freepeace13/laraversine/commit/333622a8f7850016d59944c7240203c7e5cf55aa))

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
