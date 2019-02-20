<?php
/**
 * WooCommerce Orders Tracking
 *
 * @package WooCommerce\Tracks
 */

defined( 'ABSPATH' ) || exit;

/**
 * This class adds actions to track usage of WooCommerce Orders.
 */
class WC_Orders_Tracking {
	/**
	 * Init tracking.
	 */
	public static function init() {
		add_action( 'woocommerce_order_status_changed', array( __CLASS__, 'track_order_status_change' ), 10, 3 );
		add_action( 'load-edit.php', array( __CLASS__, 'track_orders_view' ), 10 );
	}

	/**
	 * Send a Tracks event when the Orders page is viewed.
	 */
	public static function track_orders_view() {
		if ( isset( $_GET['post_type'] ) && 'shop_order' === sanitize_text_field( wp_unslash( $_GET['post_type'] ) ) ) {
			WC_Tracks::record_event( 'orders_view' );
		}
	}

	/**
	 * Send a Tracks event when an order status is changed.
	 *
	 * @param int    $id Order id.
	 * @param string $previous_status the old WooCommerce order status.
	 * @param string $next_status the new WooCommerce order status.
	 */
	public static function track_order_status_change( $id, $previous_status, $next_status ) {
		$order = wc_get_order( $id );
		$date  = $order->get_date_created();

		$properties = array(
			'order_id'        => $id,
			'next_status'     => $next_status,
			'previous_status' => $previous_status,
			'date_created'    => $date->date( 'Y-m-d' ),
		);

		WC_Tracks::record_event( 'orders_edit_status_change', $properties );
	}
}
