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
		return self::run_query( $args );
	}

	/**
	 * Count notifications matching the given filters.
	 *
	 * Wraps the data store's count-mode query with a strict int return so callers
	 * that only need a row count don't need to load and count an array of rows.
	 *
	 * @param array $args Filter args (same shape as get_notifications, minus `return`).
	 * @return int Matching row count.
	 */
	public static function count_notifications( array $args = array() ): int {
		$args['return'] = 'count';
		return (int) self::run_query( $args );
	}

	/**
	 * Delegate to the stock_notification data store's query method.
	 *
	 * @param array $args The query arguments.
	 * @return mixed Rows or row count, depending on $args['return'].
	 */
	private static function run_query( array $args ) {
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
