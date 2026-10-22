# Benchmarks

Laraversine makes performance claims only where a benchmark backs them. This page records how to reproduce the numbers and what they were.

> **Status:** the method is final; the result tables are filled in by running the benchmark on each database. Cells marked "not measured" are not yet populated.

## Method

- **Datasets:** 10,000, 100,000 and 1,000,000 rows, each in two layouts: `uniform` (worldwide) and `dense` (every point inside one country-sized area, about 5 degrees by 3.5 degrees).
- **Index:** a composite `(latitude, longitude)` index on the table.
- **Queries:** `nearest()` with `take` 1 and 10; `nearby()` with radius 1, 5 and 50 km.
- **Compared:** *v1 brute force* (the 1.1 query: law-of-cosines distance for every row, then sort) against the *v2 SQL driver*.
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

### MySQL 8

Not measured. Run the benchmark and paste `benchmarks/results/mysql.md` here.

### MariaDB 11

Not measured. Run the benchmark and paste `benchmarks/results/mariadb.md` here.

### PostgreSQL 16

Not measured. Run the benchmark and paste `benchmarks/results/pgsql.md` here.

## Known limitation

A B-tree index narrows effectively on latitude only. When a dataset is dense in one region, the latitude band can still contain many rows. A geohash driver, planned for 2.1, addresses that case; its results will be added here when it ships.
