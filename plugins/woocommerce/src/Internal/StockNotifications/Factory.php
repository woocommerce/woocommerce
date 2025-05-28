<?php
/**
 * Notification Factory
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\StockNotifications;

use Automattic\WooCommerce\Internal\StockNotifications\Notification;

defined( 'ABSPATH' ) || exit;

/**
 * Notification factory class
 */
class Factory {

	/**
	 * Get the notification object.
	 *
	 * @param  int $notification_id Notification ID to get.
	 * @return Notification|bool
	 */
	public static function get_notification( int $notification_id ) {

		if ( ! $notification_id ) {
			return false;
		}

		try {
			$notification = new Notification( $notification_id );
			return $notification;
		} catch ( \Exception $e ) {
			\wc_caught_exception( $e, __FUNCTION__, array( $notification_id ) );
			return false;
		}
	}

	/**
	 * Create a dummy notification for preview/testing purposes.
	 *
	 * @return Notification
	 */
	public static function create_dummy_notification(): Notification {
		$notification = new Notification();

		// Create a dummy product.
		$product = new \WC_Product();
		$product->set_name( __( 'Dummy Product', 'woocommerce' ) );
		$product->set_price( 25 );
		$product->set_image_id( get_option( 'woocommerce_placeholder_image', 0 ) );

		// Set required notification data.
		$notification->set_product_id( $product->get_id() );
		$notification->set_user_email( 'preview@example.com' );

		// Store the dummy product in the notification object for preview.
		$notification->product = $product;

		return $notification;
	}
}
