## What does this change?

<!-- Short description, and the issue it closes if any. -->

## Target branch

- [ ] `1.x`: bug or security fix for the previous major
- [ ] `2.x`: fix, or backward-compatible feature, for the current stable line
- [ ] `main`: breaking change

Fixes go on the oldest supported branch that has the bug. Maintainers will retarget if needed.

## Public API

Does this change the public API or query results? (yes = use a `feat!:` title and target `main`)

- [ ] No
- [ ] Yes, and I have described it below

<!-- If yes: what breaks, and how should users upgrade? -->

## Checklist

- [ ] The PR title follows Conventional Commits
- [ ] For a fix, a test that failed before the change is included
- [ ] Tests and `vendor/bin/pint` pass locally
- [ ] I have not edited `CHANGELOG.md`
