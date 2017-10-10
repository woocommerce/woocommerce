#!/usr/bin/env bash

if [[ $TRAVIS_PHP_VERSION == '7.1' ]]; then
	composer install

	CHANGED_FILES=`git diff --name-only --diff-filter=ACMR $TRAVIS_COMMIT_RANGE | grep \\\\.php | awk '{print}' ORS=' '`
	IGNORE="tests/cli/,apigen/,includes/gateways/simplify-commerce/includes/,includes/libraries/,includes/api/legacy/"

	if [ "$CHANGED_FILES" != "" ]; then
		echo "Running Code Sniffer."
		./vendor/bin/phpcs --ignore=$IGNORE --standard=./phpcs.ruleset.xml --encoding=utf-8 -n -p $CHANGED_FILES
	fi
fi
