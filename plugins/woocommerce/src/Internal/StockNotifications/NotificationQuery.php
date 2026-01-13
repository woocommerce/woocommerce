<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\StockNotifications;

/**
 * Notification query class.
 */
class NotificationQuery {

	/**
	 * Get notifications.
	 *
	 * @param array $args The arguments to pass to the query.
	 * @return array The notifications.
	 */
	public static function get_notifications( array $args ): array {
		return \WC_Data_Store::load( 'stock_notification' )->query( $args );
	}

	/**
	 * Check if a product has active notifications.
	 *
	 * @param array<int> $product_ids The product IDs to check.
	 * @return bool True if the product has active notifications, false otherwise.
	 */
	public static function product_has_active_notifications( array $product_ids ): bool {
		return \WC_Data_Store::load( 'stock_notification' )->product_has_active_notifications( $product_ids );
	}

	/**
	 * Check if a notification exists by email.
	 *
	 * @param int    $product_id The product ID.
	 * @param string $email The email address.
	 * @return bool True if the notification exists, false otherwise.
	 */
	public static function notification_exists_by_email( int $product_id, string $email ): bool {
		return \WC_Data_Store::load( 'stock_notification' )->notification_exists_by_email( $product_id, $email );
	}

	/**
	 * Get a notification by user ID.
	 *
	 * @param int $product_id The product ID.
	 * @param int $user_id The user ID.
	 * @return bool True if the notification exists, false otherwise.
	 */
	public static function notification_exists_by_user_id( int $product_id, int $user_id ): bool {
		return \WC_Data_Store::load( 'stock_notification' )->notification_exists_by_user_id( $product_id, $user_id );
	}
}
