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
			'id'                  => 0,
			'date_modified'       => null,
			'country'             => 'US',
			'state'               => 'CA',
			'postcode'            => '94110',
			'city'                => 'San Francisco',
			'address'             => '123 South Street',
			'address_2'           => 'Apt 1',
			'shipping_country'    => 'US',
			'shipping_state'      => 'CA',
			'shipping_postcode'   => '94110',
			'shipping_city'       => 'San Francisco',
			'shipping_address'    => '123 South Street',
			'shipping_address_2'  => 'Apt 1',
			'is_vat_exempt'       => false,
			'calculated_shipping' => false,
		);

		self::set_customer_details( $customer_data );

		$customer = new WC_Customer( 0, true );

		return $customer;
	}

	/**
	 * Creates a customer in the tests DB.
	 */
	public static function create_customer( $username = 'testcustomer', $password = 'hunter2', $email = 'test@woo.local' ) {
		$customer = new WC_Customer();
		$customer->set_billing_country( 'US' );
		$customer->set_first_name( 'Justin' );
		$customer->set_billing_state( 'CA' );
		$customer->set_billing_postcode( '94110' );
		$customer->set_billing_city( 'San Francisco' );
		$customer->set_billing_address( '123 South Street' );
		$customer->set_billing_address_2( 'Apt 1' );
		$customer->set_shipping_country( 'US' );
		$customer->set_shipping_state( 'CA' );
		$customer->set_shipping_postcode( '94110' );
		$customer->set_shipping_city( 'San Francisco' );
		$customer->set_shipping_address( '123 South Street' );
		$customer->set_shipping_address_2( 'Apt 1' );
		$customer->set_username( $username );
		$customer->set_password( $password );
		$customer->set_email( $email );
		$customer->save();
		return $customer;
	}

	/**
	 * Get the expected output for the store's base location settings.
	 *
	 * @return array
	 */
	public static function get_expected_store_location() {
		return array( 'US', 'CA', '', '' );
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
	 * Set the current customer's billing details in the session.
	 *
	 * @param string $default_shipping_method Shipping Method slug
	 */
	public static function set_customer_details( $customer_details ) {
		WC()->session->set( 'customer', array_map( 'strval', $customer_details ) );
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
