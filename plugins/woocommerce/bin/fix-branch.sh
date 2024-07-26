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

    composer exec phpcbf -- -s $changedFiles
    if [[ $? != 0 ]]; then
    	printf "${redColoured}Unfortunately, CodeSniffer reported some violations which need to be addressed (see above).\n"
    	exit 1
	else
		printf "CodeSniffer reported no violations, well done!\n"
    fi
fi
