#!/usr/bin/env bash

COMMIT_RANGE="${1} ${2}"
CHANGED_FILES=`git diff --name-only --diff-filter=ACMR $COMMIT_RANGE | grep '\.php' | grep 'plugins/woocommerce/' | sed -e 's/^plugins\/woocommerce\///' | awk '{print}' ORS=' '`
IGNORE="tests/cli/,includes/libraries/,includes/api/legacy/"

if [ "$CHANGED_FILES" != "" ]; then
	echo "Changed files: $CHANGED_FILES"
	echo "Running Code Sniffer."
	echo "Default branch: $DEFAULT_BRANCH"

	PHPCS="./vendor/bin/phpcs" ./vendor/bin/phpcs-changed --git --report=full -s --ignore=${IGNORE} --git-base ${1} ${CHANGED_FILES}
else
	echo "No changes found. Skipping PHPCS run."
fi
