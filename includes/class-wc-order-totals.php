<?php
/**
 * Order totals calculation class.
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
 * WC_Order_Totals class.
 *
 * @since 3.2.0
 */
final class WC_Order_Totals extends WC_Totals {

	/**
	 * Sets up the items provided, and calculate totals.
	 *
	 * @since 3.2.0
	 * @param object $object Cart or order object to calculate totals for.
	 */
	public function __construct( &$object = null ) {
		if ( is_a( $object, 'WC_Order' ) ) {
			parent::__construct( $object );
			$this->calculate();
		}
	}

	/**
	 * Handles an order object passed in for calculation. Normalises data into the same format for use by this class.
	 *
	 * @since 3.2.0
	 */
	protected function set_items() {
		$this->items = array();

		foreach ( $this->object->get_items() as $order_item_id => $order_item ) {
			$item                          = $this->get_default_item_props();
			$item->key                     = $order_item_id;
			$item->object                  = $order_item;
			$item->product                 = $order_item->get_product();
			$item->quantity                = $order_item->get_quantity();
			$item->price                   = $this->add_precision( $order_item->get_subtotal() );
			$this->items[ $order_item_id ] = $item;
		}
	}

	/**
	 * Get fee objects from the cart. Normalises data into the same format for use by this class.
	 *
	 * @since 3.2.0
	 */
	protected function set_fees() {
		$this->fees = array();

		foreach ( $this->object->get_fees() as $fee_key => $fee_object ) {
			$fee            = $this->get_default_fee_props();
			$fee->object    = $fee_object;
			$fee->total     = $this->add_precision( $fee_object->get_total() );
			$fee->taxes     = $this->add_precision( $fee_object->get_taxes() );
			$fee->total_tax = $this->add_precision( $fee_object->get_total_tax() );
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

		foreach ( $this->object->get_shipping_methods() as $key => $shipping_object ) {
			$shipping_line            = $this->get_default_shipping_props();
			$shipping_line->object    = $shipping_object;
			$shipping_line->total     = $this->add_precision( $shipping_object->get_total() );
			$shipping_line->taxes     = $this->add_precision( $shipping_object->get_taxes() );
			$shipping_line->total_tax = $this->add_precision( $shipping_object->get_total_tax() );
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
		//$this->coupons = $this->object->get_coupons(); @todo
	}

	/**
	 * Main cart totals. Set all order totals here after calculation.
	 *
	 * @since 3.2.0
	 */
	protected function calculate_totals() {
		parent::calculate_totals();

		$this->object->set_shipping_total( $this->get_total( 'shipping_total' ) );
		$this->object->set_shipping_tax( $this->get_total( 'shipping_tax_total' ) );
		$this->object->set_cart_tax( $this->get_total( 'tax_total' ) );
		$this->object->set_total( $this->get_total( 'total' ) );
	}
}
