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
# When WC_PHPCS_CHECKSTYLE_FILE is set and PHPCS finds problems, the findings are also
# written to that path as a checkstyle report, for a workflow step to feed to cs2pr,
# which turns them into inline annotations on 'Files changed'. See the
# 'Lint: PHP inline annotations' step in .github/workflows/ci.yml.

baseBranch=${1:-"origin/trunk"}

# Validated outside the process substitution below, where a failure (unfetched base
# branch, shallow clone) would be swallowed and read as "no changed files": exit 0
# with PHPCS never run.
mergeBase=$(git merge-base HEAD "$baseBranch") || exit 1

# -z plus the read loop keeps one path per argument: a filename with a space or a
# glob character would otherwise split into several bogus arguments. (mapfile is
# not an option; macOS ships bash 3.2, which doesn't have it.)
changedFiles=()
while IFS= read -r -d '' file; do
    changedFiles+=("$file")
done < <(git diff -z "$mergeBase" --relative --name-only --diff-filter=d -- '*.php')

# Only complete this if changed files are detected.
if [[ ${#changedFiles[@]} -eq 0 ]]; then
    echo "No changed files detected."
    exit 0
fi

# Run all checks even if an earlier one fails, then report a non-zero status if any failed,
# so a failure in one check is never masked by a later one passing.
status=0

# Cache phpcs output only in CI, where the checkstyle render below re-runs the same
# check: with the cache primed by the first run, the re-run is mostly cache reads.
# Local runs stay cache-free, so no .phpcs-changed-cache file is left behind.
cacheArgs=()
[[ -n $WC_PHPCS_CHECKSTYLE_FILE ]] && cacheArgs=('--cache')

# phpcs gets its own status besides the shared accumulator: the checkstyle report
# below must be tied to phpcs itself failing, not to any other check that sets status.
phpcsStatus=0
composer exec phpcs-changed -- -s --git --git-base "$baseBranch" "${cacheArgs[@]}" "${changedFiles[@]}" || phpcsStatus=1
status=$phpcsStatus

# The readable report above is the log people dig into; this re-runs the same check
# only to render the same findings as checkstyle for cs2pr (phpcs-changed can only
# emit one format per run). Guarded on failure so green runs never pay for it.
#
# GitHub resolves annotation paths from the repository root, so every path must carry
# this directory's prefix exactly once or the annotation silently never attaches to
# the diff. phpcs-changed names findings two ways (verified in v2.12.0, incl. locally
# with a planted violation): for modified files, getNewMessages() renames them to the
# `git diff --no-prefix` header path, which is repository-rooted; for new files there
# is no diff, so the cwd-relative CLI path survives. Stripping the prefix before
# adding it normalizes both forms to a single prefix.
if [[ -n $WC_PHPCS_CHECKSTYLE_FILE && $phpcsStatus -eq 1 ]]; then
    prefix=$(git rev-parse --show-prefix)
    composer exec phpcs-changed -- --git --git-base "$baseBranch" --report=checkstyle "${cacheArgs[@]}" "${changedFiles[@]}" |
        sed -e "s|<file name=\"${prefix}|<file name=\"|g" \
            -e "s|<file name=\"|<file name=\"${prefix}|g" > "$WC_PHPCS_CHECKSTYLE_FILE"
fi

# Also verify that no new PHP functions are added.
php ./bin/check-new-functions.php HEAD "$baseBranch" || status=1

exit $status
