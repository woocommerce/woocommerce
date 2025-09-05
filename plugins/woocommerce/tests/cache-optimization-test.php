<?php
/**
 * Cache Optimization Test
 * 
 * Simple test to verify cache optimization functionality.
 * 
 * @package WooCommerce/Tests
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Test cache optimization functionality.
 */
class WC_Cache_Optimization_Test {

	/**
	 * Run basic tests.
	 */
	public static function run_tests() {
		echo "Running WooCommerce Cache Optimization Tests...\n\n";

		// Test 1: Check if classes exist
		self::test_class_loading();

		// Test 2: Check if optimization is enabled
		self::test_optimization_status();

		// Test 3: Test cookie behavior
		self::test_cookie_behavior();

		echo "Tests completed.\n";
	}

	/**
	 * Test if cache optimization classes are loaded.
	 */
	private static function test_class_loading() {
		echo "Test 1: Class Loading\n";
		echo "--------------------\n";

		$classes = array(
			'WC_Cache_Optimizer',
			'WC_Cache_Optimized_Cart_Session',
		);

		foreach ( $classes as $class ) {
			if ( class_exists( $class ) ) {
				echo "✓ {$class} loaded successfully\n";
			} else {
				echo "✗ {$class} not found\n";
			}
		}
		echo "\n";
	}

	/**
	 * Test optimization status.
	 */
	private static function test_optimization_status() {
		echo "Test 2: Optimization Status\n";
		echo "---------------------------\n";

		if ( class_exists( 'WC_Cache_Optimizer' ) ) {
			$optimizer = WC_Cache_Optimizer::instance();
			$status = $optimizer->get_status();

			echo "Optimization Enabled: " . ( $status['enabled'] ? 'Yes' : 'No' ) . "\n";
			echo "Dynamic Page: " . ( $status['dynamic_page'] ? 'Yes' : 'No' ) . "\n";
			echo "Options: " . print_r( $status['options'], true ) . "\n";
		} else {
			echo "✗ WC_Cache_Optimizer not available\n";
		}
		echo "\n";
	}

	/**
	 * Test cookie behavior.
	 */
	private static function test_cookie_behavior() {
		echo "Test 3: Cookie Behavior\n";
		echo "-----------------------\n";

		// Check if cart cookies exist
		$cart_cookies = array(
			'woocommerce_items_in_cart',
			'woocommerce_cart_hash',
		);

		foreach ( $cart_cookies as $cookie ) {
			if ( isset( $_COOKIE[ $cookie ] ) ) {
				echo "✓ {$cookie} is set: " . $_COOKIE[ $cookie ] . "\n";
			} else {
				echo "- {$cookie} is not set\n";
			}
		}

		// Check session cookies
		$session_cookies = array();
		foreach ( $_COOKIE as $name => $value ) {
			if ( strpos( $name, 'wp_woocommerce_session_' ) === 0 ) {
				$session_cookies[] = $name;
			}
		}

		if ( ! empty( $session_cookies ) ) {
			echo "✓ Session cookies found: " . implode( ', ', $session_cookies ) . "\n";
		} else {
			echo "- No session cookies found\n";
		}

		echo "\n";
	}
}

// Run tests if this file is accessed directly
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WC_Cache_Optimization_Test::run_tests();
}