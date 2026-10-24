# Geohash Driver Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add an opt-in `geohash` search driver that narrows `nearby()`/`nearest()` by prefix ranges on a stored, indexed geohash column and still finishes with the exact haversine check.

**Architecture:** A pure `Geohash` encoder and a `GeohashCover` (longest prefix whose cell is at least the radius, plus the 8 neighbours) feed `GeohashDriver`, a new `SearchDriver` registered in `SearchManager`. Unreliable covers delegate to `SqlDriver`. A `saving` hook in `HasGeolocation` keeps the column in sync, and an artisan command repairs and verifies it.

**Tech Stack:** PHP 8.3+, Laravel 12/13, PHPUnit via Orchestra Testbench, Pint. MySQL, MariaDB, PostgreSQL, SQLite.

**Spec:** `docs/superpowers/specs/2026-10-24-geohash-driver-design.md`

## Global Constraints

- Branch `feat/geohash-driver` from `2.x`; PR targets `2.x`; title `feat: add an opt-in geohash search driver`. No AI attribution anywhere.
- PHP 8.3+, Laravel 12 and 13 (as `2.x`). No new dependency, no database extension.
- Coordinates are always `[latitude, longitude]`.
- A driver must never return a row on the grid alone: the exact haversine check always runs last.
- Same rows as `SqlDriver` for the same data and query (the brute-force oracle enforces it).
- Radius and bounding-box bounds are inlined as `%.8F` literals (PDO binds strings; SQLite compares them as greater than any number). Prefix bounds are strings and may be bound.
- No migration ships for users; the default driver, config defaults, API and existing migrations are unchanged.
- Hash length 12; geohash alphabet `0123456789bcdefghjkmnpqrstuvwxyz`.
- Do not edit `CHANGELOG.md` or tag versions.

## Review Focus

Failure modes the spec implies that no task's main tests would hit; each has a pinning test in the task named.

1. Existing models using `HasGeolocation` with no geohash column (`Store`, `Region`) must save exactly as before: the hook is inert unless the effective driver is `geohash` (Task 6).
2. Rows changed behind the hook (`query()->update()`) keep a stale hash; `--verify` must report them and a repair must fix them (Task 7).
3. `search_by = mile`: cell size must be compared in the configured unit (Task 3).
4. Points at exactly ±90 / ±180 and rows on cell boundaries must encode into a cell inside the cover (Tasks 1 and 2).
5. Rows with NULL coordinates (and so a NULL hash) are never returned, including on PostgreSQL where `LEAST` ignores NULL, and never count toward the NULL-hash fallback limit (Task 5).

---

### Task 1: `Geohash` encoder

**Files:**
- Create: `src/Geohash.php`
- Test: `tests/GeohashTest.php`

**Interfaces:**
- Produces:
  - `Geohash::LENGTH = 12`
  - `Geohash::encode(float $latitude, float $longitude, int $length = self::LENGTH): string`
  - `Geohash::bounds(string $hash): array{0: float, 1: float, 2: float, 3: float}` ordered `[minLat, maxLat, minLng, maxLng]`
  - `Geohash::neighbours(string $hash): list<string>`: the adjacent cells of the same length. Wraps longitude across ±180; omits cells beyond a pole.
  - `Geohash::cellSize(int $length): array{0: float, 1: float}` as `[heightDegrees, widthDegrees]`

- [ ] **Step 1: Branch and commit the design and plan**

```bash
git switch -c feat/geohash-driver
git add docs/superpowers
git commit -m "docs: add geohash driver design and plan"
```

- [ ] **Step 2: Write the failing tests** in `tests/GeohashTest.php` (plain `PHPUnit\Framework\TestCase`, `#[Test]` attributes like the rest of the suite):
  - `it_encodes_known_vectors`: `encode(57.64911, 10.40744, 11) === 'u4pruydqqvj'`, `encode(42.6, -5.6, 5) === 'ezs42'`.
  - `it_encodes_the_extreme_corners`: `encode(-90, -180) === '000000000000'`, `encode(90, 180) === str_repeat('z', 12)`.
  - `bounds_contain_the_encoded_point`: for 500 seeded random points, the point lies inside `bounds(encode(...))` at lengths 1 to 12.
  - `neighbours_are_adjacent_distinct_cells`: for `ezs42` there are 8 distinct hashes of length 5, none equal to the input, and each neighbour's bounds touch the input's bounds on an edge or corner.
  - `neighbours_wrap_the_antimeridian`: a hash whose east edge is 180 has a neighbour whose west edge is -180.
  - `neighbours_stop_at_the_poles`: a top-row hash has 5 neighbours.

