<?php
/**
 * PHPUnit bootstrap file
 *
 * @package WC_Admin
 */

$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
	echo "Could not find $_tests_dir/includes/functions.php, have you run bin/install-wp-tests.sh ?" . PHP_EOL;
	exit( 1 );
}

// Give access to tests_add_filter() function.
require_once $_tests_dir . '/includes/functions.php';

function wc_dir() {
	return dirname( dirname( dirname( __FILE__ ) ) ) . '/woocommerce';
}

function load_wc() {
	define( 'WC_TAX_ROUNDING_MODE', 'auto' );
	define( 'WC_USE_TRANSACTIONS', false );
	require_once wc_dir() . '/woocommerce.php';
}

function install_wc() {
	// Clean existing install first.
	define( 'WP_UNINSTALL_PLUGIN', true );
	define( 'WC_REMOVE_ALL_DATA', true );
	include wc_dir() . '/uninstall.php';

	WC_Install::install();

	// Reload capabilities after install, see https://core.trac.wordpress.org/ticket/28374.
	if ( version_compare( $GLOBALS['wp_version'], '4.7', '<' ) ) {
		$GLOBALS['wp_roles']->reinit();
	} else {
		$GLOBALS['wp_roles'] = null; // WPCS: override ok.
		wp_roles();
	}

	echo esc_html( 'Installing WooCommerce...' . PHP_EOL );
}

function wc_test_includes() {
	$wc_tests_framework_base_dir = wc_dir() . '/tests/';

	// WooCommerce test classes.
	// Framework.
	require_once $wc_tests_framework_base_dir . '/framework/class-wc-unit-test-factory.php';
	require_once $wc_tests_framework_base_dir . '/framework/class-wc-mock-session-handler.php';
	require_once $wc_tests_framework_base_dir . '/framework/class-wc-mock-wc-data.php';
	require_once $wc_tests_framework_base_dir . '/framework/class-wc-mock-wc-object-query.php';
	require_once $wc_tests_framework_base_dir . '/framework/class-wc-mock-payment-gateway.php';
	require_once $wc_tests_framework_base_dir . '/framework/class-wc-payment-token-stub.php';
	require_once $wc_tests_framework_base_dir . '/framework/vendor/class-wp-test-spy-rest-server.php';

	// Test cases.
	require_once $wc_tests_framework_base_dir . '/includes/wp-http-testcase.php';
	require_once $wc_tests_framework_base_dir . '/framework/class-wc-unit-test-case.php';
	require_once $wc_tests_framework_base_dir . '/framework/class-wc-api-unit-test-case.php';
	require_once $wc_tests_framework_base_dir . '/framework/class-wc-rest-unit-test-case.php';

	// Helpers.
	require_once $wc_tests_framework_base_dir . '/framework/helpers/class-wc-helper-product.php';
	require_once $wc_tests_framework_base_dir . '/framework/helpers/class-wc-helper-coupon.php';
	require_once $wc_tests_framework_base_dir . '/framework/helpers/class-wc-helper-fee.php';
	require_once $wc_tests_framework_base_dir . '/framework/helpers/class-wc-helper-shipping.php';
	require_once $wc_tests_framework_base_dir . '/framework/helpers/class-wc-helper-customer.php';
	require_once $wc_tests_framework_base_dir . '/framework/helpers/class-wc-helper-order.php';
	require_once $wc_tests_framework_base_dir . '/framework/helpers/class-wc-helper-shipping-zones.php';
	require_once $wc_tests_framework_base_dir . '/framework/helpers/class-wc-helper-payment-token.php';
	require_once $wc_tests_framework_base_dir . '/framework/helpers/class-wc-helper-settings.php';
}

function install_wc_admin() {
	require_once dirname( dirname( __FILE__ ) ) . '/includes/class-wc-admin-api-init.php';

	WC_Admin_Api_Init::install();

	echo esc_html( 'Installing WC Admin...' . PHP_EOL );
}

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin() {
	//require dirname( dirname( dirname( __FILE__ ) ) ) . '/gutenberg/gutenberg.php';
	load_wc();
	require dirname( dirname( __FILE__ ) ) . '/wc-admin.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );
tests_add_filter( 'setup_theme', 'install_wc' );
tests_add_filter( 'setup_theme', 'install_wc_admin' );

// Start up the WP testing environment.
require $_tests_dir . '/includes/bootstrap.php';

wc_test_includes();

// Include wc-admin helpers.
require_once dirname( __FILE__ ) . '/framework/helpers/class-wc-helper-reports.php';


