#!/usr/bin/env bash

# Command prefix for running wp-cli against the single-container test environment
# (started via `wp-env --config .wp-env.test.json`, whose container is `cli`).
# The CI fast-path below re-runs this script inside the container with the prefix
# blanked out (WP_CLI_PREFIX=), so each command runs as a bare `wp …` in a single
# container exec instead of one `wp-env run` round-trip per command.
WP_ENV_TEST_CMD="wp-env --config .wp-env.test.json"
WP_CLI_PREFIX="${WP_CLI_PREFIX-$WP_ENV_TEST_CMD run cli}"

if [ ! -z ${CI+y} ]; then
    # In CI we execute the setup in a single container call, while in dev
    # environments we use the script as it is. Inside the container the command is
    # executed from the /var/www/html path as pwd.
    echo -e '--> Dispatching script execution into cli\n'
    # Source from the e2e-test-helpers directory mount; a single-file mount of this
    # script can surface as an empty file under Docker gRPC FUSE.
    $WP_ENV_TEST_CMD run --debug cli cp wp-content/plugins/e2e-test-helpers/test-env-setup.sh test-env-setup-ci.sh
    $WP_ENV_TEST_CMD run --debug cli env -u CI WP_CLI_PREFIX= bash test-env-setup-ci.sh
    exit $?
fi

# In nightly runs WooCommerce is mounted via a wp-env mapping so it installs
# under the canonical `woocommerce` folder; mapped plugins are not
# auto-activated, so activate it before any WC-dependent setup below (e.g. the
# `customer` role user). Harmless when WC is already active (PR/source-mapped).
echo -e 'Activate WooCommerce \n'
$WP_CLI_PREFIX wp plugin activate woocommerce

echo -e 'Install twentytwenty, twentytwentytwo and storefront themes \n'
$WP_CLI_PREFIX wp theme install storefront twentytwenty twentytwentytwo &

echo -e 'Activate default theme \n'
$WP_CLI_PREFIX wp theme activate twentytwentythree

# Provision wp-cli.yml in-container instead of mapping it. Single-file Docker
# mounts can surface as empty files under gRPC FUSE, which would silently drop
# the apache_modules declaration that `wp rewrite ... --hard` needs to write the
# mod_rewrite block to .htaccess.
echo -e 'Provision wp-cli.yml \n'
$WP_CLI_PREFIX bash -c 'printf "apache_modules:\n  - mod_rewrite\n" > /var/www/html/wp-cli.yml'

echo -e 'Update URL structure \n'
$WP_CLI_PREFIX wp rewrite structure '/%postname%/' --hard

echo -e 'Activate Filter Setter utility plugin \n'
$WP_CLI_PREFIX wp plugin activate e2e-test-helpers/filter-setter.php

# This plugin allows you to process queued scheduled actions immediately.
# It's used in the analytics e2e tests so that order numbers are shown in Analytics.
echo -e 'Activate Process Waiting Actions utility plugin \n'
$WP_CLI_PREFIX wp plugin activate e2e-test-helpers/process-waiting-actions.php

echo -e 'Activate Test Helper APIs utility plugin \n'
$WP_CLI_PREFIX wp plugin activate e2e-test-helpers/test-helper-apis.php

echo -e 'Install Plugin-check utility plugin \n'
$WP_CLI_PREFIX wp plugin install plugin-check --activate

echo -e 'Add Customer user \n'
if ! $WP_CLI_PREFIX wp user get customer --field=ID >/dev/null 2>&1; then
	$WP_CLI_PREFIX wp user create customer customer@woocommercecoree2etestsuite.com \
		--user_pass=password \
		--role=customer \
		--first_name='Jane' \
		--last_name='Smith' \
		--user_registered='2022-01-01 12:23:45'
fi

echo -e 'Update Blog Name \n'
$WP_CLI_PREFIX wp option update blogname 'WooCommerce Core E2E Test Suite'

echo -e 'Preparing Test Files \n'
$WP_CLI_PREFIX sudo cp /var/www/html/wp-content/plugins/woocommerce/tests/legacy/unit-tests/importer/sample.csv /var/www/sample.csv

ENABLE_TRACKING="${ENABLE_TRACKING:-0}"

if [ $ENABLE_TRACKING == 1 ]; then
	echo -e 'Enable tracking\n'
	$WP_CLI_PREFIX wp option update woocommerce_allow_tracking 'yes'
fi

echo -e 'Upload test images \n'
$WP_CLI_PREFIX wp media import './test-data/images/image-01.png' './test-data/images/image-02.png' './test-data/images/image-03.png'
