<?php

namespace Automattic\WooCommerce\Internal\Admin\Orders\MetaBoxes;

use Automattic\WooCommerce\Internal\Traits\OrderAttributionMeta;
use WC_Order;

/**
 * Class CustomerHistory
 *
 * @since 8.5.0
 */
class CustomerHistory {

	use OrderAttributionMeta;

	/**
	 * Output the customer history template for the order.
	 *
	 * @param WC_Order $order The order object.
	 *
	 * @return void
	 */
	public function output( WC_Order $order ): void {
		$customer_history = $this->get_customer_history( $order->get_report_customer_id() );

		if ( ! $customer_history ) {
			$customer_history = array(
				'orders_count'    => 0,
				'total_spend'     => 0,
				'avg_order_value' => 0,
			);
		}

		wc_get_template( 'order/customer-history.php', $customer_history );
	}

}
