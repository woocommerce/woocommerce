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

	/**
	 * Get aggregated totals for all notifications.
	 *
	 * @param string $since_gmt Optional lower bound for date_created_gmt (Y-m-d H:i:s GMT). Empty = all-time.
	 * @return array{total_signups:int,active_signups:int,pending_signups:int,notifications_sent:int,cancelled:int}
	 */
	public static function get_totals( string $since_gmt = '' ): array {
		return \WC_Data_Store::load( 'stock_notification' )->get_totals( $since_gmt );
	}

	/**
	 * Per-product aggregated counts, paginated.
	 *
	 * @param int $per_page Items per page (1-100).
	 * @param int $page     1-based page number.
	 * @return array{rows:array<int,array<string,int>>,total:int}
	 */
	public static function get_per_product_summary( int $per_page = 25, int $page = 1 ): array {
		return \WC_Data_Store::load( 'stock_notification' )->get_per_product_summary( $per_page, $page );
	}

	/**
	 * Daily counts of signups and notifications_sent over a window.
	 *
	 * @param string $start_gmt Y-m-d inclusive lower bound.
	 * @param string $end_gmt   Y-m-d inclusive upper bound.
	 * @return array<int,array{date:string,signups:int,notifications_sent:int}>
	 */
	public static function get_timeseries( string $start_gmt, string $end_gmt ): array {
		return \WC_Data_Store::load( 'stock_notification' )->get_timeseries( $start_gmt, $end_gmt );
	}

	/**
	 * Top-demand products by active signups.
	 *
	 * @param int $limit Maximum rows to return (1-50).
	 * @return array<int,array{product_id:int,active_signups:int,total_signups:int}>
	 */
	public static function get_top_demand( int $limit = 10 ): array {
		return \WC_Data_Store::load( 'stock_notification' )->get_top_demand( $limit );
	}

	/**
	 * Most recently dispatched notifications.
	 *
	 * @param int $limit Maximum rows to return (1-50).
	 * @return array<int,array{id:int,product_id:int,user_email:string,date_notified_gmt:string}>
	 */
	public static function get_recent_activity( int $limit = 10 ): array {
		return \WC_Data_Store::load( 'stock_notification' )->get_recent_activity( $limit );
	}
}
