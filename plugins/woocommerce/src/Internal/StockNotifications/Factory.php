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
}
