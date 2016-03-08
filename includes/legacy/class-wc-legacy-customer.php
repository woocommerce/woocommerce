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
class WC_Legacy_Customer  {

	/**
	 * __isset legacy.
	 * @param mixed $key
	 * @return bool
	 */
	public function __isset( $key ) {
		$legacy_keys = array(
			'country', 'state', 'postcode' ,'city', 'address_1', 'address_2', 'shipping_country', 'shipping_state',
			'shipping_postcode', 'shipping_city', 'shipping_address_1', 'shipping_address_2', 'is_vat_exempt', 'calculated_shipping',
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

}
