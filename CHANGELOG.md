# Changelog

All notable changes to `freepeace13/laraversine` are documented here.

## [2.0.1](https://github.com/freepeace13/laraversine/compare/v2.0.0...v2.0.1) (2026-10-22)


### Miscellaneous Chores

* merge 1.x into 2.x ([f476b66](https://github.com/freepeace13/laraversine/commit/f476b6623910a4d41b5de915bc4074f19127ddfb))
* merge 1.x into 2.x ([543214d](https://github.com/freepeace13/laraversine/commit/543214d946f95fe89fe8e80cfd0ad8370b388651))
* merge 1.x into 2.x ([b7e1ae2](https://github.com/freepeace13/laraversine/commit/b7e1ae225961a0ec9d00940c6630a61595836eb6))
* merge 1.x into 2.x ([f8f3174](https://github.com/freepeace13/laraversine/commit/f8f3174be2985dce09b3f40bccae522aca88ed38))

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
* add Laravel Boost guidelines and skill ([ee9b1c1](https://github.com/freepeace13/laraversine/commit/ee9b1c1fd1937653bcd7dbe84250e14f0246876c))
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
* add laraversine skills, agents and bin scripts ([#21](https://github.com/freepeace13/laraversine/issues/21)) ([333622a](https://github.com/freepeace13/laraversine/commit/333622a8f7850016d59944c7240203c7e5cf55aa))
* export-ignore .claude and bin from dist archives ([#27](https://github.com/freepeace13/laraversine/issues/27)) ([7ebee61](https://github.com/freepeace13/laraversine/commit/7ebee61626fe4e8b43bce26f299cbb1e369083a2))
* merge 1.x into 2.x ([2a672c8](https://github.com/freepeace13/laraversine/commit/2a672c81ce6736153ef6f12ae60de4bdb1c72276))
* merge 1.x into 2.x ([1b76f89](https://github.com/freepeace13/laraversine/commit/1b76f89d0d7faaa0d34b4eddf220b5e196ee1613))
* merge 1.x into 2.x ([d956e80](https://github.com/freepeace13/laraversine/commit/d956e8097017404c73cfda0f20f515c24ff0b59e))
* merge 1.x into 2.x ([29fa2bf](https://github.com/freepeace13/laraversine/commit/29fa2bfe5edba36a445bd2aba8d5bef4fd22214d))
* merge 1.x into 2.x ([7b81683](https://github.com/freepeace13/laraversine/commit/7b81683f668b40b7e39d6b6c297b5dfb897af271))
* merge 1.x into 2.x ([ea4a06d](https://github.com/freepeace13/laraversine/commit/ea4a06d5212bb7f474901e1800811b9c24c2d466))
* merge 1.x into 2.x ([0643185](https://github.com/freepeace13/laraversine/commit/064318550d3a94dd849e9651df81a196a4169fc3))
* merge 1.x into 2.x ([925aac0](https://github.com/freepeace13/laraversine/commit/925aac05e89cc5e2c094071d28bdc80894ac6d62))
* merge 1.x into 2.x ([45a3945](https://github.com/freepeace13/laraversine/commit/45a3945dda052e26a0c852488397e7fcbc26fd2e))
* merge 1.x into 2.x ([2a4d777](https://github.com/freepeace13/laraversine/commit/2a4d77748270d03521588e26c4d1e72b0e231395))
* merge 1.x into 2.x ([b3e545e](https://github.com/freepeace13/laraversine/commit/b3e545e09e463dd8886f71cc603fc017aa32d7cd))
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
