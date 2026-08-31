#!/usr/bin/env bash

# Command prefix for running wp-cli against the single-container E2E environment
# (started via `wp-env --config .wp-env.e2e.json`, whose container is `cli`).
# The CI fast-path below re-runs this script inside the container with the prefix
# blanked out (WP_CLI_PREFIX=), so each command runs as a bare `wp …` in a single
# container exec instead of one `wp-env run` round-trip per command.
WP_ENV_CMD="wp-env --config .wp-env.e2e.json"
WP_CLI_PREFIX="${WP_CLI_PREFIX-$WP_ENV_CMD run cli}"

if [ ! -z ${CI+y} ]; then
    # In CI we execute the setup in a single container call, while in dev
    # environments we use the script as it is. Inside the container the command is
    # executed from the /var/www/html path as pwd.
    echo -e '--> Dispatching script execution into cli\n'
    # Source from the e2e-test-bin directory mount; a single-file mount of this
    # script can surface as an empty file under Docker gRPC FUSE.
    $WP_ENV_CMD run --debug cli cp wp-content/plugins/e2e-test-bin/test-env-setup.sh test-env-setup-ci.sh
    $WP_ENV_CMD run --debug cli env -u CI WP_CLI_PREFIX= bash test-env-setup-ci.sh
    exit $?
fi

# In nightly runs WooCommerce is mounted via a wp-env mapping so it installs
# under the canonical `woocommerce` folder; mapped plugins are not
# auto-activated, so activate it before any WC-dependent setup below (e.g. the
# `customer` role user). Harmless when WC is already active (PR/source-mapped).
echo -e 'Activate WooCommerce \n'
$WP_CLI_PREFIX wp plugin activate woocommerce

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

echo -e 'Remove Blocks database request lock configuration \n'
$WP_CLI_PREFIX php -r '
$path = "/var/www/html/.htaccess";
$block = "# BEGIN WooCommerce Blocks E2E DB Lock\nphp_value auto_prepend_file /var/www/html/wp-content/plugins/woocommerce/blocks-bin/playwright/request-lock.php\n# END WooCommerce Blocks E2E DB Lock\n";
$contents = file_get_contents( $path );
if ( false === $contents ) {
	fwrite( STDERR, "Unable to read .htaccess.\n" );
	exit( 1 );
}
$updated = preg_replace( "/^" . preg_quote( $block, "/" ) . "/m", "", $contents );
if ( null === $updated ) {
	fwrite( STDERR, "Unable to remove Blocks E2E database lock configuration.\n" );
	exit( 1 );
}
if ( $updated !== $contents && false === file_put_contents( $path, $updated ) ) {
	fwrite( STDERR, "Unable to update .htaccess.\n" );
	exit( 1 );
}
if (
	0 !== substr_count( $updated, "# BEGIN WooCommerce Blocks E2E DB Lock" ) ||
	0 !== substr_count( $updated, "# END WooCommerce Blocks E2E DB Lock" ) ||
	0 !== substr_count( $updated, "php_value auto_prepend_file /var/www/html/wp-content/plugins/woocommerce/blocks-bin/playwright/request-lock.php" )
) {
	fwrite( STDERR, "Blocks E2E database lock configuration remains in .htaccess.\n" );
	exit( 1 );
}
' || exit $?

echo -e 'Add Customer user \n'
if ! $WP_CLI_PREFIX wp user get customer --field=ID >/dev/null 2>&1; then
	$WP_CLI_PREFIX wp user create customer customer@woocommercecoree2etestsuite.com \
		--user_pass=password \
		--role=customer \
		--first_name='Jane' \
		--last_name='Smith' \
		--user_registered='2022-01-01 12:23:45'
fi

echo -e 'Enable Back in Stock Notifications feature \n'
$WP_CLI_PREFIX wp option update woocommerce_feature_customer_stock_notifications_enabled 'yes'

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
