# Benchmarks

Laraversine makes performance claims only where a benchmark backs them. This page records how to reproduce the numbers and what they were.

> **Status:** measured once on a developer laptop (macOS) with MySQL 8, MariaDB 11 and PostgreSQL 16 in Docker, and SQLite. Treat differences of a few milliseconds as noise. Re-run it on your own hardware and data before relying on a number.

## Method

- **Datasets:** 10,000, 100,000 and 1,000,000 rows, each in two layouts: `uniform` (worldwide) and `dense` (every point inside one country-sized area, about 5 degrees by 3.5 degrees).
- **Index:** a composite `(latitude, longitude)` index on the table.
- **Queries:** `nearest()` with `take` 1 and 10; `nearby()` with radius 1, 5 and 50 km.
- **Compared:** *v1 brute force* (the 1.1 query: law-of-cosines distance for every row, then sort), the *v2 SQL driver*, and the opt-in *v2 geohash driver* (2.1).
- **Geohash column:** a nullable `string(12)` column with its own index, filled for every row.
- **Origins:** random existing points (default 10), so every query finds something. Reported latency is the median.
- **Rows examined:** MySQL and MariaDB: the sum of the session `Handler_read_*` counters. PostgreSQL: scanned plus filtered rows from `EXPLAIN (ANALYZE)`. These are approximate and not comparable across engines.

## Reproduce

```bash
composer install
docker compose -f benchmarks/docker-compose.yml up -d

DB_DRIVER=mysql   DB_HOST=127.0.0.1 DB_PORT=3306 composer bench
DB_DRIVER=mariadb DB_HOST=127.0.0.1 DB_PORT=3307 composer bench
DB_DRIVER=pgsql   DB_HOST=127.0.0.1 DB_PORT=5432 DB_USERNAME=postgres composer bench
```

Use `BENCH_SIZES=10000` for a quick run. Each run writes `benchmarks/results/<driver>.md`.

## Results

Full tables for every size are in `benchmarks/results/`. The tables below are the largest size measured.

### MySQL 8 (1,000,000 rows)

| Layout | Query | v1 brute force (ms) | v2 SQL driver (ms) | v2 geohash driver (ms) | SQL rows examined | geohash rows examined |
|---|---|---|---|---|---|---|
| uniform | nearest(take: 1) | 1008.3 | 1.4 | 1.7 | 2 | 10 |
| uniform | nearest(take: 10) | 1009.8 | 14.1 | 14.8 | 34 | 714 |
| uniform | nearby(1 km) | 874.4 | 1.3 | 1.6 | 2 | 10 |
| uniform | nearby(5 km) | 876.5 | 1.6 | 1.7 | 2 | 18 |
| uniform | nearby(50 km) | 878.2 | 3.7 | 5.0 | 8 | 320 |
| dense | nearest(take: 1) | 1131.7 | 2.5 | 6.5 | 26 | 1,013 |
| dense | nearest(take: 10) | 947.6 | 3.0 | 8.2 | 26 | 1,013 |
| dense | nearby(1 km) | 833.2 | 3.2 | 8.8 | 26 | 1,013 |
| dense | nearby(5 km) | 874.0 | 19.6 | 206.5 | 460 | 32,020 |
| dense | nearby(50 km) | 954.5 | 1263.1 | 2079.1 | 1,000,003 | 1,000,003 |

### MariaDB 11 (1,000,000 rows)

| Layout | Query | v1 brute force (ms) | v2 SQL driver (ms) | v2 geohash driver (ms) | SQL rows examined | geohash rows examined |
|---|---|---|---|---|---|---|
| uniform | nearest(take: 1) | 1060.3 | 3.6 | 2.0 | 3 | 11 |
| uniform | nearest(take: 10) | 1043.6 | 16.3 | 15.1 | 55 | 432 |
| uniform | nearby(1 km) | 880.3 | 2.3 | 1.9 | 3 | 11 |
| uniform | nearby(5 km) | 963.6 | 2.0 | 1.6 | 3 | 16 |
| uniform | nearby(50 km) | 996.7 | 3.3 | 4.2 | 23 | 184 |
| dense | nearest(take: 1) | 1015.4 | 3.4 | 6.0 | 19 | 1,004 |
| dense | nearest(take: 10) | 923.0 | 3.0 | 6.4 | 28 | 1,013 |
| dense | nearby(1 km) | 764.7 | 2.8 | 7.3 | 35 | 1,020 |
| dense | nearby(5 km) | 747.8 | 18.1 | 128.4 | 855 | 31,935 |
| dense | nearby(50 km) | 960.4 | 1268.8 | 2207.9 | 1,043,962 | 1,043,962 |

