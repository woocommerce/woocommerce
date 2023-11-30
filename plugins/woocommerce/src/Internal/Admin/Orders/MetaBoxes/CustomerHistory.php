<?php

namespace Automattic\WooCommerce\Internal\Admin\Orders\MetaBoxes;

use WC_Order;

/**
 * Class CustomerHistory
 *
 * @since 8.5.0
 */
class CustomerHistory {

	/**
	 * Output the customer history template for the order.
	 *
	 * @param WC_Order $order The order object.
	 *
	 * @return void
	 */
	public function output( WC_Order $order ): void {
		$this->display_customer_history( $order->get_customer_id(), $order->get_billing_email() );
	}

	/**
	 * Display the customer history template for the customer.
	 *
	 * @param int    $customer_id The customer ID.
	 * @param string $billing_email The customer billing email.
	 *
	 * @return void
	 */
	private function display_customer_history( int $customer_id, string $billing_email ): void {
		$has_customer_id = false;
		if ( $customer_id ) {
			$has_customer_id = true;
			$args            = $this->get_customer_history( $customer_id );
		} elseif ( $billing_email ) {
			$args = $this->get_customer_history( $billing_email );
		} else {
			$args = array(
				'order_count'   => 0,
				'total_spent'   => 0,
				'average_spent' => 0,
			);
		}

		$args['has_customer_id'] = $has_customer_id;
		wc_get_template( 'order/customer-history.php', $args );
	}

	/**
	 * Get the order history for the customer (matches Customers report).
	 *
	 * @param mixed $customer_identifier The customer ID or billing email.
	 *
	 * @return array Order count, total spend, and average spend per order.
	 */
	private function get_customer_history( $customer_identifier ): array {

		// Get the valid customer orders.
		$args = array(
			'limit'  => - 1,
			'return' => 'objects',
			// Don't count cancelled or failed orders.
			'status' => array( 'pending', 'processing', 'on-hold', 'completed', 'refunded' ),
			'type'   => 'shop_order',
		);

		// If the customer_identifier is a valid ID, use it. Otherwise, use the billing email.
		if ( is_numeric( $customer_identifier ) && $customer_identifier > 0 ) {
			$args['customer_id'] = $customer_identifier;
		} else {
			$args['billing_email'] = $customer_identifier;
			$args['customer_id']   = 0;
		}

		$orders = wc_get_orders( $args );

		// Populate the order_count and total_spent variables with the valid orders.
		$order_count = 0;
		$total_spent = 0;
		foreach ( $orders as $order ) {
			$order_count ++;
			$total_spent += $order->get_total();

			// Remove any refunded amount from the total_spent.
			foreach ( $order->get_refunds() as $refund ) {
				$total_spent += $refund->get_total();
			}
		}

		return array(
			'order_count'   => $order_count,
			'total_spent'   => $total_spent,
			'average_spent' => $order_count ? $total_spent / $order_count : 0,
		);
	}

}
