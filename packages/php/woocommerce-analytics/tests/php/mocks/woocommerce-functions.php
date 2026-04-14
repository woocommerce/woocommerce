<?php
/**
 * WooCommerce function mocks for testing.
 *
 * @package automattic/woocommerce-analytics
 */

// phpcs:disable WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid

/**
 * Global variable to store mock order for testing.
 *
 * @var mixed
 */
global $wc_get_order_mock_return;
$wc_get_order_mock_return = false;

/**
 * Global variable to track wc_get_order calls.
 *
 * @var array
 */
global $wc_get_order_calls;
$wc_get_order_calls = array();

if ( ! function_exists( 'wc_get_order' ) ) {
	/**
	 * Mock wc_get_order function.
	 *
	 * @param mixed $the_order Post object or post ID of the order.
	 * @return mixed The mocked return value.
	 */
	function wc_get_order( $the_order = false ) {
		global $wc_get_order_mock_return, $wc_get_order_calls;
		$wc_get_order_calls[] = $the_order;
		return $wc_get_order_mock_return;
	}
}

if ( ! function_exists( 'WC' ) ) {
	/**
	 * Mock WC function.
	 *
	 * @return object Mock WooCommerce object.
	 */
	function WC() {
		return new class() {
			/**
			 * Session property.
			 *
			 * @var object|null
			 */
			public $session = null;
		};
	}
}
