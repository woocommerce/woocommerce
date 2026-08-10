#!/usr/bin/env bash
# Lint branch
#
# Runs phpcs-changed, comparing the current branch to its "base" or "parent" branch.
# The base branch defaults to trunk, but another branch name can be specified as an
# optional positional argument.
#
# Example:
# ./lint-branch.sh base-branch
#
# When WC_LINT_CHECKSTYLE_FILE is set and PHPCS finds problems, the findings are also
# written to that path as a checkstyle report, for a workflow step to feed to cs2pr,
# which turns them into inline annotations on 'Files changed'. See the
# 'Lint: PHP inline annotations' step in .github/workflows/ci.yml.

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

# phpcs gets its own status besides the shared accumulator: the checkstyle report
# below must be tied to phpcs itself failing, not to any other check that sets status.
phpcsStatus=0
composer exec phpcs-changed -- -s --git --git-base $baseBranch $changedFiles || phpcsStatus=1
[[ $phpcsStatus -eq 1 ]] && status=1

# The readable report above is the log people dig into; this re-runs the same check
# only to render the same findings as checkstyle for cs2pr (phpcs-changed can only
# emit one format per run). Guarded on failure so green runs never pay for it.
#
# GitHub resolves annotation paths from the repository root, so every path needs this
# directory's prefix or the annotation silently never attaches to the diff. phpcs-changed
# reports the git path (already prefixed) when it resolves a file through git, but a
# path relative to this directory when it falls back to plain phpcs, so strip the prefix
# before adding it: that lands on the right path either way.
if [[ -n $WC_LINT_CHECKSTYLE_FILE && $phpcsStatus -eq 1 ]]; then
    prefix=$(git rev-parse --show-prefix)
    composer exec phpcs-changed -- --git --git-base $baseBranch --report=checkstyle $changedFiles |
        sed -e "s|<file name=\"${prefix}|<file name=\"|g" \
            -e "s|<file name=\"|<file name=\"${prefix}|g" > "$WC_LINT_CHECKSTYLE_FILE"
fi

# Also verify that no new PHP functions are added.
php ./bin/check-new-functions.php HEAD "$baseBranch" || status=1

exit $status
