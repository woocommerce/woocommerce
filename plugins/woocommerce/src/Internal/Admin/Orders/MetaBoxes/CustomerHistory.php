<?php

namespace Automattic\WooCommerce\Internal\Admin\Orders\MetaBoxes;

use WC_Order;

/**
 * Class CustomerHistory
 *
 * @since x.x.x
 */
class CustomerHistory {

	/**
	 * Output the customer history template for the order.
	 *
	 * @since x.x.x
	 *
	 * @param WC_Order $order The order object.
	 *
	 * @return void
	 */
	public function output( WC_Order $order ) {
		$this->display_customer_history( $order->get_customer_id() );
	}

	/**
	 * Display the customer history template for the customer.
	 *
	 * @since x.x.x
	 *
	 * @param int $customer_id The customer ID.
	 *
	 * @return void
	 */
	private function display_customer_history( int $customer_id ) {
		// Calculate the data needed for the template.
		$order_count   = wc_get_customer_order_count( $customer_id );
		$total_spent   = wc_get_customer_total_spent( $customer_id );
		$average_spent = $order_count ? $total_spent / $order_count : 0;

		// Include the template file.
		include dirname( WC_PLUGIN_FILE ) . '/templates/order/customer-history.php';
	}
}
