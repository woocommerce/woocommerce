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
	 * Create a cart fee with taxes.
	 *
	 * @since 3.2
	 */
	public static function create_taxed_fee() {
		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
			return;
		}

		WC()->cart->add_fee( 'Dummy Taxed Fee', 10, true );
	}

	/**
	 * Create a negative cart fee without taxes.
	 *
	 * @since 3.2
	 */
	public static function create_negative_fee() {
		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
			return;
		}

		WC()->cart->add_fee( 'Dummy Negative Fee', -10 );

	}

	/**
	 * Create a negative cart fee with taxes.
	 *
	 * @since 3.2
	 */
	public static function create_negative_taxed_fee() {
		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
			return;
		}

		WC()->cart->add_fee( 'Dummy Negative Taxed Fee', -10, true );
	}

	/**
	 * Add a cart fee.
	 * Note: need to be added before add any product in the cart.
	 *
	 * @since 2.3
	 * @param string $fee Type of fee to add (Default: simple)
	 */
	public static function add_cart_fee( $fee = '' ) {
		switch ( $fee ) {
			case 'taxed':
				add_action( 'woocommerce_cart_calculate_fees', array( __CLASS__, 'create_taxed_fee' ) );
				break;
			case 'negative':
				add_action( 'woocommerce_cart_calculate_fees', array( __CLASS__, 'create_negative_fee' ) );
				break;
			case 'negative-taxed':
				add_action( 'woocommerce_cart_calculate_fees', array( __CLASS__, 'create_negative_taxed_fee' ) );
				break;
			default:
				add_action( 'woocommerce_cart_calculate_fees', array( __CLASS__, 'create_simple_fee' ) );
		}
	}

	/**
	 * Remove a cart fee.
	 *
	 * @since 2.3
	 * @param string $fee Type of fee to remove (Default: simple)
	 */
	public static function remove_cart_fee( $fee = '' ) {
		switch ( $fee ) {
			case 'taxed':
				remove_action( 'woocommerce_cart_calculate_fees', array( __CLASS__, 'create_taxed_fee' ) );
				break;
			case 'negative':
				remove_action( 'woocommerce_cart_calculate_fees', array( __CLASS__, 'create_negative_fee' ) );
				break;
			case 'negative-taxed':
				remove_action( 'woocommerce_cart_calculate_fees', array( __CLASS__, 'create_negative_taxed_fee' ) );
				break;
			default:
				remove_action( 'woocommerce_cart_calculate_fees', array( __CLASS__, 'create_simple_fee' ) );
		}
	}
}
