---
name: db-matrix
description: Run the PHPUnit suite locally against SQLite, MySQL, MariaDB and PostgreSQL using the benchmark docker containers. Use before pushing a change that touches SQL.
disable-model-invocation: true
---

# DB matrix

Run the suite against every supported database before CI does. `tests/TestCase.php` reads `DB_DRIVER` plus optional `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`. `benchmarks/docker-compose.yml` provides matching containers (database `laraversine_test`, password `root`).

Arguments: optional list of drivers to run (default: all).

## Steps

1. `vendor/bin/phpunit` for SQLite.
2. `docker compose -f benchmarks/docker-compose.yml up -d mysql mariadb postgres`, then wait until each accepts connections.
3. Run, one at a time:
   - `DB_DRIVER=mysql DB_HOST=127.0.0.1 DB_PORT=3306 vendor/bin/phpunit`
   - `DB_DRIVER=mariadb DB_HOST=127.0.0.1 DB_PORT=3307 vendor/bin/phpunit`
   - `DB_DRIVER=pgsql DB_HOST=127.0.0.1 DB_PORT=5432 DB_USERNAME=postgres vendor/bin/phpunit`
4. Report a table of driver, tests, assertions, failures. For each failure quote the test name and message, and say whether it is driver-specific (passes on SQLite).
5. Stop the containers with `docker compose -f benchmarks/docker-compose.yml down` only if this run started them.

## Rules

- If Docker is not running, say so and run only SQLite; never report a database as passing that did not run.
- Do not edit code to make a driver pass; report the failure.
