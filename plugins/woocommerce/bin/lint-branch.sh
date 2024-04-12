#!/usr/bin/env bash
# Lint branch
#
# Runs phpcs-changed, comparing the current branch to its "base" or "parent" branch.
# The base branch defaults to trunk, but another branch name can be specified as an
# optional positional argument.
#
# Example:
# ./lint-branch.sh base-branch

baseBranch=${1:-"trunk"}

changedFiles=$(git diff $(git merge-base HEAD $baseBranch) --relative --name-only --diff-filter=d -- '*.php')

echo $changedFiles

# Only complete this if changed files are detected.
if [[ -z $changedFiles ]]; then
    echo "No changed files detected."
    exit 0
fi

composer exec phpcs-changed -- -s --git --git-base $baseBranch $changedFiles
