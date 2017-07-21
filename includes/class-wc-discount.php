<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A single discount.
 *
 * Represents a fixed or percent based discount at cart level. Extended by cart
 * and order discounts since they share the same logic for things like tax
 * calculation.
 *
 * @author  Automattic
 * @package WooCommerce/Classes
 * @version 3.2.0
 * @since   3.2.0
 */
class WC_Discount extends WC_Data {

	/**
	 * Data array, with defaults.
	 *
	 * @var array
	 */
	protected $data = array(
		'amount'        => 0,
		'discount'      => 0,
		'discount_type' => 'fixed_cart',
	);

	/**
	 * Checks the coupon type.
	 * @param  string $type Array or string of types
	 * @return bool
	 */
	public function is_type( $type ) {
		return ( $this->get_discount_type() === $type || ( is_array( $type ) && in_array( $this->get_discount_type(), $type ) ) );
	}

	/**
	 * Prefix for action and filter hooks on data.
	 *
	 * @return string
	 */
	protected function get_hook_prefix() {
		return 'woocommerce_discount_get_';
	}

	/**
	 * Returns the ID of this dicount.
	 *
	 * @return string
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * Discount ID.
	 *
	 * @param string $id
	 */
	public function set_id( $id ) {
		$this->id = $id;
	}

	/**
	 * Get discount amount.
	 *
	 * @param  string $context
	 * @return float
	 */
	public function get_amount( $context = 'view' ) {
		return $this->get_prop( 'amount', $context );
	}

	/**
	 * Discount amount - either fixed or percentage.
	 *
	 * @param string $raw_amount
	 */
	public function set_amount( $raw_amount ) {
		if ( strstr( $raw_amount, '%' ) ) {
			$amount = absint( rtrim( $raw_amount, '%' ) );
			$this->set_prop( 'amount', $amount );
			$this->set_discount_type( 'percent' );
		} else {
			$this->set_prop( 'amount', wc_format_decimal( $amount ) );
		}
	}

	/**
	 * Get discount type.
	 *
	 * @param  string $context
	 * @return string
	 */
	public function get_discount_type( $context = 'view' ) {
		return $this->get_prop( 'discount_type', $context );
	}

	/**
	 * Set discount type.
	 *
	 * @param  string $discount_type
	 * @throws WC_Data_Exception
	 */
	public function set_discount_type( $discount_type ) {
		if ( ! in_array( $discount_type, array( 'percent', 'fixed_cart' ) ) {
			$this->error( 'coupon_invalid_discount_type', __( 'Invalid discount type', 'woocommerce' ) );
		}
		$this->set_prop( 'discount_type', $discount_type );
	}

	/**
	 * Amount of discount this has given in total. @todo should this be here?
	 */
	public function set_discount_total() {}

	/**
	 * Array of negative taxes. @todo should this be here?
	 */
	public function set_taxes() {}

	/**
	 * Calculates the amount of negative tax to apply for this discount, since
	 * discounts are applied before tax.
	 *
	 * For percent discounts this is simply a percent of each cart item's tax.
	 *
	 * For fixed discounts, the taxes are calculated proportionally so the
	 * discount is fairly split between items.
	 *
	 * @todo Should this bere here?
	 */
	public function calculate_negative_taxes() {}

}
