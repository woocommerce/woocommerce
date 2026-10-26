#!/usr/bin/env bash
# Lint branch
#
# Runs eslint, comparing the current branch to its "base" or "parent" branch.
# The base branch defaults to trunk, but another branch name can be specified as an
# optional positional argument. Further arguments are passed to ESLint.
#
# Example:
# ./eslint-branch.sh base-branch

baseBranch=${1:-"origin/trunk"}
if (( $# > 0 )); then
    shift
fi
mergeBase=$(git merge-base HEAD "$baseBranch") || exit $?

changedFiles=()
while IFS= read -r -d '' file; do
    changedFiles+=("$file")
done < <(git diff "$mergeBase" --relative --name-only -z --diff-filter=d -- '*.js' '*.jsx' '*.ts' '*.tsx')

if (( ${#changedFiles[@]} == 0 )); then
    echo "No changed files detected."
    exit 0
fi

# ESLint resolves configs per file, but suppression paths are relative to cwd.
# Run each group from its config directory so package suppressions apply too.
directories=()
fileDirectories=()
for file in "${changedFiles[@]}"; do
    directory="./$file"
    while [[ "$directory" != "." ]]; do
        directory=${directory%/*}
        for config in "$directory"/eslint.config.{js,mjs,cjs,ts,mts,cts}; do
            if [[ -f "$config" ]]; then
                break 2
            fi
        done
    done
    directory=${directory#./}
    fileDirectories+=("$directory")

    found=false
    for existing in "${directories[@]}"; do
        if [[ "$existing" == "$directory" ]]; then
            found=true
            break
        fi
    done
    if [[ "$found" == false ]]; then
        directories+=("$directory")
    fi
done

exitCode=0
for directory in "${directories[@]}"; do
    files=()
    for i in "${!changedFiles[@]}"; do
        if [[ "${fileDirectories[i]}" == "$directory" ]]; then
            files+=("./${changedFiles[i]#"$directory"/}")
        fi
    done

    # The blocks webpack resolver needs this, matching that package's lint:js.
    (
        cd -- "$directory" || exit 1
        WP_EXPERIMENTAL_MODULES=true pnpm eslint "${files[@]}" "$@"
    ) || exitCode=$?
done

exit "$exitCode"
