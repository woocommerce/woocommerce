#!/usr/bin/env bash

# Lint branch
#
# Runs phpcbf, comparing the current branch to its "base" or "parent" branch.
# The base branch defaults to trunk, but another branch name can be specified as an
# optional positional argument.
#
# Example:
# ./fix-branch.sh base-branch

baseBranch=${1:-"trunk"}
redColoured='\033[0;31m'

# Lint changed php-files to match code style.
changedFiles=$(git diff $(git merge-base HEAD $baseBranch) --relative --name-only --diff-filter=d -- '*.php')
if [[ -n $changedFiles ]]; then
	printf "Attemping to fix the following files with Code Beautifier and Fixer:\n"
	printf "    %s\n" $changedFiles

    composer exec phpcbf -- -s $changedFiles
    if [[ $? != 0 ]]; then
    	printf "${redColoured}Unfortunately, Code Beautifier and Fixer could not fix all violations which need to be addressed manually - Please run lint-branch.sh.\n"
    	exit 1
	else
		printf "Code Beautifier and Fixer reported no violations or was able to fix everything, well done!\n"
    fi
fi
