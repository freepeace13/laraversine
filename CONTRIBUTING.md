# Contributing

Thanks for helping improve Laraversine.

## Branches

Laraversine keeps one branch per major version. Fixes land on the oldest supported branch that has the bug and are merged upward (`1.x` → `2.x` → `main`). Nothing is merged downward or cherry-picked.

| Branch | Purpose | Accepts |
|---|---|---|
| `1.x` | Previous major, maintenance | Bug and security fixes |
| `2.x` | Current stable line | Fixes and backward-compatible features |
| `main` | Next major | Breaking changes (mirrors `2.x` until 3.0 work starts) |

Pick the target branch from the type of change. A maintainer will retarget your PR rather than reject it.

| Change | Target branch |
|---|---|
| Bug fix | Oldest supported branch that has the bug |
| Backward-compatible feature | `2.x` |
| Breaking change | `main` |
| Docs or tooling shared by every version | Oldest supported branch |

## Workflow

1. Fork the repository and branch from the target branch.
2. For a fix, write a failing test first, then the fix. For a feature, add tests.
3. Run the checks locally:
   ```bash
   vendor/bin/phpunit
   vendor/bin/pint
   ```
4. Open a pull request against the target branch.

Every PR needs passing `tests`, `lint` and `pr-title` checks and an approving review before it is merged. PRs are squash-merged. The exception is merge-up PRs between branches, which maintainers merge with a merge commit so history stays connected.

## Is it a breaking change?

Ask: after `composer update` within the same major, could any user's code, config, data or query results break or change without them doing anything? If yes, it is a major change and belongs on `main` with a `feat!:` title.

Examples of breaking changes:

- Removing, renaming, or changing the signature or return type of a public class, method, trait, scope or macro
- Changing config keys or their defaults
- Adding a migration for existing tables
- Different query results for the same input (units, ordering, precision, ties)
- Dropping a supported PHP, Laravel or database version

Deprecate in a minor release and remove in the next major. Never do both within one major. If you are unsure, treat it as major or make it additive, such as a new method beside the old one.

## PR titles

The PR title becomes the commit message and drives versioning and the changelog, so it must follow [Conventional Commits](https://www.conventionalcommits.org):

| Title | Release effect |
|---|---|
| `fix: handle null coordinates` | patch (1.0.0 → 1.0.1) |
| `feat: add bounding box scope` | minor (1.0.0 → 1.1.0) |
| `feat!: drop Laravel 11` or a `BREAKING CHANGE:` footer | major (1.0.0 → 2.0.0) |
| `docs:`, `test:`, `ci:`, `chore:`, `refactor:` | no release on their own |

A `pr-title` check enforces this.

## Releases

Releases are automated with [release-please](https://github.com/googleapis/release-please), run separately on each maintained branch. Reviewed changes accumulate on a branch, and a bot keeps a single "Release PR" open with the next version and changelog. A maintainer merges that PR when the batch is ready, which tags `vX.Y.Z`; Packagist publishes the tag. A workflow then opens the merge-up PR into the next branch. Do not edit `CHANGELOG.md` or tag versions by hand.

## Security

Do not open a public issue for a vulnerability. See [SECURITY.md](SECURITY.md).
