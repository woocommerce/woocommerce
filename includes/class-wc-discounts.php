<?php
/**
 * Discount calculation
 *
 * @author  Automattic
 * @package WooCommerce/Classes
 * @version 3.2.0
 * @since   3.2.0
 */

/**
 * Discounts class.
 */
class WC_Discounts {

	/**
	 * Display name.
	 *
	 * @var string
	 */
	protected $name = '';

	/**
	 * Amount of discount.
	 *
	 * @var float
	 */
	protected $amount = 0;

	/**
	 * Type of discount.
	 *
	 * @var string
	 */
	protected $type = 'percent';

	/**
	 * Allow or not accumulate with other discounts.
	 *
	 * @var bool
	 */
	protected $individual_use = false;

	/**
	 * Coupon ID.
	 *
	 * @var int
	 */
	protected $coupon_id = 0;

	/**
	 * Get available discount types.
	 *
	 * @return array
	 */
	public function get_available_types() {
		return array(
			'percent',
			'fixed_cart',
			'fixed_product',
		);
	}

	/**
	 * Set name.
	 *
	 * @param string $name New name.
	 */
	public function set_name( $name ) {
		$this->name = $name;
	}

	/**
	 * Set amount.
	 *
	 * @param float $amount Total discount amount.
	 */
	public function set_amount( $amount ) {
		$this->amount = $amount;
	}

	/**
	 * Set type.
	 *
	 * @param string $type Type of discount.
	 */
	public function set_type( $type ) {
		$this->type = in_array( $type, $this->get_available_types(), true ) ? $type : 'percent';
	}

	/**
	 * Set individual use.
	 *
	 * @param bool $individual_use Allow individual use.
	 */
	public function set_individual_use( $individual_use ) {
		$this->individual_use = $individual_use;
	}

	/**
	 * Set coupon ID.
	 *
	 * @param int $coupon_id Coupon ID.
	 */
	public function set_coupon_id( $coupon_id ) {
		$this->coupon_id = $coupon_id;
	}

	/**
	 * Get name.
	 *
	 * @return string.
	 */
	public function get_name() {
		return $this->name;
	}

	/**
	 * Get amount.
	 *
	 * @return float
	 */
	public function get_amount() {
		return $this->amount;
	}

	/**
	 * Get type.
	 *
	 * @return string
	 */
	public function get_type() {
		return $this->type;
	}

	/**
	 * Get individual use.
	 *
	 * @return bool
	 */
	public function get_individual_use() {
		return $this->individual_use;
	}

	/**
	 * Get coupon ID.
	 *
	 * @return int
	 */
	public function get_coupon_id() {
		return $this->coupon_id;
	}
}
