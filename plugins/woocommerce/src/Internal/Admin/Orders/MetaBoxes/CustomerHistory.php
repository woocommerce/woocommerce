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
		// No history when adding a new order.
		if ( 'auto-draft' === $order->get_status() ) {
			return;
		}

		$customer_history = null;

		if ( method_exists( $order, 'get_report_customer_id' ) ) {
			$customer_history = $this->get_customer_history( $order->get_report_customer_id() );
		}

		if ( ! $customer_history ) {
			$customer_history = array(
				'orders_count'    => 0,
				'total_spend'     => 0,
				'avg_order_value' => 0,
			);
		}

		wc_get_template( 'order/customer-history.php', $customer_history );
	}

	/**
	 * Get the order history for the customer (data matches Customers report).
	 *
	 * @param int $customer_report_id The reports customer ID (not necessarily User ID).
	 *
	 * @return array|null Order count, total spend, and average spend per order.
	 */
	private function get_customer_history( $customer_report_id ): ?array {

		$args = array(
			'customers'    => array( $customer_report_id ),
			// If unset, these params have default values that affect the results.
			'order_after'  => null,
			'order_before' => null,
		);


		/**
		 * Filter query args given for the report.
		 *
		 * @since x.x.x
		 *
		 * @param array $args Query args.
		 */
		$args = apply_filters( "woocommerce_analytics_customers_query_args", $args );

		$data_store = \WC_Data_Store::load( 'customers' );
		$results    = $data_store->get_data( $args );

		$customer_data   = apply_filters( "woocommerce_analytics_customers_select_query", $results, $args );
		return $customer_data->data[0] ?? null;
	}

}
