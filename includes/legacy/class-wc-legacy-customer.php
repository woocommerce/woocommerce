<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Legacy Customer.
 *
 * @version  2.7.0
 * @package  WooCommerce/Classes
 * @category Class
 * @author   WooThemes
 */
abstract class WC_Legacy_Customer extends WC_Data {

	/**
	 * __isset legacy.
	 * @param mixed $key
	 * @return bool
	 */
	public function __isset( $key ) {
		$legacy_keys = array(
			'country', 'state', 'postcode' ,'city', 'address_1', 'address', 'address_2', 'shipping_country', 'shipping_state',
			'shipping_postcode', 'shipping_city', 'shipping_address_1', 'shipping_address', 'shipping_address_2', 'is_vat_exempt', 'calculated_shipping',
		);
		$key = $this->filter_legacy_key( $key );
		return in_array( $key, $legacy_keys );
	}

	/**
	 * __get function.
	 * @todo use get_* methods
	 * @param string $key
	 * @return string
	 */
	public function __get( $key ) {
		_doing_it_wrong( $key, 'Customer properties should not be accessed directly.', '2.7' );
		$key = $this->filter_legacy_key( $key );
		if ( in_array( $key, array( 'country', 'state', 'postcode' ,'city', 'address_1', 'address', 'address_2' ) ) ) {
			$key = 'billing_' . $key;
		}
		return isset( $this->_data[ $key ] ) ? $this->_data[ $key ] : '';
	}

	/**
	 * __set function.
	 * @todo use set_* methods
	 * @param mixed $property
	 * @param mixed $key
	 */
	public function __set( $key, $value ) {
		_doing_it_wrong( $key, 'Customer properties should not be set directly.', '2.7' );
		$key = $this->filter_legacy_key( $key );
		$this->_data[ $key ] = $value;
		$this->_changed = true;
	}

	/**
	 * Address and shipping_address are aliased, so we want to get the 'real' key name.
	 * For all other keys, we can just return it.
	 * @since 2.7.0
	 * @param  string $key
	 * @return string
	 */
	private function filter_legacy_key( $key ) {
		if ( 'address' === $key ) {
			$key = 'address_1';
		}
		if ( 'shipping_address' === $key ) {
			$key = 'shipping_address_1';
		}


		return $key;
	}

	/**
	 * Is customer VAT exempt?
	 * @return bool
	 */
	public function is_vat_exempt() {
		_deprecated_function( 'WC_Customer::is_vat_exempt', '2.7', 'WC_Customer::get_is_vat_exempt' );
		return $this->get_is_vat_exempt();
	}

	/**
	 * Has calculated shipping?
	 * @return bool
	 */
	public function has_calculated_shipping() {
		_deprecated_function( 'WC_Customer::has_calculated_shipping', '2.7', 'WC_Customer::get_calculated_shipping' );
		return $this->get_calculated_shipping();
	}

	/**
	 * Get default country for a customer.
	 * @return string
	 */
	public function get_default_country() {
		_deprecated_function( 'WC_Customer::get_default_country', '2.7', 'wc_get_customer_default_location' );
		$default = wc_get_customer_default_location();
		return $default['country'];
	}

	/**
	 * Get default state for a customer.
	 * @return string
	 */
	public function get_default_state() {
		_deprecated_function( 'WC_Customer::get_default_state', '2.7', 'wc_get_customer_default_location' );
		$default = wc_get_customer_default_location();
		return $default['state'];
	}

	/**
	 * Set customer address to match shop base address.
	 */
	public function set_to_base() {
		_deprecated_function( 'WC_Customer::set_to_base', '2.7', 'WC_Customer::set_billing_address_to_base' );
		$this->set_billing_address_to_base();
	}

	/**
	 * Set customer shipping address to base address.
	 */
	public function set_shipping_to_base() {
		_deprecated_function( 'WC_Customer::set_shipping_to_base', '2.7', 'WC_Customer::set_shipping_address_to_base' );
		$this->set_shipping_address_to_base();
	}

	/**
	 * Calculated shipping.
	 * @param boolean $calculated
	 */
	public function calculated_shipping( $calculated = true ) {
		_deprecated_function( 'WC_Customer::calculated_shipping', '2.7', 'WC_Customer::set_calculated_shipping' );
		$this->set_calculated_shipping( $calculated );
	}

	/**
	 * Set default data for a customer.
	 */
	public function set_default_data() {
		_deprecated_function( 'WC_Customer::set_default_data', '2.7', '' );
	}

	/**
	 * Is the user a paying customer?
	 * @todo should this be moved to a get_ readonly?
	 * @return bool
	 */
	function is_paying_customer( $user_id = '' ) {
		_deprecated_function( 'WC_Customer::is_paying_customer', '2.7', 'WC_Customer::get_is_paying_customer' );
		if ( ! empty( $user_id ) ) {
			$user_id = get_current_user_id();
		}
		return '1' === get_user_meta( $user_id, 'paying_customer', true );
	}

	/**
	 * Legacy get country.
	 */
	function get_country() {
		_deprecated_function( 'WC_Customer::get_country', '2.7', 'WC_Customer::get_billing_country' );
		return $this->get_billing_country();
	}

	/**
	 * Legacy get state.
	 */
	function get_state() {
		_deprecated_function( 'WC_Customer::get_state', '2.7', 'WC_Customer::get_billing_state' );
		return $this->get_billing_state();
	}

	/**
	 * Legacy get postcode.
	 */
	function get_postcode() {
		_deprecated_function( 'WC_Customer::get_postcode', '2.7', 'WC_Customer::get_billing_postcode' );
		return $this->get_billing_postcode();
	}

	/**
	 * Legacy get city.
	 */
	function get_city() {
		_deprecated_function( 'WC_Customer::get_city', '2.7', 'WC_Customer::get_billing_city' );
		return $this->get_billing_city();
	}

	/**
	 * Legacy set country.
	 */
	function set_country( $country ) {
		_deprecated_function( 'WC_Customer::set_country', '2.7', 'WC_Customer::set_billing_country' );
		$this->set_billing_country( $country );
	}

	/**
	 * Legacy set state.
	 */
	function set_state( $state ) {
		_deprecated_function( 'WC_Customer::set_state', '2.7', 'WC_Customer::set_billing_state' );
		$this->set_billing_state( $state );
	}

	/**
	 * Legacy set postcode.
	 */
	function set_postcode( $postcode ) {
		_deprecated_function( 'WC_Customer::set_postcode', '2.7', 'WC_Customer::set_billing_postcode' );
		$this->set_billing_postcode( $postcode );
	}

	/**
	 * Legacy set city.
	 */
	function set_city( $city ) {
		_deprecated_function( 'WC_Customer::set_city', '2.7', 'WC_Customer::set_billing_city' );
		$this->set_billing_city( $city );
	}

}
