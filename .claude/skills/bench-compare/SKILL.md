---
name: bench-compare
description: Run composer bench on main and on the current branch against a database and report the differences. Use after changing SqlDriver, BoundingBox, Haversine or Nearest.
disable-model-invocation: true
---

# Bench compare

Compare benchmark results before and after a change. Method and table layout are in `BENCHMARKS.md`; the benchmarks live in `benchmarks/`.

Arguments: optional driver (`mysql`, `mariadb`, `pgsql`; default `mysql`) and `BENCH_SIZES` (default `10000` for a quick run).

## Steps

1. Confirm the working tree state with `git status`. Note the current branch and `git rev-parse --short HEAD`.
2. Make sure the database is up: `docker compose -f benchmarks/docker-compose.yml up -d <service>`. Ports: mysql 3306, mariadb 3307, pgsql 5432 (`DB_USERNAME=postgres` for pgsql).
3. Run the benchmark on the current tree and copy `benchmarks/results/<driver>.md` aside (scratchpad, not the repo).
4. Create a worktree of `main` (`git worktree add <scratchpad>/bench-main main`), run `composer install` there, run the same benchmark, copy its result aside, then remove the worktree.
5. Compare per dataset, layout and query: median latency and rows examined. Report a table of base, branch and percent change. Flag any regression above 10% and any change in rows examined.

## Rules

- Do not commit anything under `benchmarks/results/` and do not edit `BENCHMARKS.md`; report numbers only.
- Rows examined are not comparable across engines; only compare a driver with itself.
- Say plainly if a run was skipped or noisy (re-run once if the spread is large).
- Leave containers running unless asked to stop them.
