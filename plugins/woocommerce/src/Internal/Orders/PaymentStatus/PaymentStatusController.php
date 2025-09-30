<?php
/**
 * Payment Status Manager
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Orders\PaymentStatus;

use Automattic\WooCommerce\Admin\Features\Features;
use WC_Order;

/**
 * PaymentStatusController
 *
 * This class is responsible for keeping order payment status in sync with order status so it can be looked up via the REST API.
 *
 * @since 10.1.0
 * @package WooCommerce\Internal\Orders
 */
class PaymentStatusController {
	/**
	 * Initialize the controller.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'init', array( $this, 'initialize_payment_status' ), 10, 0 );
	}

	/**
	 * Initialize the payment status controller.
	 */
	public function initialize_payment_status() {
		// Payment statuses are enabled and synced only when REST API v4 is enabled.
		if ( ! Features::is_enabled( 'rest-api-v4' ) ) {
			return;
		}
		$this->init_hooks();
	}

	/**
	 * Initialize the hooks.
	 */
	private function init_hooks() {
		// Sync when order status is changed.
		add_action(
			'woocommerce_order_status_changed',
			function ( $order_id, $from, $to, $order ) {
				if ( $order instanceof WC_Order ) {
					$payment_status = PaymentStatusUtils::get_synced_payment_status_for_order( $order );
					if ( $payment_status !== $order->get_meta( '_payment_status' ) ) {
						$order->update_meta_data( '_payment_status', $payment_status );
						$order->save();
					}
				}
			},
			10,
			4
		);

		// Sunc when partial refund is created.
		add_action(
			'woocommerce_order_partially_refunded',
			function ( $order_id ) {
				$order = wc_get_order( $order_id );
				if ( $order instanceof WC_Order ) {
					$payment_status = PaymentStatusUtils::get_synced_payment_status_for_order( $order );
					if ( $payment_status !== $order->get_meta( '_payment_status' ) ) {
						$order->update_meta_data( '_payment_status', $payment_status );
						$order->save();
					}
				}
			}
		);
	}
}
