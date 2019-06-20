<?php
/**
 * WC Admin Order
 *
 * WC Admin Order class that adds some functionality on top of general WooCommerce WC_Order.
 *
 * @package WooCommerce Admin/Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_Admin_Order class.
 */
class WC_Admin_Order extends WC_Order {
	/**
	 * Order traits.
	 */
	use WC_Admin_Order_Trait;

	/**
	 * Holds refund amounts and quantities for the order.
	 *
	 * @var void|array
	 */
	protected $refunded_line_items;

	/**
	 * Add filter(s) required to hook WC_Admin_Order class to substitute WC_Order.
	 */
	public static function add_filters() {
		add_filter( 'woocommerce_order_class', array( __CLASS__, 'order_class_name' ), 10, 3 );
	}

	/**
	 * Filter function to swap class WC_Order for WC_Admin_Order in cases when it's suitable.
	 *
	 * @param string $classname Name of the class to be created.
	 * @param string $order_type Type of order object to be created.
	 * @param number $order_id Order id to create.
	 *
	 * @return string
	 */
	public static function order_class_name( $classname, $order_type, $order_id ) {
		if ( 'WC_Order' === $classname ) {
			return 'WC_Admin_Order';
		} else {
			return $classname;
		}
	}

	/**
	 * Get the customer ID used for reports in the customer lookup table.
	 *
	 * @return int
	 */
	public function get_report_customer_id() {
		return WC_Admin_Reports_Customers_Data_Store::get_or_create_customer_from_order( $this );
	}

	/**
	 * Returns true if the customer has made an earlier order.
	 *
	 * @return bool
	 */
	public function is_returning_customer() {
		return WC_Admin_Reports_Orders_Stats_Data_Store::is_returning_customer( $this );
	}

	/**
	 * Get the customer's first name.
	 */
	public function get_customer_first_name() {
		if ( $this->get_user_id() ) {
			return get_user_meta( $this->get_user_id(), 'first_name', true );
		}

		if ( '' !== $this->get_billing_first_name( 'edit' ) ) {
			return $this->get_billing_first_name( 'edit' );
		} else {
			return $this->get_shipping_first_name( 'edit' );
		}
	}

	/**
	 * Get the customer's last name.
	 */
	public function get_customer_last_name() {
		if ( $this->get_user_id() ) {
			return get_user_meta( $this->get_user_id(), 'last_name', true );
		}

		if ( '' !== $this->get_billing_last_name( 'edit' ) ) {
			return $this->get_billing_last_name( 'edit' );
		} else {
			return $this->get_shipping_last_name( 'edit' );
		}
	}
}
