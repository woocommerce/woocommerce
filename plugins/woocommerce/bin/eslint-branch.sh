#!/usr/bin/env bash
# Lint branch
#
# Runs eslint, comparing the current branch to its "base" or "parent" branch.
# The base branch defaults to trunk, but another branch name can be specified as an
# optional positional argument.
#
# Example:
# ./eslint-branch.sh base-branch
#
# When WC_ESLINT_JSON_FILE is set and ESLint finds problems, the findings are also
# written to that path as an ESLint JSON report, for a workflow step to turn into
# inline annotations on 'Files changed'. See the 'Lint: JS inline annotations' step
# in .github/workflows/ci.yml.

baseBranch=${1:-"origin/trunk"}

# shellcheck disable=SC2046
changedFiles=$(git diff $(git merge-base HEAD $baseBranch) --relative --name-only --diff-filter=d -- '*.js' '*.jsx' '*.ts' '*.tsx')

# Only complete this if changed files are detected.
if [[ -z $changedFiles ]]; then
    echo "No changed files detected."
    exit 0
fi

# A changed client/blocks file is linted with that package's own flat config,
# whose import/webpack resolver loads its webpack.config.js. That config only
# exports an iterable when WP_EXPERIMENTAL_MODULES is set, matching its lint:js.
status=0
# shellcheck disable=SC2086
WP_EXPERIMENTAL_MODULES=true pnpm eslint $changedFiles || status=$?

# The readable report above is the log people dig into; this re-runs the same check
# only to render the findings as JSON (ESLint emits one format per run). Guarded on
# failure so green runs never pay for it.
if [[ -n $WC_ESLINT_JSON_FILE && $status -ne 0 ]]; then
    # shellcheck disable=SC2086
    WP_EXPERIMENTAL_MODULES=true pnpm eslint --format json --output-file "$WC_ESLINT_JSON_FILE" $changedFiles
fi

exit $status
