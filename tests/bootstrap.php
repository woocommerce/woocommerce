<?php
/**
 * PHPUnit bootstrap file
 *
 * @package WooCommerce/RestApi
 */

$wc_tests_dir = dirname( dirname( dirname( __FILE__ ) ) ) . '/woocommerce/tests';
$tests_dir    = getenv( 'WP_TESTS_DIR' );

if ( ! $tests_dir ) {
	$tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

if ( ! file_exists( $tests_dir . '/includes/functions.php' ) ) {
	echo "Could not find $_tests_dir/includes/functions.php, have you run bin/install-wp-tests.sh ?" . PHP_EOL; // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped
	exit( 1 );
}

// Give access to tests_add_filter() function.
require_once $tests_dir . '/includes/functions.php';

tests_add_filter( 'muplugins_loaded', function() {
	require_once dirname( dirname( __DIR__ ) ) . '/woocommerce/woocommerce.php';
	require_once dirname( __DIR__ ) . '/woocommerce-rest-api.php';
} );

tests_add_filter( 'setup_theme', function() {
	echo esc_html( 'Installing WooCommerce...' . PHP_EOL );

	define( 'WP_UNINSTALL_PLUGIN', true );
	define( 'WC_REMOVE_ALL_DATA', true );
	include dirname( dirname( __DIR__ ) ) . '/woocommerce/uninstall.php';

	WC_Install::install();

	$GLOBALS['wp_roles'] = null; // WPCS: override ok.
	wp_roles();
} );

// Start up the WP testing environment.
require $tests_dir . '/includes/bootstrap.php';
require $wc_tests_dir . '/bootstrap.php';

// Framework.
require_once __DIR__ . '/AbstractRestApiTest.php';
require_once $wc_tests_dir . '/framework/class-wc-unit-test-factory.php';
require_once $wc_tests_dir . '/framework/class-wc-mock-session-handler.php';
require_once $wc_tests_dir . '/framework/class-wc-mock-wc-data.php';
require_once $wc_tests_dir . '/framework/class-wc-mock-wc-object-query.php';
require_once $wc_tests_dir . '/framework/class-wc-mock-payment-gateway.php';
require_once $wc_tests_dir . '/framework/class-wc-payment-token-stub.php';
require_once $wc_tests_dir . '/framework/vendor/class-wp-test-spy-rest-server.php';

// Test cases.
require_once $wc_tests_dir . '/includes/wp-http-testcase.php';
require_once $wc_tests_dir . '/framework/class-wc-unit-test-case.php';
require_once $wc_tests_dir . '/framework/class-wc-api-unit-test-case.php';
require_once $wc_tests_dir . '/framework/class-wc-rest-unit-test-case.php';

// Helpers.
require_once $wc_tests_dir . '/framework/helpers/class-wc-helper-product.php';
require_once $wc_tests_dir . '/framework/helpers/class-wc-helper-coupon.php';
require_once $wc_tests_dir . '/framework/helpers/class-wc-helper-fee.php';
require_once $wc_tests_dir . '/framework/helpers/class-wc-helper-shipping.php';
require_once $wc_tests_dir . '/framework/helpers/class-wc-helper-customer.php';
require_once $wc_tests_dir . '/framework/helpers/class-wc-helper-order.php';
require_once $wc_tests_dir . '/framework/helpers/class-wc-helper-shipping-zones.php';
require_once $wc_tests_dir . '/framework/helpers/class-wc-helper-payment-token.php';
require_once $wc_tests_dir . '/framework/helpers/class-wc-helper-settings.php';
