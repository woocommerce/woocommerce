#!/usr/bin/env bash

# The steps after the seed are as easy to lose as the steps inside it: a
# silently skipped preference write or translation build is restored before
# every test along with everything else. Fail on the first broken one.
set -euo pipefail

script_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# Command prefix for running wp-cli against the single-container E2E environment
# (started via `wp-env --config .wp-env.e2e.json`, whose container is `cli`).
wp_cli="wp-env --config .wp-env.e2e.json run cli"

# Remove the database snapshot if it exists.
$wp_cli -- rm -f blocks_e2e.sql
# Run the main script in the container for better performance.
$wp_cli -- bash wp-content/plugins/woocommerce/blocks-bin/playwright/scripts/index.sh

echo "Configuring database request lock"
$wp_cli -- bash -c 'touch /var/www/html/.woocommerce-blocks-e2e-db.lock && chmod 0666 /var/www/html/.woocommerce-blocks-e2e-db.lock && test -f /var/www/html/.woocommerce-blocks-e2e-db.lock && test -r /var/www/html/.woocommerce-blocks-e2e-db.lock && test -w /var/www/html/.woocommerce-blocks-e2e-db.lock'
$wp_cli -- php -r '
$path = "/var/www/html/.htaccess";
$block = "# BEGIN WooCommerce Blocks E2E DB Lock\nphp_value auto_prepend_file /var/www/html/wp-content/plugins/woocommerce/blocks-bin/playwright/request-lock.php\n# END WooCommerce Blocks E2E DB Lock\n";
$contents = file_get_contents( $path );
if ( false === $contents ) {
	fwrite( STDERR, "Unable to read .htaccess.\n" );
	exit( 1 );
}
$without_blocks = preg_replace( "/^" . preg_quote( $block, "/" ) . "/m", "", $contents );
if ( null === $without_blocks ) {
	fwrite( STDERR, "Unable to remove stale Blocks E2E database lock configuration.\n" );
	exit( 1 );
}
$separator = "" !== $without_blocks && "\n" !== substr( $without_blocks, -1 ) ? "\n" : "";
$updated = $without_blocks . $separator . $block;
if ( false === file_put_contents( $path, $updated ) ) {
	fwrite( STDERR, "Unable to update .htaccess.\n" );
	exit( 1 );
}
if (
	1 !== substr_count( $updated, "# BEGIN WooCommerce Blocks E2E DB Lock" ) ||
	1 !== substr_count( $updated, "# END WooCommerce Blocks E2E DB Lock" ) ||
	1 !== substr_count( $updated, "php_value auto_prepend_file /var/www/html/wp-content/plugins/woocommerce/blocks-bin/playwright/request-lock.php" ) ||
	0666 !== ( fileperms( "/var/www/html/.woocommerce-blocks-e2e-db.lock" ) & 0777 ) ||
	! is_readable( "/var/www/html/.woocommerce-blocks-e2e-db.lock" ) ||
	! is_writable( "/var/www/html/.woocommerce-blocks-e2e-db.lock" )
) {
	fwrite( STDERR, "Invalid Blocks E2E database lock configuration.\n" );
	exit( 1 );
}
'
$wp_cli -- curl --fail --silent --show-error http://wordpress/wp-content/plugins/woocommerce/blocks-bin/playwright/request-lock-probe.php

# Disable the LYS Coming Soon banner.
$wp_cli -- wp option update woocommerce_coming_soon 'no'
# Dismiss the site editor welcome guide for the admin user so it does not
# block interactions during tests. The preference is stored in user meta and
# will be included in the database snapshot that is restored between tests.
$wp_cli -- wp eval '
$prefs = get_user_meta( 1, "wp_persisted_preferences", true );
if ( ! is_array( $prefs ) ) { $prefs = array(); }
if ( ! isset( $prefs["core/edit-site"] ) ) { $prefs["core/edit-site"] = array(); }
$prefs["core/edit-site"]["welcomeGuide"] = false;
$prefs["core/edit-site"]["welcomeGuideStyles"] = false;
$prefs["core/edit-site"]["welcomeGuidePage"] = false;
$prefs["core/edit-site"]["welcomeGuideTemplate"] = false;
update_user_meta( 1, "wp_persisted_preferences", $prefs );
'

echo "Generating test translations"
node $script_dir/generate-test-translations.js
