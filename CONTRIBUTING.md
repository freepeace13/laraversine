# Contributing

Thanks for helping improve Laraversine.

## Workflow

1. Fork the repository and branch from `main`.
2. Make your change and add or update tests.
3. Run the checks locally:
   ```bash
   vendor/bin/phpunit
   vendor/bin/pint
   ```
4. Open a pull request **against `main`**.

Every PR needs a passing `tests` and `lint` run and an approving review before it is merged. PRs are squash-merged.

## PR titles

The PR title becomes the commit message on `main` and drives versioning and the changelog, so it must follow [Conventional Commits](https://www.conventionalcommits.org):

| Title | Release effect |
|---|---|
| `fix: handle null coordinates` | patch (1.0.0 → 1.0.1) |
| `feat: add bounding box scope` | minor (1.0.0 → 1.1.0) |
| `feat!: drop Laravel 11` or a `BREAKING CHANGE:` footer | major (1.0.0 → 2.0.0) |
| `docs:`, `test:`, `ci:`, `chore:`, `refactor:` | no release on their own |

A `pr-title` check enforces this.

## Releases

Releases are automated with [release-please](https://github.com/googleapis/release-please). Reviewed changes accumulate on `main`, and a bot keeps a single "Release PR" open with the next version and changelog. A maintainer merges that PR when the batch is ready, which tags `vX.Y.Z`; Packagist publishes the tag. Do not edit `CHANGELOG.md` or tag versions by hand.
