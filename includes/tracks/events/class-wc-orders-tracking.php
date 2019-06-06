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
	public function init() {
		add_action( 'woocommerce_order_status_changed', array( $this, 'track_order_status_change' ), 10, 3 );
		add_action( 'load-edit.php', array( $this, 'track_orders_view' ), 10 );
		add_action( 'pre_post_update', array( $this, 'track_created_date_change' ), 10 );
	}

	/**
	 * Send a Tracks event when the Orders page is viewed.
	 */
	public function track_orders_view() {
		if ( isset( $_GET['post_type'] ) && 'shop_order' === wp_unslash( $_GET['post_type'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.NoNonceVerification, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

			// phpcs:disable WordPress.Security.NonceVerification.NoNonceVerification, WordPress.Security.ValidatedSanitizedInput
			$properties = array(
				'status' => isset( $_GET['post_status'] ) ? sanitize_text_field( $_GET['post_status'] ) : 'all',
			);
			// phpcs:enable

			WC_Tracks::record_event( 'orders_view', $properties );
		}
	}

	/**
	 * Send a Tracks event when an order status is changed.
	 *
	 * @param int    $id Order id.
	 * @param string $previous_status the old WooCommerce order status.
	 * @param string $next_status the new WooCommerce order status.
	 */
	public function track_order_status_change( $id, $previous_status, $next_status ) {
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

	/**
	 * Send a Tracks event when an order date is changed.
	 *
	 * @param int $id Order id.
	 */
	public function track_created_date_change( $id ) {
		$post_type = get_post_type( $id );

		if ( 'shop_order' !== $post_type ) {
			return;
		}

		$order        = wc_get_order( $id );
		$date_created = $order->get_date_created()->date( 'Y-m-d H:i:s' );
		// phpcs:disable WordPress.Security.NonceVerification.NoNonceVerification
		$new_date     = sprintf(
			'%s %2d:%2d:%2d',
			isset( $_POST['order_date'] ) ? wc_clean( wp_unslash( $_POST['order_date'] ) ) : '',
			isset( $_POST['order_date_hour'] ) ? wc_clean( wp_unslash( $_POST['order_date_hour'] ) ) : '',
			isset( $_POST['order_date_minute'] ) ? wc_clean( wp_unslash( $_POST['order_date_minute'] ) ) : '',
			isset( $_POST['order_date_second'] ) ? wc_clean( wp_unslash( $_POST['order_date_second'] ) ) : ''
		);
		// phpcs:enable

		if ( $new_date !== $date_created ) {
			$properties = array(
				'order_id'   => $id,
				'status'     => $order->get_status(),
			);

			WC_Tracks::record_event( 'order_edit_date_created', $properties );
		}
	}
}
