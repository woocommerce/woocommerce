<?php
/**
 * WooCommerce Order Tracking
 *
 * @package WooCommerce\Tracks
 */

defined( 'ABSPATH' ) || exit;

/**
 * This class adds actions to track usage of a WooCommerce Order.
 */
class WC_Order_Tracking {
	/**
	 * Init tracking.
	 */
	public function init() {
		add_action( 'woocommerce_admin_order_data_after_order_details', array( $this, 'track_order_viewed' ) );
	}

	/**
	 * Send a Tracks event when an order is viewed.
	 *
	 * @param object $order Order.
	 */
	public function track_order_viewed( $order ) {
		$properties = array(
			'order_id'         => $order->get_id(),
			'current_status'   => $order->get_status(),
			'date_created'     => $order->get_date_created(),
			'payment_method'   => $order->get_payment_method(),
		);

		WC_Tracks::record_event( 'single_order_view', $properties );
	}
}

