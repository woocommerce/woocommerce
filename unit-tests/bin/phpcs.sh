#!/usr/bin/env bash

if [[ ${RUN_PHPCS} == 1 ]]; then
	CHANGED_FILES=`git diff --name-only --diff-filter=ACMR $TRAVIS_COMMIT_RANGE | grep \\\\.php | awk '{print}' ORS=' '`
	IGNORE=""

	if [ "$CHANGED_FILES" != "" ]; then
		echo "Running Code Sniffer."
		cd "$WP_CORE_DIR/wp-content/plugins/woocommerce-rest-api/"
		./vendor/bin/phpcs --ignore=$IGNORE --encoding=utf-8 -s -n -p $CHANGED_FILES
	fi
fi
