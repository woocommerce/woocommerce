<?php

/**
 * Class WC_Helper_Customer.
 *
 * This helper class should ONLY be used for unit tests!.
 */
class WC_Helper_Customer {

	/**
	 * Create a mock customer for testing purposes.
	 *
	 * @return WC_Customer
	 */

	public static function create_mock_customer() {

		$customer_data = array(
			'country' 				=> 'US',
			'state' 				=> 'PA',
			'postcode' 				=> '19123',
			'city'					=> 'Philadelphia',
			'address' 				=> '123 South Street',
			'address_2' 			=> 'Apt 1',
			'shipping_country' 		=> 'US',
			'shipping_state' 		=> 'PA',
			'shipping_postcode' 	=> '19123',
			'shipping_city'			=> 'Philadelphia',
			'shipping_address'		=> '123 South Street',
			'shipping_address_2'	=> 'Apt 1',
			'is_vat_exempt' 		=> false,
			'calculated_shipping'	=> false
		);

		WC_Helper_Customer::set_customer_details( $customer_data );

		return new WC_Customer();
	}

	/**
	 * Get the expected output for the store's base location settings.
	 *
	 * @return array
	 */

	public static function get_expected_store_location() {
		return array( "GB", "", "", "" );
	}

	/**
	 * Get the customer's shipping and billing info from the session.
	 *
	 * @return array
	 */

	public static function get_customer_details() {
		return WC()->session->get( 'customer' );
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
	 * Set the the current customer's billing details in the session.
	 *
	 * @param string $default_shipping_method Shipping Method slug
	 */

	public static function set_customer_details( $customer_details ) {
		WC()->session->set( 'customer', $customer_details );
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
