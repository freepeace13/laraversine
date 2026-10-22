---
name: laraversine-fix
description: Use when fixing a Laraversine bug or adding a change. Classifies it (fix, feature, breaking, shared docs/tooling), picks the target branch, writes a failing test first, and opens the PR with a Conventional Commit title.
---

# Laraversine fix

Follow CONTRIBUTING.md; this skill is the procedure. Read `laraversine-support-policy` for which branches are still maintained.

## 1. Classify

Ask: after `composer update` within the same major, could any user's code, config, data or query results break or change without them doing anything? If yes, it is major.

| Type | Title prefix | Target branch |
|---|---|---|
| Bug fix | `fix:` | Oldest supported branch that has the bug |
| Backward-compatible feature | `feat:` | `2.x` |
| Breaking change | `feat!:` | `main` |
| Docs, tests, tooling shared by every version | `docs:` `test:` `ci:` `chore:` | Oldest supported branch |
| Docs that differ per branch (upgrade guide, branch notes) | `docs:` | Each branch separately |

If unsure whether it is breaking, treat it as major or redesign it as an additive change. Deprecate in a minor; remove only in the next major.

## 2. Find the oldest branch with the bug

For a fix, check out each maintained branch from oldest up and reproduce. The fix starts on the oldest one that has it and reaches the others through merge-up, never by cherry-picking.

## 3. Failing test first

Write a test that fails for the bug. Run it and confirm it fails for the right reason. Use `tests/Support/BruteForce.php` as the oracle for query results. Only then write the fix.

## 4. Verify

```bash
vendor/bin/phpunit
vendor/bin/pint --test
```

Both must pass. Run against another database with `DB_DRIVER` if the change touches SQL (`mysql`, `mariadb`, `pgsql`).

## 5. Open the PR

- Branch from the target branch, not `main`.
- Title follows Conventional Commits; it becomes the squash commit that release-please reads.
- Fill in the PR template: target branch and the public API question.
- Do not edit `CHANGELOG.md` or tag versions.
- Do not add AI attribution to commits or the PR.

When the PR merges, a release PR and later a merge-up PR follow; hand off to `laraversine-merge-up` and `laraversine-release`.
