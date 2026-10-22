---
name: laraversine-support-policy
description: Single source of truth for which Laraversine majors, PHP, Laravel and database versions are supported and until when. Read this before stating a version or end date, or deciding whether a fix belongs on a branch.
---

# Laraversine support policy

Other skills and agents read this file instead of hardcoding versions. Update it when a major ships or a window changes.

## Policy

The previous major gets bug fixes for 6 months after the next major ships, then security fixes only for a further 6 to 12 months. This is sized for a solo maintainer; promise no more than can be sustained.

## Branches and versions

| Branch | Status | PHP | Laravel | Bug fixes until | Security fixes until |
|---|---|---|---|---|---|
| `main` | Mirrors `2.x` until 3.0 work starts | 8.3+ | 12, 13 | n/a | n/a |
| `2.x` | Current stable | 8.3+ | 12, 13 | 3.0 release + 6 months | 3.0 release + 12 to 18 months |
| `1.x` | Maintenance | 8.2+ | 11, 12 | 2.0 release + 6 months | 2.0 release + 12 to 18 months |

Concrete dates: TODO, fill in once 2.0 ships. Until then, say "not yet scheduled".

Supported databases on every branch: MySQL, MariaDB, PostgreSQL, SQLite. Exact database versions are an open question; read the CI matrix in `.github/workflows/tests.yml` for what is tested.

The PHP and Laravel columns mirror `composer.json` on each branch. If they disagree, `composer.json` wins; fix this table.

## Using it

- A bug fix goes on the oldest branch that is still in its bug-fix window and has the bug.
- A branch past its bug-fix window takes security fixes only.
- Dropping a PHP, Laravel or database version is a breaking change (`feat!:`) and belongs in a new major.
- Security reports go through GitHub private security advisories (see `SECURITY.md`).
