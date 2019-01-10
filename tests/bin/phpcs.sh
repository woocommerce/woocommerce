#!/usr/bin/env bash

if [[ ${RUN_PHPCS} == 1 ]]; then
	CHANGED_FILES=`git diff --name-only --diff-filter=ACMR $TRAVIS_COMMIT_RANGE | grep \\\\.php | awk '{print}' ORS=' '`
	IGNORE="tests/cli/,apigen/,includes/libraries/,includes/api/legacy/"

	if [ "$CHANGED_FILES" != "" ]; then
		echo "Running Code Sniffer."
		./vendor/bin/phpcs --ignore=$IGNORE --encoding=utf-8 -n -p $CHANGED_FILES
	fi
fi
