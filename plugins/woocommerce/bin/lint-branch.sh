#!/bin/bash

# Lint branch
#
# Runs phpcs-changed, comparing the current branch to its "base" or "parent" branch.
# The base branch defaults to trunk, but another branch name can be specified as an
# optional positional argument.
#
# Example:
# ./lint-branch.sh base-branch

baseBranch=${1:-"trunk"}

changedFiles=$(git diff $(git merge-base HEAD $baseBranch) --relative --name-only -- '*.php')

# Only complete this if changed files are detected.
[[ -z $changedFiles ]] || composer exec phpcs-changed -- -s --git --git-base $baseBranch $changedFiles
