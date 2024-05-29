#!/usr/bin/env bash
# Lint branch
#
# Runs eslint, comparing the current branch to its "base" or "parent" branch.
# The base branch defaults to trunk, but another branch name can be specified as an
# optional positional argument.
#
# Example:
# ./eslint-branch.sh base-branch

baseBranch=${1:-"trunk"}

# shellcheck disable=SC2046
changedFiles=$(git diff $(git merge-base HEAD $baseBranch) --relative --name-only --diff-filter=d -- '*.js' '*.ts' '*.tsx')

# Only complete this if changed files are detected.
if [[ -z $changedFiles ]]; then
    echo "No changed files detected."
    exit 0
fi

# shellcheck disable=SC2086
pnpm eslint $changedFiles
