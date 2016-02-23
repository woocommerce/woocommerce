<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Legacy Abstract Coupon
 *
 * Legacy and deprecated functions are here to keep the WC_Legacy_Coupon class clean.
 * This class will be removed in future versions.
 *
 * @class       WC_Legacy_Coupon
 * @version     2.6.0
 * @package     WooCommerce/Classes
 * @category    Class
 * @author      WooThemes
 */
class WC_Legacy_Coupon {

	/** @public bool Coupon exists */
	public $exists = false;

	public function __construct() {
		$this->exists = ( $this->get_id() > 0 ) ? true : false;
	}

	/**
	 * Magic __isset method for backwards compatibility.
	 * @param  string $key
	 * @return bool
	 */
	public function __isset( $key ) {
		// Legacy properties which could be accessed directly in the past.
		if ( in_array( $key, array( 'coupon_custom_fields', 'type', 'amount', 'code' ) ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Magic __get method for backwards compatibility.
	 * @param  string $key
	 * @return mixed
	 */
	public function __get( $key ) {
		/**
		 * Maps legacy vars to new getters.
		 * @todo finish mapping these..
		 */
		if ( 'coupon_custom_fields' === $key ) {
			_doing_it_wrong( $key, 'Coupon properties should not be accessed directly.', '2.6' );
			$value = $this->id ? $this->get_custom_fields() : array();
		} elseif ( 'type' === $key ) {
			_doing_it_wrong( $key, 'Coupon properties should not be accessed directly.', '2.6' );
			$value = $this->get_discount_type();
		} elseif ( 'amount' === $key ) {
			_doing_it_wrong( $key, 'Coupon properties should not be accessed directly.', '2.6' );
			$value = $this->get_amount();
		} elseif ( 'code' === $key ) {
			_doing_it_wrong( $key, 'Coupon properties should not be accessed directly.', '2.6' );
			$value = $this->get_code();
		} else {
			_doing_it_wrong( $key, 'Coupon properties should not be accessed directly.', '2.6' );
			$value = '';
		}
		return $value;
	}

	/**
	 * Format loaded data as array.
	 * @param  string|array $array
	 * @return array
	 */
	public function format_array( $array ) {
		_deprecated_function( 'format_array', '2.6', '' );
		if ( ! is_array( $array ) ) {
			if ( is_serialized( $array ) ) {
				$array = maybe_unserialize( $array );
			} else {
				$array = explode( ',', $array );
			}
		}
		return array_filter( array_map( 'trim', array_map( 'strtolower', $array ) ) );
	}


	/**
	 * Check if coupon needs applying before tax.
	 *
	 * @return bool
	 */
	public function apply_before_tax() {
		_deprecated_function( 'apply_before_tax', '2.6', '' );
		return true;
	}

	/**
	 * Check if a coupon enables free shipping.
	 *
	 * @return bool
	 */
	public function enable_free_shipping() {
		_deprecated_function( 'enable_free_shipping', '2.6', 'get_free_shipping_enabled' );
		return $this->get_free_shipping_enabled();
	}

	/**
	 * Check if a coupon excludes sale items.
	 *
	 * @return bool
	 */
	public function exclude_sale_items() {
		_deprecated_function( 'exclude_sale_items', '2.6', 'get_should_exclude_sale_items' );
		return $this->get_should_exclude_sale_items();
	}

}
