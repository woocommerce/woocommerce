#!/usr/bin/env bash
# Lint branch
#
# Runs phpcs-changed, comparing the current branch to its "base" or "parent" branch.
# The base branch defaults to trunk, but another branch name can be specified as an
# optional positional argument.
#
# Example:
# ./lint-branch.sh base-branch

baseBranch=${1:-"origin/trunk"}

changedFiles=$(git diff $(git merge-base HEAD $baseBranch) --relative --name-only --diff-filter=d -- '*.php')

# Only complete this if changed files are detected.
if [[ -z $changedFiles ]]; then
    echo "No changed files detected."
    exit 0
fi

# Run all checks even if an earlier one fails, then report a non-zero status if any failed,
# so a failure in one check is never masked by a later one passing.
status=0

composer exec phpcs-changed -- -s --git --git-base $baseBranch $changedFiles || status=1

# Also verify that no new PHP functions are added.
php ./bin/check-new-functions.php HEAD "$baseBranch" || status=1

exit $status
