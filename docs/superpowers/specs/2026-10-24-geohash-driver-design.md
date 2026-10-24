# Geohash driver (v2.1)

Target branch: `2.x`. Type: backward-compatible feature (`feat:`).

## Intent

`BENCHMARKS.md` lists a known limitation: a B-tree index narrows on latitude
only, so a dataset that is dense in one region still scans a wide latitude
band. A geohash turns a coordinate into a string where a shared prefix means
the same grid cell. "Near this point" then becomes a few prefix range lookups
on one ordinary indexed column, narrowing latitude and longitude together (the
approach Redis GEO uses internally). It needs no database extension and keeps
the public API unchanged.

## Goals

- An opt-in `SearchDriver` that narrows by geohash prefix ranges and always
  finishes with the exact haversine check. A driver must never return a row on
  the grid alone.
- Correct results even when the stored column cannot be trusted (see
  "Untrusted rows").
- Same API: `nearby()`, `nearestFirst()`, `nearest()` and `distance()` behave as
  they do with the default driver.
- Works on MySQL, MariaDB, PostgreSQL and SQLite.

## Non-goals

- Changing the default driver or any existing behaviour.
- Shipping a migration (docs snippet only, see below).
- Covering every search with the geohash path. Hard cases delegate to
  `SqlDriver`.
- Detecting stale hashes at query time.

## Anti-goals

- Returning a different result set from `SqlDriver` for the same data and
  query, other than by being faster.
- Silently missing nearby rows because a hash is absent.
- A new dependency, or a database extension requirement.

## Decisions (made by the maintainer)

**Storage.** A stored, indexed geohash column, kept in sync by a model hook.

**Opt-in.** `'driver' => 'geohash'` in `config/laraversine.php`, or per model
through the existing `geolocationDriver` property. The column name comes from a
`geohashColumn` property read with `property_exists()` (the trait declares
none), defaulting to `geohash`, matching how `latitudeColumn` and
`longitudeColumn` work.

**Migration.** None ships. The docs show the `Schema` snippet (nullable string
column plus index) and a collation note. Users of their own tables copy it.

**Cell cover.** Store a full-length hash. At query time choose the longest
prefix whose cell is at least as large as the radius, then query that cell and
its 8 neighbours: up to 9 prefix ranges. The exact haversine check runs last.

**Fallback to `SqlDriver`.** The driver delegates the whole query to
`SqlDriver` when the cover is unreliable: a radius too large for any useful
cell, or a search near a pole or across the antimeridian.

**Untrusted rows.**
- Rows with a NULL hash are handled inside the query: the candidate filter is
  "matches a geohash prefix range, or hash IS NULL", then the haversine check.
  Past a share of NULL rows the driver falls back entirely.
- Stale non-null hashes (coordinates changed without the hook, for example
  `query()->update()`, `insert()`, raw SQL, imports) cannot be detected at query
  time. The docs say so.
- A repair artisan command recomputes hashes from the coordinates, with a
  verify-only mode that reports mismatches without writing. It is also the
  backfill path for existing rows.

## Definition of done

1. The driver passes `tests/Drivers/SearchDriverContract.php` against the
   brute-force oracle (`tests/Support/BruteForce.php`), including NULL-hash rows
   and every `SqlDriver` fallback, on SQLite, MySQL, MariaDB and PostgreSQL in
   CI.
2. A dense-region benchmark in `benchmarks/` shows the driver beating the
   bounding-box driver, and the results replace the "planned for 2.1" note in
   `BENCHMARKS.md`.
3. No backward-compatibility break: the `bc-reviewer` agent finds nothing.
4. README/docs and upgrade notes cover the opt-in, the column snippet, the
   repair command, the stale-hash caveat and the fallback cases.

## Constraints

- PHP 8.3+, Laravel 12 and 13 (as `2.x`).
- Coordinates are always `[latitude, longitude]`.
- Radius and bounding-box bounds are inlined as `%.8F` literals, because PDO
  binds them as strings and SQLite then compares them as greater than any
  number. Prefix range bounds are strings; check they index correctly on every
  database.
- Do not edit `CHANGELOG.md` or tag versions; release-please owns them.
- PR title follows Conventional Commits (`feat:`).

## Calls made by the author (not yet confirmed by the maintainer)

- **Same results as `SqlDriver`.** For the same data and query, the driver
  returns the same set of rows. Order and `distance()` values come from the
  existing haversine ordering, so they match `SqlDriver`. The brute-force
  oracle enforces this.
- **`nearest()`.** `Nearest` doubles the radius each round, so the driver
  computes its cover per round. Early rounds use the geohash path and later
  rounds may hit the `SqlDriver` fallback. Both must return the same rows.
- **Cell size basis.** A cell must be at least the radius in both dimensions.
  Cells are not square and narrow with latitude, so use the smaller
  dimension at the highest absolute latitude the search circle reaches. A
  cover that is too small silently drops rows, which breaks the main
  anti-goal.
- **Pole and antimeridian detection.** Reuse the logic `BoundingBox` already
  uses to decide the same cases, so the two drivers agree.
- **Model without the column.** With `'driver' => 'geohash'` and a model that
  has no geohash column, throw a `LogicException` with a clear message. Do not
  fall back silently, because that hides a misconfiguration. `LogicException`
  is the package's existing idiom for misuse.
- **Hook with null or invalid coordinates.** Set the hash to NULL, which the
  NULL-row handling already covers.
- **Repair command.** `laraversine:geohash {model}`, using the model's column
  properties. `--verify` writes nothing, lists mismatches and exits non-zero
  when it finds any, so it can run in CI.
- **Benchmark timing.** Treat the benchmark as an early spike, before the
  full build, so a loss shows up before the work is done.

## Left to the builder

- Geohash encoder and neighbour calculation, written in-house (my call: no
  dependency).
- Hash length (my call: 12 characters).
- The hook mechanism in `HasGeolocation` (my call: recompute on `saving` when
  the latitude or longitude attribute is dirty, or the hash is empty).
- Fallback thresholds: the radius ceiling and the NULL-share limit.
- The repair command's name, options and chunking.
- The form of the NULL-hash handling: `OR hash IS NULL` inline, a `UNION`, or a
  separate pass. Pick whichever the databases can still use the index for;
  the benchmark decides.
- The prefix range form (`hash >= prefix AND hash < next`, not `LIKE`), and
  the index and collation guidance per database. If a database needs a
  specific collation for the range scan to use the index (for example `C` on
  PostgreSQL), that is code and docs together: the query and the docs snippet
  must agree, so the snippet creates an index the query can actually use.
- How the benchmark dataset is built, and which regions and sizes it uses.

## If the benchmark does not show a win

Decided by the maintainer: ship it opt-in anyway. The default driver is
unchanged, so nobody is affected unless they enable it. Document where the
driver helps and where it does not, and publish the honest numbers in
`BENCHMARKS.md`. This replaces the earlier reading of "benchmark shows a win"
as a hard release gate: the benchmark must be run and its results published,
and a loss on one database is documented, not blocking.
