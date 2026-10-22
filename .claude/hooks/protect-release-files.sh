#!/usr/bin/env bash
# Blocks edits to files owned by release-please (see CLAUDE.md > Workflow).
file=$(jq -r '.tool_input.file_path // empty')

case "$file" in
    */CHANGELOG.md|*/.release-please-manifest.json|*/release-please-config.json)
        echo "Blocked: $(basename "$file") is owned by release-please. Do not edit it by hand; use a Conventional Commit PR title instead." >&2
        exit 2
        ;;
esac

exit 0
