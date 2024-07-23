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
redColoured='\033[0;31m'

# Lint changed php-files to match code style.
changedFiles=$(git diff $(git merge-base HEAD $baseBranch) --relative --name-only --diff-filter=d -- '*.php')
if [[ -n $changedFiles ]]; then
	printf "Linting the following files with CodeSniffer:\n"
	printf "    %s\n" $changedFiles

    composer exec phpcs-changed -- -s --git --git-base $baseBranch $changedFiles
    if [[ $? != 0 ]]; then
    	printf "${redColoured}Unfortunately, CodeSniffer reported some violations which need to be addressed (see above).\n"
    	exit 1
	else
		printf "CodeSniffer reported no violations, well done!\n"
    fi
fi

# Lint new (excl. renamed) php-files to contain strict types directive.
newFiles=$(git diff $(git merge-base HEAD $baseBranch) --relative --name-only --diff-filter=dmr -- '*.php')
if [[ -n $newFiles ]]; then
	printf "Linting the new files for declaring strict types directive (https://www.php.net/manual/en/language.types.declarations.php#language.types.declarations.strict):\n"
	printf "    %s\n" $newFiles

	passingFiles=$(find $newFiles -type f -exec grep -xl --regexp='declare(\s*strict_types\s*=\s*1\s*);' /dev/null {} +)
	violatingFiles=$(grep -vxf <(printf "%s\n" $passingFiles | sort) <(printf "%s\n" $newFiles | sort) || echo '')
	if [[ -n "$violatingFiles" ]]; then
		printf "${redColoured}Unfortunately, some files miss 'declare( strict_types = 1)' directive and need to be updated:\n"
		printf "${redColoured}    %s\n" $violatingFiles
		exit 1
	else
		printf "Strict types directive linting reported no violations, well done!\n"
	fi
fi
