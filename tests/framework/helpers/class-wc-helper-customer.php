<?php

/**
 * Class WC_Helper_Customer
 *
 * This helper class should ONLY be used for unit tests!
 */
class WC_Helper_Customer {

	/**
	 * Get the the current customer's billing details from the session
	 *
	 * @return array
	 */

	public static function get_customer_details() {
		WC()->session->get( 'customer' );
	}

	/**
	 * Get the store's default shipping method.
	 *
	 * @return string
	 */

	public static function get_default_shipping_method() {
		get_option( 'woocommerce_default_shipping_method' );
	}


	/**
	 * Get the user's chosen shipping method.
	 *
	 * @return array
	 */

	public static function get_chosen_shipping_methods() {
		return WC()->session->get( 'chosen_shipping_methods' );
	}

	/**
	 * Get the "Tax Based On" WooCommerce option.
	 *
	 * @return string base or billing
	 */

	public static function get_tax_based_on() {
		return get_option( 'woocommerce_tax_based_on' );
	}

	/**
	 * Set the the current customer's billing details in the session
	 *
	 * @param string $default_shipping_method Shipping Method slug
	 */

	public static function set_customer_details( $customer_details ) {
		WC()->session->set( 'customer', $customer_details );
	}

	/**
	 * Set the store's default shipping method.
	 *
	 * @param string $default_shipping_method Shipping Method slug
	 */

	public static function set_default_shipping_method( $default_shipping_method ) {
		update_option( 'woocommerce_default_shipping_method', $default_shipping_method );
	}

	/**
	 * Set the user's chosen shipping method.
	 *
	 * @param string $chosen_shipping_method Shipping Method slug
	 */

	public static function set_chosen_shipping_methods( $chosen_shipping_methods ) {
		WC()->session->set( 'chosen_shipping_methods', $chosen_shipping_methods );
	}

	/**
	 * Set the "Tax Based On" WooCommerce option.
	 *
	 * @param string $default_shipping_method Shipping Method slug
	 */

	public static function set_tax_based_on( $default_shipping_method ) {
		update_option( 'woocommerce_tax_based_on', $default_shipping_method );
	}
}
