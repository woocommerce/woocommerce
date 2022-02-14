<?php
/**
 * OrdersTableDataStore class file.
 */

namespace Automattic\WooCommerce\Internal\DataStores\Orders;

defined( 'ABSPATH' ) || exit;

/**
 * This class is the standard data store to be used when the custom orders table is in use.
 */
class OrdersTableDataStore extends \Abstract_WC_Order_Data_Store_CPT implements \WC_Object_Data_Store_Interface, \WC_Order_Data_Store_Interface {

	/**
	 * Get the custom orders table name.
	 *
	 * @return string The custom orders table name.
	 */
	public function get_orders_table_name() {
		global $wpdb;

		return $wpdb->prefix . 'wc_orders';
	}

	// TODO: Add methods for other table names as appropriate.

	//phpcs:disable Squiz.Commenting.FunctionComment.Missing

	public function get_total_refunded( $order ) {
		// TODO: Implement get_total_refunded() method.
		return 0;
	}

	public function get_total_tax_refunded( $order ) {
		// TODO: Implement get_total_tax_refunded() method.
		return 0;
	}

	public function get_total_shipping_refunded( $order ) {
		// TODO: Implement get_total_shipping_refunded() method.
		return 0;
	}

	public function get_order_id_by_order_key( $order_key ) {
		// TODO: Implement get_order_id_by_order_key() method.
		return 0;
	}

	public function get_order_count( $status ) {
		// TODO: Implement get_order_count() method.
		return 0;
	}

	public function get_orders( $args = array() ) {
		// TODO: Implement get_orders() method.
		return array();
	}

	public function get_unpaid_orders( $date ) {
		// TODO: Implement get_unpaid_orders() method.
		return array();
	}

	public function search_orders( $term ) {
		// TODO: Implement search_orders() method.
		return array();
	}

	public function get_download_permissions_granted( $order ) {
		// TODO: Implement get_download_permissions_granted() method.
		false;
	}

	public function set_download_permissions_granted( $order, $set ) {
		// TODO: Implement set_download_permissions_granted() method.
	}

	public function get_recorded_sales( $order ) {
		// TODO: Implement get_recorded_sales() method.
		return false;
	}

	public function set_recorded_sales( $order, $set ) {
		// TODO: Implement set_recorded_sales() method.
	}

	public function get_recorded_coupon_usage_counts( $order ) {
		// TODO: Implement get_recorded_coupon_usage_counts() method.
		return false;
	}

	public function set_recorded_coupon_usage_counts( $order, $set ) {
		// TODO: Implement set_recorded_coupon_usage_counts() method.
	}

	public function get_order_type( $order_id ) {
		// TODO: Implement get_order_type() method.
		return 'shop_order';
	}

	public function create( &$order ) {
		throw new \Exception( 'Unimplemented' );
	}

	public function update( &$order ) {
		throw new \Exception( 'Unimplemented' );
	}

	public function get_coupon_held_keys( $order, $coupon_id = null ) {
		return array();
	}

	public function get_coupon_held_keys_for_users( $order, $coupon_id = null ) {
		return array();
	}

	public function set_coupon_held_keys( $order, $held_keys, $held_keys_for_user ) {
		throw new \Exception( 'Unimplemented' );
	}

	public function release_held_coupons( $order, $save = true ) {
		throw new \Exception( 'Unimplemented' );
	}

	public function get_stock_reduced( $order ) {
		return false;
	}

	public function set_stock_reduced( $order, $set ) {
		throw new \Exception( 'Unimplemented' );
	}

	public function query( $query_vars ) {
		return array();
	}

	public function get_order_item_type( $order, $order_item_id ) {
		return 'line_item';
	}

	//phpcs:enable Squiz.Commenting.FunctionComment.Missing
}
