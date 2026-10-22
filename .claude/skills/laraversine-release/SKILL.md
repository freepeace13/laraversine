---
name: laraversine-release
description: Use when preparing or reviewing a Laraversine release. Checks CI, reviews the release-please PR's version and notes, runs the BC review, and hands off to the maintainer, who merges the release PR.
---

# Laraversine release

release-please keeps one release PR open per maintained branch (`chore(<branch>): release X.Y.Z`). **The maintainer merging that PR is the only approval step.** Never merge it yourself and never tag by hand.

## Procedure

1. List release PRs: `gh pr list --search "release in:title" --json number,title,baseRefName`.
2. For the one being released, check CI is green: `gh pr checks <number>`. Stop if anything is red or pending.
3. Review the proposed version against the commits it includes:
   - Any `feat!:` or `BREAKING CHANGE:` footer must mean a major bump.
   - `feat:` means at least minor; only `fix:` means patch.
   - A fix that changes results users may rely on needs a prominent note in the release body.
4. Run the `bc-reviewer` agent against the last tag, and the `compat-checker` agent for the test matrix. Report their findings.
5. Read the changelog entry. Do not edit `CHANGELOG.md` by hand; if a title is wrong, fix the source PR title and let release-please regenerate.
6. Hand off: summarise version, notable changes, CI status and reviewer findings, and ask the maintainer to merge.

## After the merge

- release-please tags `vX.Y.Z` and publishes the GitHub Release; Packagist picks up the tag.
- `merge-up.yml` opens the merge-up PR into the next branch. Continue with `laraversine-merge-up`.
- If a Trello card tracks the release on the Laraversine board, move it to done.

## Notes

- `2.x` and `main` both propose the same version while they are identical. Release from one and close the other's release PR; do not merge both.
- Read versions and dates from `laraversine-support-policy`.