- [ ] **Step 3: Run to verify they fail**

Run: `vendor/bin/phpunit tests/GeohashTest.php`
Expected: FAIL, class `Laraversine\Geohash` not found.

- [ ] **Step 4: Implement** `final class Geohash` in `src/Geohash.php` with the signatures above. Standard bit interleave (longitude first), base32 alphabet from Global Constraints. Clamp so 90 and 180 land in the last cell. `neighbours` encodes the cell centre offset by one cell size in each direction, wrapping longitude and dropping out-of-range latitude.

- [ ] **Step 5: Run to verify they pass**

Run: `vendor/bin/phpunit tests/GeohashTest.php && vendor/bin/pint --test`
Expected: PASS, no lint output.

- [ ] **Step 6: Commit**

```bash
git add src/Geohash.php tests/GeohashTest.php
git commit -m "feat: add a geohash encoder"
```

---

### Task 2: `GeohashCover`

**Files:**
- Create: `src/Drivers/GeohashCover.php`
- Test: `tests/Drivers/GeohashCoverTest.php`

**Interfaces:**
- Consumes: `Geohash::encode/bounds/neighbours/cellSize`, `BoundingBox::around(Coordinates, float, string): ?BoundingBox` (`longitudeRanges` null means a pole is inside; more than one range means the antimeridian is crossed), `Haversine::earthRadius(string $unit): float`.
- Produces: `final class GeohashCover`
  - `public readonly int $precision`
  - `public readonly array $ranges`: `list<array{0: string, 1: string}>`, each a 12-character `[low, high]` pair (prefix padded with `0` and with `z`)
  - `GeohashCover::around(Coordinates $origin, float $radius, string $unit): ?self`. Null means the cover is unreliable and the caller must fall back.

- [ ] **Step 1: Write the failing tests**:
  - `it_picks_the_longest_prefix_at_least_the_radius`: origin `[7.1, 125.6]`, radius 1 km gives precision 5; radius 5 km gives precision 4; each has between 1 and 9 ranges.
  - `it_returns_null_when_the_radius_is_too_large`: `[0, 0]`, 5000 km gives null.
  - `it_returns_null_near_a_pole`: `[89.95, 10]`, 5 km gives null.
  - `it_returns_null_across_the_antimeridian`: `[0, 179.95]`, 5 km gives null.
  - `it_uses_the_configured_unit`: `[7.1, 125.6]`, radius 1 mile in unit `mile` gives a precision no longer than the 1 km result (1 mile is farther than 1 km).
  - `every_point_within_the_radius_falls_in_a_range` (Review Focus 4): seed `DatasetFactory::uniform(2000, 9)` and `cluster(2000, 8, 7.1, 125.6, 0.5)` in memory (no database). For 100 seeded origins and radii in `[0.5, 5, 50, 500]` km where the cover is non-null, every row with `BruteForce::within(...)` has `Geohash::encode(lat, lng)` inside at least one `[low, high]` (string comparison).

- [ ] **Step 2: Run to verify they fail**

Run: `vendor/bin/phpunit tests/Drivers/GeohashCoverTest.php`
Expected: FAIL, class not found.

- [ ] **Step 3: Implement `GeohashCover::around`** in `src/Drivers/GeohashCover.php`:
  1. Return null if `BoundingBox::around()` is null, has null `longitudeRanges`, or has more than one range.
  2. Let `highest = min(90, |lat| + radius/earthRadius in degrees)`. For `p` from 12 down to 1, accept the first `p` whose cell height in kilometres, and whose cell width in kilometres multiplied by `cos(highest)`, are both at least `radius` (km conversion uses `Haversine::earthRadius($unit)`). None accepted gives null.
  3. Cells are the origin's cell plus `Geohash::neighbours()`. Ranges are `[prefix . str_repeat('0', 12 - p), prefix . str_repeat('z', 12 - p)]`, which stay valid hash characters so they are collation-safe.

