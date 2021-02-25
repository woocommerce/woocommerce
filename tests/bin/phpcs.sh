#!/usr/bin/env bash

if [[ ${RUN_PHPCS} == 1 ]]; then
	echo "git diff --name-only --diff-filter=ACMR ${COMMIT_RANGE}"
	CHANGED_FILES=`git diff --name-only --diff-filter=ACMR ${COMMIT_RANGE} | grep \\\\.php | awk '{print}' ORS=' '`
	IGNORE="tests/cli/,includes/libraries/,includes/api/legacy/"

	if [ "$CHANGED_FILES" != "" ]; then
		echo "Running Code Sniffer."
		vendor/bin/phpcs --ignore=$IGNORE --encoding=utf-8 -s -n -p $CHANGED_FILES
	fi
fi
