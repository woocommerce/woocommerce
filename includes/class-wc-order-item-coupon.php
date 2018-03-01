<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Order Line Item (coupon).
 *
 * @version     3.0.0
 * @since       3.0.0
 * @package     WooCommerce/Classes
 * @author      WooCommerce
 */
class WC_Order_Item_Coupon extends WC_Order_Item {

	/**
	 * Order Data array. This is the core order data exposed in APIs since 3.0.0.
	 * @since 3.0.0
	 * @var array
	 */
	protected $extra_data = array(
		'code'         => '',
		'discount'     => 0,
		'discount_tax' => 0,
	);

	/*
	|--------------------------------------------------------------------------
	| Setters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Set order item name.
	 *
	 * @param string $value
	 * @throws WC_Data_Exception
	 */
	public function set_name( $value ) {
		return $this->set_code( $value );
	}

	/**
	 * Set code.
	 *
	 * @param string $value
	 * @throws WC_Data_Exception
	 */
	public function set_code( $value ) {
		$this->set_prop( 'code', wc_clean( $value ) );
	}

	/**
	 * Set discount amount.
	 *
	 * @param string $value
	 * @throws WC_Data_Exception
	 */
	public function set_discount( $value ) {
		$this->set_prop( 'discount', wc_format_decimal( $value ) );
	}

	/**
	 * Set discounted tax amount.
	 *
	 * @param string $value
	 * @throws WC_Data_Exception
	 */
	public function set_discount_tax( $value ) {
		$this->set_prop( 'discount_tax', wc_format_decimal( $value ) );
	}

	/*
	|--------------------------------------------------------------------------
	| Getters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Get order item type.
	 *
	 * @return string
	 */
	public function get_type() {
		return 'coupon';
	}

	/**
	 * Get order item name.
	 *
	 * @param  string $context
	 * @return string
	 */
	public function get_name( $context = 'view' ) {
		return $this->get_code( $context );
	}

	/**
	 * Get coupon code.
	 *
	 * @param  string $context
	 * @return string
	 */
	public function get_code( $context = 'view' ) {
		return $this->get_prop( 'code', $context );
	}

	/**
	 * Get discount amount.
	 *
	 * @param  string $context
	 * @return string
	 */
	public function get_discount( $context = 'view' ) {
		return $this->get_prop( 'discount', $context );
	}

	/**
	 * Get discounted tax amount.
	 *
	 * @param string $context
	 *
	 * @return string
	 */
	public function get_discount_tax( $context = 'view' ) {
		return $this->get_prop( 'discount_tax', $context );
	}

	/*
	|--------------------------------------------------------------------------
	| Array Access Methods
	|--------------------------------------------------------------------------
	|
	| For backwards compatibility with legacy arrays.
	|
	*/

	/**
	 * offsetGet for ArrayAccess/Backwards compatibility.
	 * @deprecated Add deprecation notices in future release.
	 * @param string $offset
	 * @return mixed
	 */
	public function offsetGet( $offset ) {
		if ( 'discount_amount' === $offset ) {
			$offset = 'discount';
		} elseif ( 'discount_amount_tax' === $offset ) {
			$offset = 'discount_tax';
		}
		return parent::offsetGet( $offset );
	}

	/**
	 * offsetSet for ArrayAccess/Backwards compatibility.
	 * @deprecated Add deprecation notices in future release.
	 * @param string $offset
	 * @param mixed $value
	 */
	public function offsetSet( $offset, $value ) {
		if ( 'discount_amount' === $offset ) {
			$offset = 'discount';
		} elseif ( 'discount_amount_tax' === $offset ) {
			$offset = 'discount_tax';
		}
		parent::offsetSet( $offset, $value );
	}

	/**
	 * offsetExists for ArrayAccess
	 * @param string $offset
	 * @return bool
	 */
	public function offsetExists( $offset ) {
		if ( in_array( $offset, array( 'discount_amount', 'discount_amount_tax' ) ) ) {
			return true;
		}
		return parent::offsetExists( $offset );
	}
}
