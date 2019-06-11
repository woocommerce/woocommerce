#!/usr/bin/env bash
WORKING_DIR="$PWD"
cd "$WP_CORE_DIR/wp-content/plugins/woocommerce-rest-api"
ls
composer install
./vendor/bin/phpunit --version
./vendor/bin/phpunit -c phpunit.xml
TEST_RESULT=$?
cd "$WORKING_DIR"
exit $TEST_RESULT