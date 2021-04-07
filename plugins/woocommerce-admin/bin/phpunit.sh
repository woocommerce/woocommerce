#!/usr/bin/env bash
WORKING_DIR="$PWD"
cd "$WP_CORE_DIR/wp-content/plugins/woocommerce-admin/"
if [[ {$COMPOSER_DEV} == 1 || "$(php -r "echo version_compare(PHP_VERSION,'8.0','>=');")" ]]; then
	./vendor/bin/phpunit --version
	if [[ {$RUN_RANDOM} == 1 ]]; then
		./vendor/bin/phpunit -c phpunit.xml.dist --order-by=random
	else
		./vendor/bin/phpunit -c phpunit.xml.dist
	fi
else
	phpunit --version
	phpunit -c phpunit.xml.dist
fi
TEST_RESULT=$?
cd "$WORKING_DIR"
exit $TEST_RESULT
