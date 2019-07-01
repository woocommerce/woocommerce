<?php
/**
 * PHPUnit bootstrap file
 *
 * @package WooCommerce\Blocks
 */

$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
	echo "Could not find $_tests_dir/includes/functions.php, have you run bin/install-wp-tests.sh ?";
	exit( 1 );
}

// Give access to tests_add_filter() function.
require_once $_tests_dir . '/includes/functions.php';

tests_add_filter(
	'setup_theme',
	function() {
		echo esc_html( 'Installing WooCommerce...' . PHP_EOL );
		WC_Install::install();
		$GLOBALS['wp_roles'] = null; // WPCS: override ok.
		wp_roles();
	}
);

/**
 * Manually load the plugin being tested.
 */
tests_add_filter(
	'muplugins_loaded',
	function() {
		define( 'WC_TAX_ROUNDING_MODE', 'auto' );
		define( 'WC_USE_TRANSACTIONS', false );

		echo esc_html( 'Loading WooCommerce plugin' . PHP_EOL );
		if ( file_exists( WP_CONTENT_DIR . '/woocommerce/woocommerce.php' ) ) {
			require WP_CONTENT_DIR . '/woocommerce/woocommerce.php';
			echo "Found WooCommerce plugin in content dir." . PHP_EOL;
		} elseif ( file_exists( dirname( dirname( __DIR__ ) ) . '/woocommerce/woocommerce.php' ) ) {
			require dirname( dirname( __DIR__ ) ) . '/woocommerce/woocommerce.php';
			echo "Found WooCommerce plugin in relative dir." . PHP_EOL;
		} elseif ( file_exists( '/tmp/wordpress/wp-content/plugins/woocommerce/woocommerce.php' ) ) {
			require '/tmp/wordpress/wp-content/plugins/woocommerce/woocommerce.php';
			echo "Found WooCommerce plugin in tmp dir." . PHP_EOL;
		} else {
			echo "Could not find WooCommerce plugin." . PHP_EOL;
			exit( 1 );
		}

		echo esc_html( 'Loading WooCommerce Gutenberg Products Block plugin' . PHP_EOL );
		require dirname( __DIR__ ) . '/woocommerce-gutenberg-products-block.php';
	}
);

// Start up the WP testing environment.
require $_tests_dir . '/includes/bootstrap.php';

// Framework @todo this should be a separate package.
require_once __DIR__ . '/framework/wp-http-testcase.php';
require_once __DIR__ . '/framework/class-wc-unit-test-factory.php';
require_once __DIR__ . '/framework/class-wc-unit-test-case.php';
require_once __DIR__ . '/framework/class-wc-rest-unit-test-case.php';
require_once __DIR__ . '/framework/class-wc-mock-session-handler.php';
require_once __DIR__ . '/framework/class-wc-mock-wc-data.php';
require_once __DIR__ . '/framework/class-wc-mock-wc-object-query.php';
require_once __DIR__ . '/framework/vendor/class-wp-test-spy-rest-server.php';

// Helpers.
require_once __DIR__ . '/framework/helpers/class-wc-helper-product.php';
require_once __DIR__ . '/framework/helpers/class-wc-helper-coupon.php';
require_once __DIR__ . '/framework/helpers/class-wc-helper-fee.php';
require_once __DIR__ . '/framework/helpers/class-wc-helper-shipping.php';
require_once __DIR__ . '/framework/helpers/class-wc-helper-customer.php';
require_once __DIR__ . '/framework/helpers/class-wc-helper-order.php';
require_once __DIR__ . '/framework/helpers/class-wc-helper-shipping-zones.php';
require_once __DIR__ . '/framework/helpers/class-wc-helper-payment-token.php';
require_once __DIR__ . '/framework/helpers/class-wc-helper-settings.php';
