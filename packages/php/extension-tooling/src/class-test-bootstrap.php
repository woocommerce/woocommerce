<?php
/**
 * Shared setup for extension PHPUnit suites backed by WooCommerce's test framework.
 *
 * @package WooCommerce\ExtensionTools
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\ExtensionTools\Testing;

use Automattic\WooCommerce\Container;
use Automattic\WooCommerce\Proxies\LegacyProxy;
use Automattic\WooCommerce\Testing\Tools\DependencyManagement\MockableLegacyProxy;
use Automattic\WooCommerce\Testing\Tools\TestingContainer;
use ReflectionException;
use ReflectionProperty;

/**
 * Load the base test cases and prepare the WooCommerce test container.
 */
final class Test_Bootstrap {

	/**
	 * Load the base class files from the WooCommerce legacy test framework.
	 *
	 * @param string $wc_tests_dir Path to the WooCommerce legacy tests.
	 */
	public static function load_core_test_cases( string $wc_tests_dir ): void {
		require_once $wc_tests_dir . '/includes/wp-http-testcase.php';
		require_once $wc_tests_dir . '/framework/class-wc-unit-test-case.php';
	}

	/**
	 * Wrap the WooCommerce container once after WooCommerce and its test tools load.
	 *
	 * @param bool $prefer_existing_container Reuse an inner container that supports service replacement and reset.
	 */
	public static function initialize_test_container( bool $prefer_existing_container = false ): void {
		try {
			$inner_property = new ReflectionProperty( Container::class, 'container' );
		} catch ( ReflectionException $exception ) {
			// Preserve the legacy extension bootstraps' fallback for other container layouts.
			return;
		}

		if ( PHP_VERSION_ID < 80100 ) {
			$inner_property->setAccessible( true );
		}

		$container       = wc_get_container();
		$inner_container = $inner_property->getValue( $container );

		if ( $prefer_existing_container && method_exists( $inner_container, 'replace' ) && method_exists( $inner_container, 'reset_all_resolved' ) ) {
			$inner_container->replace( LegacyProxy::class, new MockableLegacyProxy() );
			$inner_container->reset_all_resolved();
		} else {
			$inner_container = new TestingContainer( $inner_container );
			$inner_property->setValue( $container, $inner_container );
		}

		// WooCommerce's wc_get_container() returns this global.
		$GLOBALS['wc_container'] = $inner_container;
	}
}
