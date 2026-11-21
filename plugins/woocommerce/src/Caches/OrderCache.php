<?php

namespace Automattic\WooCommerce\Caches;

use Automattic\WooCommerce\Caching\ObjectCache;
use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;
use Automattic\WooCommerce\Internal\Utilities\DatabaseTransaction;

/**
 * A class to cache order objects.
 */
class OrderCache extends ObjectCache {

	/**
	 * Sites deferring the order transient version, and whether a refresh was requested.
	 *
	 * @var array<int, bool>
	 */
	private static $deferred_transient_refresh = array();

	/**
	 * Refresh the shared version used by order transients.
	 *
	 * @internal
	 * @since 11.4.0
	 */
	public static function refresh_transient_version(): void {
		$site_id = get_current_blog_id();
		if ( isset( self::$deferred_transient_refresh[ $site_id ] ) ) {
			self::$deferred_transient_refresh[ $site_id ] = true;
			return;
		}
		\WC_Cache_Helper::get_transient_version( 'orders', true );
	}

	/**
	 * Defer the shared version update until an order save commits.
	 *
	 * Leave the timing alone if another caller owns the transaction.
	 *
	 * @internal
	 * @since 11.4.0
	 * @param callable $save Owns the transaction and throws if the save did not commit.
	 */
	public static function with_deferred_transient_refresh( callable $save ): void {
		$site_id = get_current_blog_id();
		if ( isset( self::$deferred_transient_refresh[ $site_id ] ) || DatabaseTransaction::has_active_transaction() ) {
			$save();
			return;
		}

		self::$deferred_transient_refresh[ $site_id ] = false;
		try {
			$save();
		} finally {
			$refresh = self::take_deferred_transient_refresh( $site_id );
		}

		if ( $refresh ) {
			self::refresh_transient_version();
		}
	}

	/**
	 * Clear the site's deferral and read whether the save requested a refresh.
	 *
	 * @param int $site_id Site whose order was saved.
	 * @return bool Whether to refresh the version after a successful save.
	 */
	private static function take_deferred_transient_refresh( int $site_id ): bool {
		$refresh = self::$deferred_transient_refresh[ $site_id ];
		unset( self::$deferred_transient_refresh[ $site_id ] );
		return $refresh;
	}

	/**
	 * Get the cache key and prefix to use for Orders.
	 *
	 * @return string
	 */
	public function get_object_type(): string {
		if ( 'yes' === get_option( CustomOrdersTableController::HPOS_DATASTORE_CACHING_ENABLED_OPTION ) ) {
			/**
			 * The use of datastore caching moves persistent data caching to the datastore. Order object caching then only
			 * acts as request level caching as the `order_objects` cache group is set as non-persistent.
			 */
			return 'order_objects';
		} else {
			return 'orders';
		}
	}

	/**
	 * Get the id of an object to be cached.
	 *
	 * @param array|object $object The object to be cached.
	 * @return int|string|null The id of the object, or null if it can't be determined.
	 */
	protected function get_object_id( $object ) {
		return $object->get_id();
	}

	/**
	 * Validate an object before caching it.
	 *
	 * @param array|object $object The object to validate.
	 * @return string[]|null An array of error messages, or null if the object is valid.
	 */
	protected function validate( $object ): ?array {
		if ( ! $object instanceof \WC_Abstract_Order ) {
			return array( 'The supplied order is not an instance of WC_Abstract_Order, ' . gettype( $object ) );
		}

		return null;
	}
}
