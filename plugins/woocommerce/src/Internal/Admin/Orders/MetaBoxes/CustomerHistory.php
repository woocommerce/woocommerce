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

}
