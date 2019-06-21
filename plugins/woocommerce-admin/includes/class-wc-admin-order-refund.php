<?php
/**
 * WC Admin Order Refund
 *
 * WC Admin Order Refund class that adds some functionality on top of general WooCommerce WC_Order_Refund.
 *
 * @package WooCommerce Admin/Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_Admin_Order_Refund class.
 */
class WC_Admin_Order_Refund extends WC_Order_Refund {
	/**
	 * Order traits.
	 */
	use WC_Admin_Order_Trait;

	/**
	 * Add filter(s) required to hook WC_Admin_Order_Refund class to substitute WC_Order_Refund.
	 */
	public static function add_filters() {
		add_filter( 'woocommerce_order_class', array( __CLASS__, 'order_class_name' ), 10, 3 );
	}

	/**
	 * Filter function to swap class WC_Order_Refund for WC_Admin_Order_Refund in cases when it's suitable.
	 *
	 * @param string $classname Name of the class to be created.
	 * @param string $order_type Type of order object to be created.
	 * @param number $order_id Order id to create.
	 *
	 * @return string
	 */
	public static function order_class_name( $classname, $order_type, $order_id ) {
		if ( 'WC_Order_Refund' === $classname ) {
			return 'WC_Admin_Order_Refund';
		} else {
			return $classname;
		}
	}

	/**
	 * Get the customer ID of the parent order used for reports in the customer lookup table.
	 *
	 * @return int|bool Customer ID of parent order, or false if parent order not found.
	 */
	public function get_report_customer_id() {
		$parent_order = wc_get_order( $this->get_parent_id() );

		if ( ! $parent_order ) {
			return false;
		}

		return WC_Admin_Reports_Customers_Data_Store::get_or_create_customer_from_order( $parent_order );
	}

	/**
	 * Returns null since refunds should not be counted towards returning customer counts.
	 *
	 * @return null
	 */
	public function is_returning_customer() {
		return null;
	}
}
