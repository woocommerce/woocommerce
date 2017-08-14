<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A discount.
 *
 * Represents a fixed, percent or coupon based discount calculated by WC_Discounts class.
 *
 * @author  Automattic
 * @package WooCommerce/Classes
 * @version 3.2.0
 * @since   3.2.0
 */
class WC_Discount {

	/**
	 * Data array, with defaults.
	 *
	 * @var array
	 */
	protected $data = array(
		'amount'         => 0, // Discount amount.
		'discount_type'  => 'fixed', // Fixed, percent, or coupon.
		'discount_total' => 0,
	);

	/**
	 * Get discount amount.
	 *
	 * @return int
	 */
	public function get_amount() {
		return $this->data['amount'];
	}

	/**
	 * Discount amount - either fixed or percentage.
	 *
	 * @param string $raw_amount Amount discount gives.
	 */
	public function set_amount( $raw_amount ) {
		$this->data['amount'] = wc_format_decimal( $raw_amount );
	}

	/**
	 * Get discount type.
	 *
	 * @return string
	 */
	public function get_discount_type() {
		return $this->data['discount_type'];
	}

	/**
	 * Set discount type.
	 *
	 * @param string $discount_type Type of discount.
	 */
	public function set_discount_type( $discount_type ) {
		$this->data['discount_type'] = $discount_type;
	}

	/**
	 * Get discount total.
	 *
	 * @return int
	 */
	public function get_discount_total() {
		return $this->data['discount_total'];
	}

	/**
	 * Discount total.
	 *
	 * @param string $total Total discount applied.
	 */
	public function set_discount_total( $total ) {
		$this->data['discount_total'] = wc_format_decimal( $total );
	}
}
