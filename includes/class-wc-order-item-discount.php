<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Order Line Item (discount).
 *
 * @version     3.2.0
 * @since       3.2.0
 * @package     WooCommerce/Classes
 * @author      WooCommerce
 */
class WC_Order_Item_Discount extends WC_Order_Item {

	/**
	 * Data array.
	 *
	 * @var array
	 */
	protected $extra_data = array(
		'amount'         => 0, // Discount amount.
		'discount_type'  => 'fixed', // Fixed or percent type.
		'discount_total' => 0,
	);

	/*
	|--------------------------------------------------------------------------
	| Setters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Set amount.
	 *
	 * @param string $value Value to set.
	 */
	public function set_amount( $value ) {
		$this->set_prop( 'amount', $value );
	}

	/**
	 * Set discount_type.
	 *
	 * @param string $value Value to set.
	 */
	public function set_discount_type( $value ) {
		$this->set_prop( 'discount_type', $value );
	}

	/**
	 * Set discount total.
	 *
	 * @param string $value Value to set.
	 */
	public function set_discount_total( $value ) {
		$this->set_prop( 'discount_total', wc_format_decimal( $value ) );
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
		return 'discount';
	}

	/**
	 * Get amount.
	 *
	 * @param  string $context View or edit context.
	 * @return string
	 */
	public function get_amount( $context = 'view' ) {
		return $this->get_prop( 'amount', $context );
	}

	/**
	 * Get discount_type.
	 *
	 * @param  string $context View or edit context.
	 * @return string
	 */
	public function get_discount_type( $context = 'view' ) {
		return $this->get_prop( 'discount_type', $context );
	}

	/**
	 * Get discount_total.
	 *
	 * @param string $context View or edit context.
	 * @return string
	 */
	public function get_discount_total( $context = 'view' ) {
		return $this->get_prop( 'discount_total', $context );
	}
}
