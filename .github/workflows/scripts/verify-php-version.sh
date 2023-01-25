#!/usr/bin/env bash

#
# Verify that the PHP version in the launched WP ENV environment is equal to expected.
#

cd $GITHUB_WORKSPACE/plugins/woocommerce
ACTUAL_PHP_VERSION=$(pnpm exec wp-env run tests-cli "wp --info | grep 'PHP version:'")
EXIT_CODE=''

echo "PHP version found in WP Env environment: \"$ACTUAL_PHP_VERSION\""
echo "Expected PHP version: \"$EXPECTED_PHP_VERSION\""

if [[ $ACTUAL_PHP_VERSION == *"$EXPECTED_PHP_VERSION"* ]]
    then
        EXIT_CODE=0
    else
        EXIT_CODE=1
fi

exit $EXIT_CODE
