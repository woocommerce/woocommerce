#!/usr/bin/env bash
# Lint branch
#
# Runs eslint, comparing the current branch to its "base" or "parent" branch.
# The base branch defaults to origin/trunk. A different base can be specified as
# the first argument; all remaining arguments are passed to ESLint.
#
# Examples:
# ./eslint-branch.sh base-branch
# ./eslint-branch.sh --fix
# ./eslint-branch.sh base-branch --fix

baseBranch="origin/trunk"
if (( $# > 0 )) && [[ "$1" != -* ]]; then
    baseBranch=${1:-"origin/trunk"}
    shift
fi
mergeBase=$(git merge-base HEAD "$baseBranch") || exit $?

# Skip source paths covered by the dedicated admin and blocks lint jobs.
changedFiles=()
while IFS= read -r -d '' file; do
    changedFiles+=("$file")
done < <(git diff "$mergeBase" --relative --name-only -z --diff-filter=d -- \
    '*.js' '*.jsx' '*.ts' '*.tsx' \
    ':(exclude)client/admin/client/**' \
    ':(exclude)client/blocks/assets/**' \
    ':(exclude)client/blocks/packages/**' \
    ':(exclude)client/blocks/tests/**')

if (( ${#changedFiles[@]} == 0 )); then
    echo "No changed files detected."
    exit 0
fi

WP_EXPERIMENTAL_MODULES=true pnpm eslint "${changedFiles[@]}" "$@"
