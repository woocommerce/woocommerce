<?php
use Automattic\WooCommerce\Proxies\LegacyProxy;
use Automattic\WooCommerce\Testing\Tools\DependencyManagement\MockableLegacyProxy;
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
	echo "Could not find $_tests_dir/includes/functions.php, have you run tests/bin/install-wp-tests.sh ?";
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
	static $dir = '';
	if ( $dir === '' ) {
		if ( file_exists( WP_CONTENT_DIR . '/plugins/woocommerce/woocommerce.php' ) ) {
			$dir = WP_CONTENT_DIR . '/plugins/woocommerce';
			echo "Found WooCommerce plugin in content dir." . PHP_EOL;
		} elseif ( file_exists( dirname( dirname( __DIR__ ) ) . '/woocommerce/woocommerce.php' ) ) {
			$dir = dirname( dirname( __DIR__ ) ) . '/woocommerce';
			echo "Found WooCommerce plugin in relative dir." . PHP_EOL;
		} elseif ( file_exists( '/tmp/wordpress/wp-content/plugins/woocommerce/woocommerce.php' ) ) {
			$dir = '/tmp/wordpress/wp-content/plugins/woocommerce';
			echo "Found WooCommerce plugin in tmp dir." . PHP_EOL;
		} else {
			echo "Could not find WooCommerce plugin." . PHP_EOL;
			exit( 1 );
		}
	}
	return $dir;
}

/**
 * Install WC Blocks
 */
function wc_blocks_install() {
	echo esc_html( 'Loading WooCommerce Blocks plugin' . PHP_EOL );
	require dirname( __DIR__ ) . '/woocommerce-gutenberg-products-block.php';
}

/**
 * Adds WooCommerce testing framework classes.
 */
function wc_test_includes() {
	$wc_tests_framework_base_dir = wc_dir() . '/tests/legacy';
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
 * Re-initialize the dependency injection engine.
 *
 * The dependency injection engine has been already initialized as part of the Woo initialization, but we need
 * to replace the registered read-only container with a fully configurable one for testing.
 * To this end we hack a bit and use reflection to grab the underlying container that the read-only one stores
 * in a private property.
 *
 * Additionally, we replace the legacy/function proxies with mockable versions to easily replace anything
 * in tests as appropriate.
 *
 * @throws \Exception The Container class doesn't have a 'container' property.
 */
function wc_blocks_initialize_dependency_injection() {
	try {
		$inner_container_property = new \ReflectionProperty( \Automattic\WooCommerce\Container::class, 'container' );
	} catch ( ReflectionException $ex ) {
		throw new \Exception( "Error when trying to get the private 'container' property from the " . \Automattic\WooCommerce\Container::class . ' class using reflection during unit testing bootstrap, has the property been removed or renamed?' );
	}

	$inner_container_property->setAccessible( true );
	$inner_container = $inner_container_property->getValue( wc_get_container() );

	$inner_container->replace( LegacyProxy::class, MockableLegacyProxy::class );
	$inner_container->reset_all_resolved();

	$GLOBALS['wc_container'] = $inner_container;
}

function wc_load_core() {
	define( 'WC_TAX_ROUNDING_MODE', 'auto' );
	define( 'WC_USE_TRANSACTIONS', false );
	echo esc_html( 'Loading WooCommerce plugin' . PHP_EOL );
	require_once wc_dir() . '/woocommerce.php';
}

function wc_install_core() {
	// Clean existing install first.
	define( 'WP_UNINSTALL_PLUGIN', true );
	define( 'WC_REMOVE_ALL_DATA', true );
	include wc_dir() . '/uninstall.php';
	$GLOBALS['wp_roles'] = null; // WPCS: override ok.
	wp_roles();
	echo esc_html( 'Installing WooCommerce Blocks...' . PHP_EOL );
	\Automattic\WooCommerce\Blocks\Package::container()->get( \Automattic\WooCommerce\Blocks\Installer::class )->maybe_create_tables();
}

/**
 * Manually load the plugin being tested.
 */
tests_add_filter(
	'muplugins_loaded',
	function() {
		define( 'JETPACK_AUTOLOAD_DEV', true );
		wc_load_core();
		// install blocks plugin
		wc_blocks_install();
	}
);

tests_add_filter( 'setup_theme', 'wc_install_core' );

// Start up the WP testing environment.
require $_tests_dir . '/includes/bootstrap.php';
wc_test_includes();
wc_blocks_initialize_dependency_injection();
