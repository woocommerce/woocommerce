#!/usr/bin/env bash

if [ ! -z ${CI+y} ]; then
    # In CI we want to execute the setup behind single container call, while in dev-environments we use the script as it is.
    # Inside the container the command executed from /var/www/html path as pwd
    echo -e '--> Dispatching script execution into tests-cli\n'
    # Source from the e2e-test-helpers directory mount; a single-file mount of this
    # script can surface as an empty file under Docker gRPC FUSE.
    wp-env run --debug tests-cli cp wp-content/plugins/e2e-test-helpers/test-env-setup.sh test-env-setup-ci.sh
    wp-env run --debug tests-cli sed -i -e 's/wp-env run tests-cli //' test-env-setup-ci.sh
    wp-env run --debug tests-cli bash test-env-setup-ci.sh
    exit $?
fi

# In nightly runs WooCommerce is mounted via a wp-env mapping so it installs
# under the canonical `woocommerce` folder; mapped plugins are not
# auto-activated, so activate it before any WC-dependent setup below (e.g. the
# `customer` role user). Harmless when WC is already active (PR/source-mapped).
echo -e 'Activate WooCommerce \n'
wp-env run tests-cli wp plugin activate woocommerce

echo -e 'Install twentytwenty, twentytwentytwo and storefront themes \n'
wp-env run tests-cli wp theme install storefront twentytwenty twentytwentytwo &

echo -e 'Activate default theme \n'
wp-env run tests-cli wp theme activate twentytwentythree

# Provision wp-cli.yml in-container instead of mapping it. Single-file Docker
# mounts can surface as empty files under gRPC FUSE, which would silently drop
# the apache_modules declaration that `wp rewrite ... --hard` needs to write the
# mod_rewrite block to .htaccess.
echo -e 'Provision wp-cli.yml \n'
wp-env run tests-cli bash -c 'printf "apache_modules:\n  - mod_rewrite\n" > /var/www/html/wp-cli.yml'

echo -e 'Update URL structure \n'
wp-env run tests-cli wp rewrite structure '/%postname%/' --hard

echo -e 'Activate Filter Setter utility plugin \n'
wp-env run tests-cli wp plugin activate e2e-test-helpers/filter-setter.php

# This plugin allows you to process queued scheduled actions immediately.
# It's used in the analytics e2e tests so that order numbers are shown in Analytics.
echo -e 'Activate Process Waiting Actions utility plugin \n'
wp-env run tests-cli wp plugin activate e2e-test-helpers/process-waiting-actions.php

echo -e 'Activate Test Helper APIs utility plugin \n'
wp-env run tests-cli wp plugin activate e2e-test-helpers/test-helper-apis.php

echo -e 'Install Plugin-check utility plugin \n'
wp-env run tests-cli wp plugin install plugin-check --activate

echo -e 'Add Customer user \n'
if ! wp-env run tests-cli wp user get customer --field=ID >/dev/null 2>&1; then
	wp-env run tests-cli wp user create customer customer@woocommercecoree2etestsuite.com \
		--user_pass=password \
		--role=customer \
		--first_name='Jane' \
		--last_name='Smith' \
		--user_registered='2022-01-01 12:23:45'
fi

echo -e 'Update Blog Name \n'
wp-env run tests-cli wp option update blogname 'WooCommerce Core E2E Test Suite'

echo -e 'Preparing Test Files \n'
wp-env run tests-cli sudo cp /var/www/html/wp-content/plugins/woocommerce/tests/legacy/unit-tests/importer/sample.csv /var/www/sample.csv

ENABLE_TRACKING="${ENABLE_TRACKING:-0}"

if [ $ENABLE_TRACKING == 1 ]; then
	echo -e 'Enable tracking\n'
	wp-env run tests-cli wp option update woocommerce_allow_tracking 'yes'
fi

echo -e 'Upload test images \n'
wp-env run tests-cli wp media import './test-data/images/image-01.png' './test-data/images/image-02.png' './test-data/images/image-03.png'