### PostgreSQL 16 (1,000,000 rows)

| Layout | Query | v1 brute force (ms) | v2 SQL driver (ms) | v2 geohash driver (ms) | SQL rows examined | geohash rows examined |
|---|---|---|---|---|---|---|
| uniform | nearest(take: 1) | 768.5 | 2.1 | 2.8 | 1 | 2 |
| uniform | nearest(take: 10) | 925.9 | 45.5 | 48.6 | 53 | 1,690 |
| uniform | nearby(1 km) | 742.3 | 7.3 | 5.2 | 1 | 2 |
| uniform | nearby(5 km) | 784.1 | 3.1 | 4.1 | 1 | 36 |
| uniform | nearby(50 km) | 818.1 | 10.8 | 9.8 | 25 | 806 |
| dense | nearest(take: 1) | 633.5 | 5.3 | 21.7 | 34 | 1,908 |
| dense | nearest(take: 10) | 620.1 | 3.5 | 7.3 | 34 | 1,908 |
| dense | nearby(1 km) | 523.8 | 6.3 | 8.2 | 34 | 1,908 |
| dense | nearby(5 km) | 567.8 | 28.3 | 120.9 | 1,062 | 34,184 |
| dense | nearby(50 km) | 676.7 | 1347.5 | 2111.9 | 72,304 | 801,881 |

### SQLite (100,000 rows)

SQLite does not report rows examined here, and uses math functions registered from PHP, so its absolute times are not comparable with the other databases.

| Layout | Query | v1 brute force (ms) | v2 SQL driver (ms) | v2 geohash driver (ms) |
|---|---|---|---|---|
| uniform | nearest(take: 1) | 188.4 | 0.3 | 0.4 |
| uniform | nearest(take: 10) | 187.7 | 3.0 | 8.6 |
| uniform | nearby(1 km) | 184.9 | 0.3 | 0.4 |
| uniform | nearby(5 km) | 184.6 | 0.3 | 0.4 |
| uniform | nearby(50 km) | 185.2 | 0.4 | 0.6 |
| dense | nearest(take: 1) | 187.1 | 0.4 | 0.9 |
| dense | nearest(take: 10) | 191.4 | 1.7 | 11.4 |
| dense | nearby(1 km) | 184.1 | 0.4 | 0.8 |
| dense | nearby(5 km) | 184.1 | 2.4 | 13.7 |
| dense | nearby(50 km) | 197.0 | 109.4 | 312.4 |

## The geohash driver

**It was not faster than the default `sql` driver in these measurements.** The `sql` driver already uses the `(latitude, longitude)` composite index, and in the `dense` layout it was written for (100,000 rows and up) the geohash driver took longer on every database, and read more rows where rows examined is reported. On the `uniform` layout the two were within a few milliseconds of each other. For example, MySQL, 1,000,000 dense rows, `nearby(5 km)`: 19.6 ms and 460 rows examined with the `sql` driver, against 206.5 ms and 32,020 rows with the geohash driver.

Likely reason, not tested: the driver searches the cell around the origin and its 8 neighbours, with cells at least as large as the radius. When a radius sits just above a cell size, those nine cells cover much more area than the circle, and every row in them has to be checked.

The driver is shipped as opt-in and the default is unchanged. Enable it only after measuring a win on your own data.

## Known limitation

A B-tree index narrows effectively on latitude only. When a dataset is dense in one region, the latitude band can still contain many rows. The 2.1 geohash driver was tried against this case and did not improve on the `sql` driver (see above), so the limitation stands.
