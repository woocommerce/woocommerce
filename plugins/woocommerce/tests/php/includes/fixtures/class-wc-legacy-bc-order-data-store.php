<?php
/**
 * Test fixture: a minimal order data store that implements
 * WC_Order_Data_Store_Interface WITHOUT the get_total_shipping_tax_refunded()
 * method, simulating a legacy third-party order data store. Used to verify
 * that adding get_total_shipping_tax_refunded() to the interface would be a
 * BC break and to guard the fallback in WC_Order::get_total_shipping_tax_refunded().
 *
 * @package WooCommerce\Tests\Fixtures
 */

//phpcs:disable Squiz.Classes.ClassFileName.NoMatch, Squiz.Classes.ValidClassName.NotCamelCaps -- Legacy class name kept for parity with WC interfaces.

if ( class_exists( 'WC_Legacy_BC_Order_Data_Store' ) ) {
	return;
}

/**
 * Legacy-style order data store for BC regression coverage.
 *
 * Intentionally implements only the publicly required interfaces and omits
 * get_total_shipping_tax_refunded() on purpose. Methods return safe defaults
 * because no order persistence is exercised by the BC tests.
 */
class WC_Legacy_BC_Order_Data_Store implements WC_Object_Data_Store_Interface, WC_Order_Data_Store_Interface {

	// --- WC_Object_Data_Store_Interface ---

	/**
	 * No-op create.
	 *
	 * @param WC_Data $data Data object.
	 */
	public function create( &$data ) {}

	/**
	 * No-op read.
	 *
	 * @param WC_Data $data Data object.
	 */
	public function read( &$data ) {}

	/**
	 * No-op update.
	 *
	 * @param WC_Data $data Data object.
	 */
	public function update( &$data ) {}

	/**
	 * No-op delete.
	 *
	 * @param WC_Data $data Data object.
	 * @param array   $args Args.
	 * @return bool
	 */
	public function delete( &$data, $args = array() ) {
		return true;
	}

	/**
	 * No-op read_meta.
	 *
	 * @param WC_Data $data Data object.
	 * @return array
	 */
	public function read_meta( &$data ) {
		return array();
	}

	/**
	 * No-op delete_meta.
	 *
	 * @param WC_Data $data Data object.
	 * @param object  $meta Meta.
	 * @return array
	 */
	public function delete_meta( &$data, $meta ) {
		return array();
	}

	/**
	 * No-op add_meta.
	 *
	 * @param WC_Data $data Data object.
	 * @param object  $meta Meta.
	 * @return int
	 */
	public function add_meta( &$data, $meta ) {
		return 0;
	}

	/**
	 * No-op update_meta.
	 *
	 * @param WC_Data $data Data object.
	 * @param object  $meta Meta.
	 */
	public function update_meta( &$data, $meta ) {}

	// --- WC_Order_Data_Store_Interface ---

	/**
	 * Get amount already refunded.
	 *
	 * @param WC_Order $order Order.
	 * @return float
	 */
	public function get_total_refunded( $order ) {
		return 0.0;
	}

	/**
	 * Get total tax refunded.
	 *
	 * @param WC_Order $order Order.
	 * @return float
	 */
	public function get_total_tax_refunded( $order ) {
		return 0.0;
	}

	/**
	 * Get total shipping refunded.
	 *
	 * @param WC_Order $order Order.
	 * @return float
	 */
	public function get_total_shipping_refunded( $order ) {
		return 0.0;
	}

	/**
	 * Finds an Order ID based on an order key.
	 *
	 * @param string $order_key Key.
	 * @return int
	 */
	public function get_order_id_by_order_key( $order_key ) {
		return 0;
	}

	/**
	 * Return count of orders with a specific status.
	 *
	 * @param string $status Status.
	 * @return int
	 */
	public function get_order_count( $status ) {
		return 0;
	}

	/**
	 * Get all orders matching the passed in args.
	 *
	 * @param array $args Args.
	 * @return array
	 */
	public function get_orders( $args = array() ) {
		return array();
	}

	/**
	 * Get unpaid orders after a certain date.
	 *
	 * @param int $date Date.
	 * @return array
	 */
	public function get_unpaid_orders( $date ) {
		return array();
	}

	/**
	 * Search orders.
	 *
	 * @param string $term Term.
	 * @return array
	 */
	public function search_orders( $term ) {
		return array();
	}

	/**
	 * Get download permissions granted.
	 *
	 * @param WC_Order $order Order.
	 * @return bool
	 */
	public function get_download_permissions_granted( $order ) {
		return false;
	}

	/**
	 * Set download permissions granted.
	 *
	 * @param WC_Order $order Order.
	 * @param bool     $set   Value.
	 */
	public function set_download_permissions_granted( $order, $set ) {}

	/**
	 * Get recorded sales.
	 *
	 * @param WC_Order $order Order.
	 * @return bool
	 */
	public function get_recorded_sales( $order ) {
		return false;
	}

	/**
	 * Set recorded sales.
	 *
	 * @param WC_Order $order Order.
	 * @param bool     $set   Value.
	 */
	public function set_recorded_sales( $order, $set ) {}

	/**
	 * Get recorded coupon usage counts.
	 *
	 * @param WC_Order $order Order.
	 * @return bool
	 */
	public function get_recorded_coupon_usage_counts( $order ) {
		return false;
	}

	/**
	 * Set recorded coupon usage counts.
	 *
	 * @param WC_Order $order Order.
	 * @param bool     $set   Value.
	 */
	public function set_recorded_coupon_usage_counts( $order, $set ) {}

	/**
	 * Get order type.
	 *
	 * @param int $order_id ID.
	 * @return string
	 */
	public function get_order_type( $order_id ) {
		return 'shop_order';
	}

	// Intentionally NO get_total_shipping_tax_refunded() method here.
}
