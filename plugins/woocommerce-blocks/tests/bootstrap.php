<?php
namespace Automattic\WooCommerce\Blocks\Tests;

// Require composer dependencies.
require_once dirname( __DIR__ ) . '/vendor/autoload.php';

// Determine the tests directory (from a WP dev checkout).
// Try the WP_TESTS_DIR environment variable first.
$_wc_tests_framework_dir = dirname( dirname( __DIR__ ) ) . '/woocommerce/tests/legacy';
$_tests_dir = getenv( 'WP_TESTS_DIR' );

// Next, try the WP_PHPUNIT composer package.
if ( ! $_tests_dir ) {
	$_tests_dir = getenv( 'WP_PHPUNIT__DIR' );
}

// See if we're installed inside an existing WP dev instance.
if ( ! $_tests_dir ) {
	$_try_tests_dir = __DIR__ . '/../../../../../tests/phpunit';
	if ( file_exists( $_try_tests_dir . '/includes/functions.php' ) ) {
		$_tests_dir = $_try_tests_dir;
	}
}
// Fallback.
if ( ! $_tests_dir ) {
	$_tests_dir = '/tmp/wordpress-tests-lib';
}

// Give access to tests_add_filter() function.
require_once $_tests_dir . '/includes/functions.php';

/**
 * Manually load the plugin being tested.
 */
function manually_load_plugins() {
	require dirname( dirname( __DIR__ ) ) . '/woocommerce/woocommerce.php';
	require dirname( __DIR__ ) . '/woocommerce-gutenberg-products-block.php';
}

tests_add_filter( 'muplugins_loaded', __NAMESPACE__ . '\\manually_load_plugins' );

/**
 * Manually install plugins being tested.
 */
function manually_install_plugins() {
	\Automattic\WooCommerce\Blocks\Package::container()->get( \Automattic\WooCommerce\Blocks\Installer::class )->maybe_create_tables();
}

tests_add_filter( 'setup_theme', __NAMESPACE__ . '\\manually_install_plugins' );

// Start up the WP testing environment.
require $_tests_dir . '/includes/bootstrap.php';
