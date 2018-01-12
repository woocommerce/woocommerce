#!/usr/bin/env bash

if [[ ${RUN_PHPCS} == 1 ]]; then
	CHANGED_FILES=`git diff --name-only --diff-filter=ACMR $TRAVIS_COMMIT_RANGE | grep \\\\.php | awk '{print}' ORS=' '`
	DEV_FILES=`git diff --name-only --diff-filter=ACMR $TRAVIS_COMMIT_RANGE | egrep '^tests/.+\.php' | awk '{print}' ORS=' '`
	IGNORE="tests/cli/,apigen/,includes/gateways/simplify-commerce/includes/,includes/libraries/,includes/api/legacy/"

	if [ "$CHANGED_FILES" != "" ]; then
		echo "Running Code Sniffer."
		./vendor/bin/phpcs --ignore=$IGNORE --encoding=utf-8 -n -p $CHANGED_FILES
	fi

	if [ "$DEV_FILES" != "" ]; then
		echo "Running PHP Compatibility checks for development files (PHP 7.0+)."
		./vendor/bin/phpcs --encoding=utf-8 -n -p --standard=PHPCompatibility --runtime-set testVersion 7.0- $DEV_FILES
	fi
fi
