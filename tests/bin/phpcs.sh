#!/usr/bin/env bash

echo '1'
echo "arg1: $1"
echo "git diff --name-only --diff-filter=ACMR ${1}"
CHANGED_FILES=`git diff --name-only --diff-filter=ACMR ${1} | grep \\\\.php | awk '{print}' ORS=' '`
IGNORE="tests/cli/,includes/libraries/,includes/api/legacy/"
echo "changed files: $CHANGED_FILES"
if [ "$CHANGED_FILES" != "" ]; then
	echo "Running Code Sniffer."
	./vendor/bin/phpcs --ignore=$IGNORE --encoding=utf-8 -s -n -p $CHANGED_FILES
fi
