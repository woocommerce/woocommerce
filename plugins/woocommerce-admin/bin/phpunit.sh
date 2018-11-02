#!/usr/bin/env bash
WORKING_DIR="$PWD"
cd "$WP_CORE_DIR/wp-content/plugins/wc-admin/"
phpunit --version
phpunit -c phpunit.xml.dist
TEST_RESULT=$?
cd "$WORKING_DIR"
exit $TEST_RESULT
