#!/usr/bin/env bash

COMMIT_RANGE="${1}...${2}"
CHANGED_FILES=`git diff --name-only --diff-filter=ACMR $COMMIT_RANGE | grep \\\\.php | awk '{print}' ORS=' '`
IGNORE="tests/cli/,includes/libraries/,includes/api/legacy/"

echo "Changed files: $CHANGED_FILES"

if [ "$CHANGED_FILES" != "" ]; then
	echo "Running Code Sniffer."
	./vendor/bin/phpcs --ignore=$IGNORE --encoding=utf-8 -s -n -p --report-full --report-checkstyle=./phpcs-report.xml ${CHANGED_FILES}
fi
