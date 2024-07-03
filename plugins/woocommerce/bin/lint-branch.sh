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

# Lint changed php-files to match code style.
changedFiles=$(git diff $(git merge-base HEAD $baseBranch) --relative --name-only --diff-filter=d -- '*.php')
if [[ -n $changedFiles ]]; then
    composer exec phpcs-changed -- -s --git --git-base $baseBranch $changedFiles
fi

# Lint new (excl. renamed) php-files to contain strict types directive.
newFiles=$(git diff $(git merge-base HEAD $baseBranch) --relative --name-only --diff-filter=dmr -- '*.php')
if [[ -n $newFiles ]]; then
	passingFiles=$(find $newFiles -type f -exec grep -xl --regexp='declare(\s*strict_types\s*=\s*1\s*);' /dev/null {} +)
	violatingFiles=$(grep -vxf <(printf "%s\n" $passingFiles | sort) <(printf "%s\n" $newFiles | sort))
	if [[ -n "$violatingFiles" ]]; then
		redColoured='\033[0;31m'
		printf "${redColoured}Following files are missing 'declare( strict_types = 1)' directive:\n"
		printf "${redColoured}%s\n" $violatingFiles
		exit 1
	fi
fi