- [ ] **Step 4: Run to verify they pass**

Run: `vendor/bin/phpunit tests/Drivers/GeohashCoverTest.php && vendor/bin/pint --test`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add src/Drivers/GeohashCover.php tests/Drivers/GeohashCoverTest.php
git commit -m "feat: compute the geohash cell cover for a radius"
```

---

### Task 3: `GeohashDriver` (full hashes) and contract

**Files:**
- Create: `src/Drivers/GeohashDriver.php`, `tests/database/migrations/2026_01_01_000002_add_geohash_to_destinations_table.php`, `tests/Drivers/GeohashDriverContractTest.php`, `tests/Drivers/GeohashDriverTest.php`
- Modify: `src/Haversine.php`, `src/Drivers/SqlDriver.php`, `src/SearchManager.php`, `src/Concerns/HasGeolocation.php`, `config/laraversine.php`, `tests/Drivers/SearchDriverContract.php`, `tests/Support/DatasetFactory.php`

**Interfaces:**
- Consumes: `GeohashCover::around`, `SqlDriver::applyRadius`, `Haversine::forQuery`.
- Produces:
  - `Haversine::applyExact(Builder $query, Coordinates $origin, float $radius, string $unit): void` adds the `AS distance` select (keeping the `select table.*` default) and the `<= radius` literal filter. Extracted from `SqlDriver`, which now calls it, behaviour unchanged.
  - `HasGeolocation::getGeohashColumn(): string` (reads a `geohashColumn` property with `property_exists()`, default `'geohash'`), `getQualifiedGeohashColumn(): string`, `usesGeohash(): bool` (the model's effective driver name, from `getGeolocationDriverName() ?? config('laraversine.driver')`, is `'geohash'`).
  - `final class GeohashDriver implements SearchDriver`, `__construct(private SqlDriver $fallback)`. Registered as `'geohash'` in `SearchManager::createGeohashDriver()`.
  - `DatasetFactory::seed(array $rows, bool $geohash = false): void`; with `$geohash` it adds a `geohash` value (`Geohash::encode`) to each row.
  - `SearchDriverContract::seed(array $rows): void` (protected, default `DatasetFactory::seed($rows)`); the contract's tests call it instead of `DatasetFactory::seed`.

- [ ] **Step 1: Refactor safely.** Extract `Haversine::applyExact` from `SqlDriver` and make `SqlDriver` call it. Run `vendor/bin/phpunit`; expected: PASS with no test changes. Commit `refactor: extract the exact distance filter`.

- [ ] **Step 2: Write the failing tests.**
  - Test migration adds a nullable string(12) `geohash` column with an index to `destinations`.
  - `GeohashDriverContractTest extends SearchDriverContract`: `driverName()` returns `'geohash'`, `seed()` calls `DatasetFactory::seed($rows, true)`. It must pass `nearby_matches_brute_force`, `nearest_matches_brute_force` and `nearest_finds_a_single_far_away_row` unchanged (poles, antimeridian and wide radii exercise the fallback).
  - `GeohashDriverTest`:
    - `it_narrows_with_geohash_prefix_ranges`: the SQL from `Destination::nearby([7.1, 125.6], 1)->toRawSql()` contains `geohash` and `BETWEEN`.
    - `it_uses_miles_when_configured` (Review Focus 3): with `laraversine.search_by = mile`, results for a seeded cluster equal `BruteForce::within($stored, $origin, $radius, 'mile')`.
    - `it_falls_back_to_the_sql_driver_for_huge_radii`: for radius 6000 km the SQL does not contain `geohash`.
    - `getGeohashColumn_defaults_and_honours_the_property` using an anonymous model with `protected $geohashColumn = 'hash'`.

- [ ] **Step 3: Run to verify they fail**

Run: `vendor/bin/phpunit tests/Drivers/GeohashDriverContractTest.php tests/Drivers/GeohashDriverTest.php`
Expected: FAIL (driver `geohash` not supported).

- [ ] **Step 4: Implement** `GeohashDriver::applyRadius(Builder $query, Coordinates $origin, float $radius, string $unit): Builder`. Build the cover; a null cover returns `$this->fallback->applyRadius(...)`. Otherwise call `Haversine::applyExact`, then add `whereRaw` on the wrapped qualified geohash column with the ranges ORed in parentheses (`col BETWEEN ? AND ?`, bound strings). Add `getGeohashColumn`, `getQualifiedGeohashColumn` and `usesGeohash` to the trait, `createGeohashDriver()` to `SearchManager`, and update the `driver` comment in `config/laraversine.php` to `Supported: "sql", "geohash"` (default stays `'sql'`).

- [ ] **Step 5: Run the full suite**

Run: `vendor/bin/phpunit && vendor/bin/pint --test`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add -A src config tests
git commit -m "feat: add the geohash search driver"
```

