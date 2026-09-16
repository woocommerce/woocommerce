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
		$result = self::run_query( $args );

		return is_array( $result ) ? $result : array();
	}

	/**
	 * Count notifications matching the given filters.
	 *
	 * @param array $args Same filter args as {@see self::get_notifications()}, minus
	 *                    the `return` / `limit` / `offset` keys (those are forced to
	 *                    `count` / no-limit).
	 * @return int Number of matching notifications.
	 */
	public static function count_notifications( array $args ): int {
		$args['return'] = 'count';
		unset( $args['limit'], $args['offset'] );

		return (int) self::run_query( $args );
	}

	/**
	 * Load the stock notification data store, or null while the feature is off.
	 *
	 * The data store is only registered while the feature is enabled, so
	 * `WC_Data_Store::load()` throws when it is off. Every query in this class
	 * goes through here so all of them fail soft instead of fataling.
	 *
	 * @return \WC_Data_Store|null
	 */
	private static function load_data_store(): ?\WC_Data_Store {
		try {
			return \WC_Data_Store::load( 'stock_notification' );
		} catch ( \Exception $e ) {
			\wc_caught_exception( $e, __METHOD__ );
			return null;
		}
	}

	/**
	 * Single dispatch site to the underlying data store's `query()` method.
	 *
	 * Centralised so the `WC_Data_Store::query()` PHPStan suppression in
	 * `phpstan-baseline.neon` only needs to cover one call site.
	 *
	 * @param array $args Query args.
	 * @return mixed Whatever the data store returns for the requested `return` mode
	 *               (array of objects/ids, int for `count`), or null while the feature is off.
	 */
	private static function run_query( array $args ) {
		$data_store = self::load_data_store();

		return $data_store ? $data_store->query( $args ) : null;
	}

	/**
	 * Check if a product has active notifications.
	 *
	 * @param array<int> $product_ids The product IDs to check.
	 * @return bool True if the product has active notifications, false otherwise.
	 */
	public static function product_has_active_notifications( array $product_ids ): bool {
		$data_store = self::load_data_store();

		return $data_store ? $data_store->product_has_active_notifications( $product_ids ) : false;
	}

	/**
	 * Check if a notification exists by email.
	 *
	 * @param int    $product_id The product ID.
	 * @param string $email The email address.
	 * @return bool True if the notification exists, false otherwise.
	 */
	public static function notification_exists_by_email( int $product_id, string $email ): bool {
		$data_store = self::load_data_store();

		return $data_store ? $data_store->notification_exists_by_email( $product_id, $email ) : false;
	}

	/**
	 * Get a notification by user ID.
	 *
	 * @param int $product_id The product ID.
	 * @param int $user_id The user ID.
	 * @return bool True if the notification exists, false otherwise.
	 */
	public static function notification_exists_by_user_id( int $product_id, int $user_id ): bool {
		$data_store = self::load_data_store();

		return $data_store ? $data_store->notification_exists_by_user_id( $product_id, $user_id ) : false;
	}
}
