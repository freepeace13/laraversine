---
name: laraversine-merge-up
description: Use to merge Laraversine's maintained branches upward (1.x into 2.x into main) after a fix or release lands. Resolves mechanical conflicts by keeping the target's version and stops on real code conflicts.
---

# Laraversine merge-up

Changes flow `1.x` → `2.x` → `main`. Never merge downward and never cherry-pick. Merge-up PRs use a **merge commit**, not squash, so history stays connected.

`merge-up.yml` opens these PRs when a release is published. Use this skill when one has conflicts, or when a non-release change (docs, CI) needs to ride up.

## Procedure

For each pair in order (`1.x` into `2.x`, then `2.x` into `main`):

1. `git fetch origin`
2. Check the pair: `git rev-list --left-right --count origin/<base>...origin/<head>`. If the head has nothing the base lacks, skip it.
3. Dry run: `git merge-tree --write-tree origin/<base> origin/<head>`. Clean output means no conflicts.
4. If a PR from `<head>` into `<base>` is not open, open it with `gh pr create --base <base> --head <head> --title "chore: merge <head> into <base>"`. Say in the body to use a merge commit.
5. With conflicts, check out a branch from `<base>`, merge `<head>` into it and resolve:
   - `CHANGELOG.md`, `.release-please-manifest.json`, `.claude/branch-notes.md`, per-branch support and upgrade docs: keep the **target** branch's version (`git checkout --ours <file>`).
   - `composer.json` `branch-alias` and `release.yml` triggers: keep the target's, but make sure the target's own branch name is still in the trigger list.
   - Anything under `src/`, `tests/`, `config/` or `database/`: **stop** and report. Do not guess; show the conflict and ask.
6. Run `vendor/bin/phpunit` before pushing a conflict resolution.

Do the pairs in order; do not open `2.x` into `main` until the `1.x` into `2.x` PR has merged.

## Never

- Force-push, or push directly to a protected branch.
- Resolve a source conflict silently.
- Squash a merge-up PR.