---

### Task 4: Benchmark spike (early gate)

**Files:**
- Modify: `benchmarks/Benchmark.php`
- Generated: `benchmarks/results/<driver>.md`

**Interfaces:**
- Consumes: `GeohashDriver` through `config(['laraversine.driver' => 'geohash'])`, `Geohash::encode`.

- [ ] **Step 1: Extend the harness.** `seedRows` also writes `geohash` (`Geohash::encode`) and ensures an index on `geohash`. `scenarios()` returns a third closure per scenario that sets `laraversine.driver` to `geohash` for the call and restores it. The report table gains `v2 geohash driver (ms)` and `v2 geohash rows examined` columns. The `dense` layout stays the Korea-sized cluster, which is the case the geohash driver targets.

- [ ] **Step 2: Run on every database.** Use the services in `benchmarks/docker-compose.yml`:

```bash
BENCH_SIZES=10000,100000 DB_DRIVER=sqlite composer bench
DB_DRIVER=mysql composer bench
DB_DRIVER=mariadb composer bench
DB_DRIVER=pgsql composer bench
```

Expected: `benchmarks/results/*.md` for each. Read them. Per the spec, a loss on a database does not block (ship opt-in with a caveat); note which databases and layouts win or lose.

- [ ] **Step 3: Commit**

```bash
git add benchmarks
git commit -m "test: benchmark the geohash driver against the bounding-box driver"
```

---

### Task 5: NULL-hash handling

**Files:**
- Modify: `src/Drivers/GeohashDriver.php`, `src/SearchManager.php`, `config/laraversine.php`
- Create: `src/GeohashColumn.php`, `tests/Drivers/GeohashNullRowsContractTest.php`
- Test: `tests/Drivers/GeohashDriverTest.php`

**Interfaces:**
- Produces:
  - `GeohashDriver::__construct(private SqlDriver $fallback, private int $nullLimit)`. `SearchManager` passes `(int) config('laraversine.geohash.null_limit', 1000)`; add `'geohash' => ['null_limit' => 1000]` to `config/laraversine.php`, documented as "when more than this many rows with coordinates have no hash, the whole search falls back to the sql driver".
  - `final class GeohashColumn`: `static exists(Model $model): bool` (memoised per connection and table), `static assertFor(Model $model): void` (throws `LogicException` naming the model and column), `static forget(): void`.

- [ ] **Step 1: Write the failing tests.**
  - `GeohashNullRowsContractTest extends SearchDriverContract` (driver `geohash`): `seed()` hashes every row except every third (NULL hash). The three standard tests must still pass.
  - In `GeohashDriverTest`:
    - `rows_with_null_coordinates_are_never_returned` (Review Focus 5): insert a row with NULL latitude, longitude and geohash; for radii 1 and 5000 km it is absent from `nearby()`, on every database.
    - `null_coordinates_do_not_count_toward_the_null_limit`: with `null_limit = 2` and 5 rows that have NULL coordinates plus hashed rows, the SQL still contains `geohash` (no fallback).
    - `too_many_null_hashes_fall_back`: with `null_limit = 2` and 5 rows that have coordinates but a NULL hash, results equal brute force and the SQL does not contain `geohash`.
    - `a_missing_geohash_column_throws`: an anonymous model on the `regions` table (no geohash column) with `$geolocationDriver = 'geohash'` throws `LogicException` from `nearby()`, with a message naming the column.

- [ ] **Step 2: Run to verify they fail**

Run: `vendor/bin/phpunit tests/Drivers/GeohashNullRowsContractTest.php tests/Drivers/GeohashDriverTest.php`
Expected: FAIL.

