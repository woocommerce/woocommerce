<?php

/**
 * Class WC_Helper_Fee.
 *
 * This helper class should ONLY be used for unit tests!.
 */
class WC_Helper_Fee {

	/**
	 * Create a cart simple fee without taxes.
	 *
	 * @since 2.3
	 */
	public static function create_simple_fee() {
		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
			return;
		}

		WC()->cart->add_fee( 'Dummy Fee', 10 );
	}

	/**
	 * Add a cart simple fee without taxes.
	 * Note: need to be added before add any product in the cart.
	 *
	 * @since 2.3
	 */
	public static function add_cart_fee() {
		add_action( 'woocommerce_cart_calculate_fees', array( __CLASS__, 'create_simple_fee' ) );
	}

	/**
	 * Remove a cart simple fee without taxes.
	 *
	 * @since 2.3
	 */
	public static function remove_cart_fee() {
		remove_action( 'woocommerce_cart_calculate_fees', array( __CLASS__, 'create_simple_fee' ) );
	}
}
