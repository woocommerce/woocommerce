<?php
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

/**
 * Discount class.
 */
class WC_Discount {

	/**
	 * Data array, with defaults.
	 *
	 * @var array
	 */
	protected $data = array(
		'id'       => '',
		'discount' => 0,
	);

	/**
	 * Coupon ID.
	 *
	 * @param string $id
	 */
	public function set_id( $id ) {

	}

	/**
	 * Discount amount - either fixed or percentage.
	 *
	 * @param string $amount
	 */
	public function set_amount( $amount ) {

	}

	/**
	 * Amount of discount this has given in total.
	 */
	public function set_discount_total() {

	}

	/**
	 * Array of negative taxes.
	 */
	public function set_taxes() {

	}

	/**
	 * Calculates the amount of negative tax to apply for this discount, since
	 * discounts are applied before tax.
	 *
	 * For percent discounts this is simply a percent of each cart item's tax.
	 *
	 * For fixed discounts, the taxes are calculated proportionally so the
	 * discount is fairly split between items.
	 *
	 * @return [type] [description]
	 */
	public function calculate_negative_taxes() {

	}

}
