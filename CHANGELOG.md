# Changelog

All notable changes to `freepeace13/laraversine` are documented here.

## [2.0.0](https://github.com/freepeace13/laraversine/compare/v1.1.0...v2.0.0) (2026-10-22)


### ⚠ BREAKING CHANGES

* nearest(), driver architecture and required radius for 2.0 ([#10](https://github.com/freepeace13/laraversine/issues/10))
* stop auto-loading the destinations migration
* move scopes into HasGeolocation and require a radius on nearby()
* require PHP 8.3 and Laravel 12 or 13

### Features

* add bounding box math for radius pre-filtering ([0e39c73](https://github.com/freepeace13/laraversine/commit/0e39c734947ef7d6b91f3e88b2fbfe1e99ba3cad))
* add Coordinates value object ([4f1cb68](https://github.com/freepeace13/laraversine/commit/4f1cb688036fda26a16a606a3ac4051dd2a4d0b6))
* add HasGeolocation trait with configurable columns ([c040b13](https://github.com/freepeace13/laraversine/commit/c040b13adc97ce3bf94b44cfecde91ff3d841737))
* add nearest() with expanding-radius search ([261204b](https://github.com/freepeace13/laraversine/commit/261204b86c0ff9d540dd468965518d2e50f99450))
* add reproducible benchmarks ([8c8fe4a](https://github.com/freepeace13/laraversine/commit/8c8fe4ae2dd52f922f80c88ff90fff55f2eddd85))
* add SearchDriver contract and bounded SQL driver ([f700e30](https://github.com/freepeace13/laraversine/commit/f700e30eb6234988f37187e65596aae80b0e62de))
* add SearchManager, facade and driver configuration ([366b1c5](https://github.com/freepeace13/laraversine/commit/366b1c50dcf242cffb4d8b6142944661cf45ff6a))
* add true haversine SQL builder ([dc76ac9](https://github.com/freepeace13/laraversine/commit/dc76ac9ccd2da62f35e45f8dfef16c0647570e38))
* move scopes into HasGeolocation and require a radius on nearby() ([b1f29c7](https://github.com/freepeace13/laraversine/commit/b1f29c702f8bf714a2f5060f93b4a9c942a7d5a7))
* nearest(), driver architecture and required radius for 2.0 ([#10](https://github.com/freepeace13/laraversine/issues/10)) ([21d41c0](https://github.com/freepeace13/laraversine/commit/21d41c0c80eee43627e8567ee3a140731b3e4621))
* stop auto-loading the destinations migration ([eb4bb90](https://github.com/freepeace13/laraversine/commit/eb4bb90b0438f5af7f1ecdab16b15d629f2c05d8))


### Miscellaneous Chores

* add 1.x branch alias and release-please target branch ([93e23ab](https://github.com/freepeace13/laraversine/commit/93e23abafca256bee6ec546d6153cf10b47b549f))
* add 1.x release config and merge-up workflow ([1020ced](https://github.com/freepeace13/laraversine/commit/1020ced512710e417a6269711a2cc63cf04cd177))
* add CLAUDE.md and ignore .superpowers ([#8](https://github.com/freepeace13/laraversine/issues/8)) ([57ff197](https://github.com/freepeace13/laraversine/commit/57ff197b1fddcaf81990cf4d6d902e8f631c2f17))
* merge 1.x into 2.x ([2a4d777](https://github.com/freepeace13/laraversine/commit/2a4d77748270d03521588e26c4d1e72b0e231395))
* merge 1.x into 2.x ([b3e545e](https://github.com/freepeace13/laraversine/commit/b3e545e09e463dd8886f71cc603fc017aa32d7cd))
* merge 2.x into main ([f64b9ac](https://github.com/freepeace13/laraversine/commit/f64b9ace0ec0c5052ff9e1c3b9ded32596e796d3))
* merge 2.x into main ([166c680](https://github.com/freepeace13/laraversine/commit/166c680721538c4992dba8432adc3e10454502ee))
* sync main with 2.x ([f9530fc](https://github.com/freepeace13/laraversine/commit/f9530fc52c4276be94851aee10ef8eb29d604ea4))


### Build System

* require PHP 8.3 and Laravel 12 or 13 ([c7c1688](https://github.com/freepeace13/laraversine/commit/c7c16888bba9d0b9763f734989047f17e829d810))

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
