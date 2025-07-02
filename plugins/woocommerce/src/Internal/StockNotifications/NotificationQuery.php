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
}