- [ ] **Step 3: Implement.** In `applyRadius`, call `GeohashColumn::assertFor($model)` first. Run a bounded probe: more than `nullLimit` rows where the hash is NULL and latitude and longitude are NOT NULL (`$model->newQueryWithoutScopes()`, `limit($nullLimit + 1)` inside `fromSub`, then `count`). If it exceeds the limit, delegate to `$this->fallback`. Otherwise OR a clause into the prefix group: `(geohash IS NULL AND lat IS NOT NULL AND lng IS NOT NULL)`. The explicit coordinate checks keep PostgreSQL's `LEAST` from turning NULL rows into matches.

- [ ] **Step 4: Check the index is still used.** Under `DB_DRIVER=mysql` and `pgsql`, run `EXPLAIN` on `Destination::nearby([7.1, 125.6], 1)` with 1% of hashes NULL. Expected: the geohash index is used with the OR form. If either database cannot, change the NULL form (`UNION` or a separate pass, the builder's call per the spec) and record why in the commit message.

- [ ] **Step 5: Run the suite and commit**

Run: `vendor/bin/phpunit && vendor/bin/pint --test`
Expected: PASS.

```bash
git add -A src config tests
git commit -m "feat: keep geohash results correct for rows without a hash"
```

---

### Task 6: Model hook

**Files:**
- Modify: `src/Concerns/HasGeolocation.php`
- Create: `tests/GeohashHookTest.php`
- Modify: `tests/database/migrations/2026_01_01_000002_add_geohash_to_destinations_table.php` (also add a nullable `hash` string(12) column with an index to `stores`, for the custom-column test)

**Interfaces:**
- Consumes: `Geohash::encode`, `GeohashColumn::assertFor`, `usesGeohash()`, `Coordinates`.
- Produces: `HasGeolocation::bootHasGeolocation(): void` registers a `saving` listener. When `$model->usesGeohash()`: call `GeohashColumn::assertFor`, then set the geohash attribute if the latitude or longitude is dirty or the hash is empty. Null or invalid coordinates (`InvalidArgumentException` from `Coordinates`) set it to `null`.

- [ ] **Step 1: Write the failing tests** (`tests/GeohashHookTest.php`, extends the suite's `TestCase`):
  - `it_sets_the_hash_on_create_and_update`: with `laraversine.driver = geohash`, `Destination::create([...])->geohash === Geohash::encode(lat, lng)`. After changing the coordinates and saving, the hash changes accordingly.
  - `it_leaves_the_hash_alone_when_coordinates_are_unchanged`.
  - `null_coordinates_set_a_null_hash`.
  - `it_is_inert_for_models_without_the_geohash_column` (Review Focus 1): with the default driver, `Region` (no geohash column) and `Store` (custom `lat`/`lng`, no geohash column) create and update exactly as before; no exception, no extra attribute.
  - `it_honours_a_custom_column_and_per_model_driver`: an anonymous `Store` subclass with `$geolocationDriver = 'geohash'`, `$geohashColumn = 'hash'`, and `$latitudeColumn`/`$longitudeColumn` set to `lat`/`lng` fills `hash`, and `nearby()` on it matches brute force.

- [ ] **Step 2: Run to verify they fail**

Run: `vendor/bin/phpunit tests/GeohashHookTest.php`
Expected: FAIL.

- [ ] **Step 3: Implement** `bootHasGeolocation` as above. It must do nothing when `usesGeohash()` is false, which is what keeps existing models unchanged.

- [ ] **Step 4: Run the suite and commit**

Run: `vendor/bin/phpunit && vendor/bin/pint --test`
Expected: PASS.

```bash
git add -A src tests
git commit -m "feat: keep the geohash column in sync on save"
```

---

### Task 7: Repair command

**Files:**
- Create: `src/Console/GeohashCommand.php`, `tests/GeohashCommandTest.php`
- Modify: `src/LaraversineServiceProvider.php`

**Interfaces:**
- Produces: `final class GeohashCommand extends Illuminate\Console\Command`, signature `laraversine:geohash {model : The model class} {--verify : Report mismatches without writing} {--chunk=1000}`. Registered with `$this->commands([GeohashCommand::class])` inside the existing `runningInConsole()` block.
  - It works whether or not the effective driver is `geohash` (this is the backfill path before enabling it). It uses `getGeohashColumn()` and the latitude and longitude getters.
  - It reads with `chunkById`. The expected hash is `Geohash::encode(lat, lng)`, or `null` for NULL or invalid coordinates. Repair writes only rows that differ, through the query builder (no model events, no `updated_at` change).
  - `--verify` writes nothing, prints the mismatch count and the first 10 ids, and returns `Command::FAILURE` when any mismatch exists. Otherwise `Command::SUCCESS`. A class that does not exist or does not use `HasGeolocation` returns `Command::FAILURE` with a message.

- [ ] **Step 1: Write the failing tests** (`$this->artisan(...)`):
  - `it_backfills_missing_hashes`: rows seeded without hashes; after the command they match `Geohash::encode`; exit 0.
  - `verify_reports_stale_hashes_and_writes_nothing` (Review Focus 2): fix the hashes, then `Destination::query()->update(['latitude' => 10.0])` (bypasses the hook); `--verify` exits 1, the output mentions the mismatch count, and the stored hashes are unchanged. Running without `--verify` then makes `--verify` exit 0.
  - `verify_exits_zero_when_everything_matches`.
  - `null_coordinates_expect_a_null_hash`.
  - `it_rejects_a_class_without_the_trait`.

- [ ] **Step 2: Run to verify they fail**

Run: `vendor/bin/phpunit tests/GeohashCommandTest.php`
Expected: FAIL, command not defined.

- [ ] **Step 3: Implement** the command per the interface above and register it.

- [ ] **Step 4: Run the suite and commit**

Run: `vendor/bin/phpunit && vendor/bin/pint --test`
Expected: PASS.

```bash
git add -A src tests
git commit -m "feat: add a command to backfill and verify geohashes"
```

---

### Task 8: Documentation

**Files:**
- Modify: `README.md`, `UPGRADE.md`, `BENCHMARKS.md`, and any file under `resources/` that lists drivers (find with `grep -rn -i "driver" resources README.md`).

- [ ] **Step 1: README.** In the Drivers section: how to opt in (`'driver' => 'geohash'` or a model's `$geolocationDriver`, optional `$geohashColumn`), the column snippet (nullable `string('geohash', 12)` plus an index, with a PostgreSQL note on `COLLATE "C"` and the matching index if the benchmark or EXPLAIN showed it is needed), the backfill and verify command, the stale-hash caveat (use Eloquent saves, or re-run the command after bulk changes), the fallback cases (very large radius, near a pole, across the antimeridian, too many NULL hashes), and `laraversine.geohash.null_limit`. In "How it works", one paragraph on the prefix cover.

- [ ] **Step 2: UPGRADE.md.** Add a short optional section "Adopting the geohash driver (2.1)": nothing changes unless you opt in; add the column, run the command, switch the driver.

- [ ] **Step 3: BENCHMARKS.md.** Replace the "planned for 2.1" sentence in "Known limitation" with the measured results from Task 4: summarise the numbers, say where the geohash driver helps and where it does not, and link to `benchmarks/results/`. Report losses honestly.

- [ ] **Step 4: Verify and commit**

Run: `vendor/bin/pint --test`
Expected: no lint output.

```bash
git add README.md UPGRADE.md BENCHMARKS.md resources
git commit -m "docs: document the geohash driver"
```

---

### Task 9: Verify and open the PR

- [ ] **Step 1: Full verification.** `vendor/bin/phpunit && vendor/bin/pint --test`, then `DB_DRIVER=mysql`, `mariadb` and `pgsql` runs of `vendor/bin/phpunit`. Expected: all green. If any fails, fix before continuing.

- [ ] **Step 2: Reviews.** Run the `compat-checker` agent (the change touches SQL) and the `bc-reviewer` agent against `v2.0.0`. Expected: the default driver, config defaults, API and migrations are unchanged and no break is reported. Fix anything either reports.

- [ ] **Step 3: Open the PR** following `laraversine-fix` step 5: branch from `2.x`, target `2.x`, title `feat: add an opt-in geohash search driver`, fill in the PR template (target branch, public API question: additive only), no AI attribution, no edits to `CHANGELOG.md`. Hand off to `laraversine-merge-up` and `laraversine-release` after it merges.
