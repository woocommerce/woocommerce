#!/usr/bin/env bash
WORKING_DIR="$PWD"
cd "$WP_CORE_DIR/wp-content/plugins/woocommerce-rest-api/"
which phpunit
phpunit --version
phpunit -c phpunit.xml
TEST_RESULT=$?
cd "$WORKING_DIR"
exit $TEST_RESULT