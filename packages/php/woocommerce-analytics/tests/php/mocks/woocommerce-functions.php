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

/**
 * Whether is_order_received_page() reports the order received page.
 *
 * @var bool
 */
global $wc_is_order_received_page;
$wc_is_order_received_page = false;

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
		global $wc_cart_mock;
		$wc       = new class() {
			/**
			 * Session property.
			 *
			 * @var object|null
			 */
			public $session = null;

			/**
			 * Cart property, set from the `$wc_cart_mock` global.
			 *
			 * @var object|null
			 */
			public $cart = null;
		};
		$wc->cart = $wc_cart_mock ?? null;
		return $wc;
	}
}

if ( ! function_exists( 'is_cart' ) ) {
	/**
	 * Mock is_cart function.
	 *
	 * @return bool Always false; tests exercise generic front-end pages.
	 */
	function is_cart() {
		return false;
	}
}

if ( ! function_exists( 'is_order_received_page' ) ) {
	/**
	 * Mock is_order_received_page function.
	 *
	 * @return bool The `$wc_is_order_received_page` global.
	 */
	function is_order_received_page() {
		global $wc_is_order_received_page;
		return (bool) $wc_is_order_received_page;
	}
}

if ( ! function_exists( 'wc_float_to_string' ) ) {
	/**
	 * Mock wc_float_to_string function, same as WooCommerce's.
	 *
	 * @param mixed $float Value to format.
	 * @return mixed A float as a string with a `.` decimal separator; anything else unchanged.
	 */
	function wc_float_to_string( $float ) {
		if ( ! is_float( $float ) ) {
			return $float;
		}
		$locale = localeconv();
		return str_replace( $locale['decimal_point'], '.', strval( $float ) );
	}
}

if ( ! function_exists( 'is_account_page' ) ) {
	/**
	 * Mock is_account_page function.
	 *
	 * @return bool Always false; tests exercise generic front-end pages.
	 */
	function is_account_page() {
		return false;
	}
}
