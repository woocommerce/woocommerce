<?php
/**
 * Cart totals calculation class.
 *
 * Methods are protected and class is final to keep this as an internal API.
 * May be opened in the future once structure is stable.
 *
 * @author  Automattic
 * @package WooCommerce/Classes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Cart_Totals class.
 *
 * @todo woocommerce_calculate_totals action for carts.
 * @todo woocommerce_calculated_total filter for carts.
 * @todo record coupon totals and counts for cart.
 *
 * @since 3.2.0
 */
final class WC_Cart_Totals extends WC_Totals {

	/**
	 * Sets up the items provided, and calculate totals.
	 *
	 * @since 3.2.0
	 * @param object $object Cart or order object to calculate totals for.
	 */
	public function __construct( &$object = null ) {
		if ( is_a( $object, 'WC_Cart' ) ) {
			parent::__construct( $object );
			$this->calculate();
		}
	}

	/**
	 * Handles a cart or order object passed in for calculation. Normalises data
	 * into the same format for use by this class.
	 *
	 * Each item is made up of the following props, in addition to those returned by get_default_item_props() for totals.
	 * 	- key: An identifier for the item (cart item key or line item ID).
	 *  - cart_item: For carts, the cart item from the cart which may include custom data.
	 *  - quantity: The qty for this line.
	 *  - price: The line price in cents.
	 *  - product: The product object this cart item is for.
	 *
	 * @since 3.2.0
	 */
	protected function set_items() {
		$this->items = array();

		foreach ( $this->object->get_cart() as $cart_item_key => $cart_item ) {
			$item                          = $this->get_default_item_props();
			$item->key                     = $cart_item_key;
			$item->object                  = $cart_item;
			$item->quantity                = $cart_item['quantity'];
			$item->price                   = $this->add_precision( $cart_item['data']->get_price() ) * $cart_item['quantity'];
			$item->product                 = $cart_item['data'];
			$this->items[ $cart_item_key ] = $item;
		}
	}

	/**
	 * Get fee objects from the cart. Normalises data
	 * into the same format for use by this class.
	 *
	 * @since 3.2.0
	 */
	protected function set_fees() {
		$this->fees = array();
		$this->object->calculate_fees();

		foreach ( $this->object->get_fees() as $fee_key => $fee_object ) {
			$fee         = $this->get_default_fee_props();
			$fee->object = $fee_object;
			$fee->total  = $this->add_precision( $fee->object->amount );

			if ( wc_tax_enabled() && $fee->object->taxable ) {
				$fee->taxes     = WC_Tax::calc_tax( $fee->total, WC_Tax::get_rates( $fee->object->tax_class ), false );
				$fee->total_tax = array_sum( $fee->taxes );
			}

			$this->fees[ $fee_key ] = $fee;
		}
	}

	/**
	 * Get shipping methods from the cart and normalise.
	 *
	 * @since 3.2.0
	 */
	protected function set_shipping() {
		$this->shipping = array();

		foreach ( $this->object->calculate_shipping() as $key => $shipping_object ) {
			$shipping_line            = $this->get_default_shipping_props();
			$shipping_line->object    = $shipping_object;
			$shipping_line->total     = $this->add_precision( $shipping_object->cost );
			$shipping_line->taxes     = $this->add_precision( $shipping_object->taxes );
			$shipping_line->total_tax = $this->add_precision( array_sum( $shipping_object->taxes ) );
			$this->shipping[ $key ]   = $shipping_line;
		}
	}

	/**
	 * Return array of coupon objects from the cart. Normalises data
	 * into the same format for use by this class.
	 *
	 * @since  3.2.0
	 */
	protected function set_coupons() {
		$this->coupons = $this->object->get_coupons();
	}

	/**
	 * Totals are costs after discounts.
	 *
	 * @since 3.2.0
	 */
	protected function calculate_item_totals() {
		parent::calculate_item_totals();

		$this->object->subtotal        = $this->get_total( 'items_total' ) + $this->get_total( 'items_total_tax' );
		$this->object->subtotal_ex_tax = $this->get_total( 'items_total' );
	}

	/**
	 * Triggers the cart fees API, grabs the list of fees, and calculates taxes.
	 *
	 * Note: This class sets the totals for the 'object' as they are calculated. This is so that APIs like the fees API can see these totals if needed.
	 *
	 * @since 3.2.0
	 */
	protected function calculate_fee_totals() {
		parent::calculate_fee_totals();

		foreach ( $this->fees as $fee_key => $fee ) {
			$this->object->fees[ $fee_key ]->tax      = $this->remove_precision( $fee->total_tax );
			$this->object->fees[ $fee_key ]->tax_data = $this->remove_precision( $fee->taxes );
		}
		$this->object->fee_total = $this->remove_precision( array_sum( wp_list_pluck( $this->fees, 'total' ) ) );
	}

	/**
	 * Calculate any shipping taxes.
	 *
	 * @since 3.2.0
	 */
	protected function calculate_shipping_totals() {
		parent::calculate_shipping_totals();

		$this->object->shipping_total     = $this->get_total( 'shipping_total' );
		$this->object->shipping_tax_total = $this->get_total( 'shipping_tax_total' );
	}

	/**
	 * Main cart totals.
	 *
	 * @since 3.2.0
	 */
	protected function calculate_totals() {
		parent::calculate_totals();

		$this->object->tax_total = $this->get_total( 'tax_total' );
		$this->object->total     = $this->get_total( 'total' );
	}
}
