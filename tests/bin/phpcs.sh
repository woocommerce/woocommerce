#!/usr/bin/env bash

CHANGED_FILES=`git diff --name-only --diff-filter=ACMR "${1}..${2}" | grep \\\\.php | awk '{print}' ORS=' '`
IGNORE="tests/cli/,includes/libraries/,includes/api/legacy/"
echo "Changed files: $CHANGED_FILES"
if [ "$CHANGED_FILES" != "" ]; then
	echo "Running Code Sniffer."
	# ./vendor/bin/phpcs --ignore=$IGNORE --encoding=utf-8 -s -n -p $CHANGED_FILES
	echo "${CHANGED_FILES}" | xargs -rt ./vendor/bin/phpcs --ignore=$IGNORE --encoding=utf-8 -s -n -p --report=checkstyle | cs2pr
fi
