#!/usr/bin/env bash

ENABLE_TRACKING="${ENABLE_TRACKING:-0}"

echo -e 'Activate default theme \n'
wp-env run tests-cli wp theme activate twentytwentythree

echo -e 'Update URL structure \n'
wp-env run tests-cli wp rewrite structure '/%postname%/' --hard

echo -e 'Activate Filter Setter utility plugin \n'
wp-env run tests-cli wp plugin activate filter-setter

# This plugin allows you to process queued scheduled actions immediately.
# It's used in the analytics e2e tests so that order numbers are shown in Analytics.
echo -e 'Activate Process Waiting Actions utility plugin \n'
wp-env run tests-cli wp plugin activate process-waiting-actions

echo -e 'Activate Test Helper APIs utility plugin \n'
wp-env run tests-cli wp plugin activate test-helper-apis

echo -e 'Add Customer user \n'
wp-env run tests-cli wp user create customer customer@woocommercecoree2etestsuite.com \
	--user_pass=password \
	--role=customer \
	--first_name='Jane' \
	--last_name='Smith' \
	--user_registered='2022-01-01 12:23:45'

echo -e 'Update Blog Name \n'
wp-env run tests-cli wp option update blogname 'WooCommerce Core E2E Test Suite'

echo -e 'Preparing Test Files \n'
wp-env run tests-cli sudo cp /var/www/html/wp-content/plugins/woocommerce/tests/legacy/unit-tests/importer/sample.csv /var/www/sample.csv

if [ $ENABLE_TRACKING == 1 ]; then
	echo -e 'Enable tracking\n'
	wp-env run tests-cli wp option update woocommerce_allow_tracking 'yes'
fi

echo -e 'Upload test images \n'
wp-env run tests-cli wp media import './test-data/images/image-01.png' './test-data/images/image-02.png' './test-data/images/image-03.png'
