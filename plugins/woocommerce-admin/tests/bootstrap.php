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
	echo "Could not find $_tests_dir/includes/functions.php, have you run bin/install-wp-tests.sh ?" . PHP_EOL; // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped
	exit( 1 );
}

// Give access to tests_add_filter() function.
require_once $_tests_dir . '/includes/functions.php';

/**
 * Returns WooCommerce main directory.
 *
 * @return string
 */
function wc_dir() {
	return dirname( dirname( dirname( __FILE__ ) ) ) . '/woocommerce';
}

/**
 * Install wc admin.
 */
function wc_admin_install() {
	// Clean existing install first.
	define( 'WP_UNINSTALL_PLUGIN', true );
	define( 'WC_REMOVE_ALL_DATA', true );

	// Initialize the WC API extensions.
	\Automattic\WooCommerce\Admin\Install::create_tables();
	\Automattic\WooCommerce\Admin\Install::create_events();

	// Reload capabilities after install, see https://core.trac.wordpress.org/ticket/28374.
	$GLOBALS['wp_roles'] = null; // WPCS: override ok.
	wp_roles();

	echo esc_html( 'Installing woocommerce-admin...' . PHP_EOL );
}

/**
 * Adds WooCommerce testing framework classes.
 */
function wc_test_includes() {
	$wc_tests_framework_base_dir = wc_dir() . '/tests';

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

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin() {
	define( 'WC_TAX_ROUNDING_MODE', 'auto' );
	define( 'WC_USE_TRANSACTIONS', false );
	update_option( 'woocommerce_enable_coupons', 'yes' );
	update_option( 'woocommerce_calc_taxes', 'yes' );

	require_once wc_dir() . '/woocommerce.php';
	require dirname( __DIR__ ) . '/vendor/autoload.php';

	wc_admin_install();

	require dirname( dirname( __FILE__ ) ) . '/woocommerce-admin.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

// Start up the WP testing environment.
require $_tests_dir . '/includes/bootstrap.php';

wc_test_includes();

// Include wc-admin helpers.
require_once dirname( __FILE__ ) . '/framework/helpers/class-wc-helper-reports.php';
require_once dirname( __FILE__ ) . '/framework/helpers/class-wc-helper-admin-notes.php';
require_once dirname( __FILE__ ) . '/framework/helpers/class-wc-test-action-queue.php';
require_once dirname( __FILE__ ) . '/framework/helpers/class-wc-helper-queue.php';

/**
 * Use the `development` features for testing.
 */
function wc_admin_add_development_features() {
	$config = json_decode( file_get_contents( dirname( dirname( __FILE__ ) ) . '/config/development.json' ) ); // @codingStandardsIgnoreLine.
	$flags  = array();
	foreach ( $config->features as $feature => $bool ) {
		$flags[ $feature ] = $bool;
	}
	return $flags;
}
tests_add_filter( 'wc_admin_get_feature_config', 'wc_admin_add_development_features' );
