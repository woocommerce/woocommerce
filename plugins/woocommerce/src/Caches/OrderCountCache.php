<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Caches;

use Automattic\WooCommerce\Caching\ObjectCache;
use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableDataStore;
use Automattic\WooCommerce\Utilities\OrderUtil;

/**
 * A class to cache counts for various order statuses.
 */
class OrderCountCache {

	/**
	 * Cache prefix.
	 *
	 * @var string
	 */
	private $cache_prefix = 'order-count';

	/**
	 * Default value for the duration of the objects in the cache, in seconds
	 * (may not be used depending on the cache engine used WordPress cache implementation).
	 *
	 * @var int
	 */
	protected $expiration = DAY_IN_SECONDS;

	/**
	 * Retrieves the list of known statuses by order type. A cached array of statuses is saved per order type for
	 * improved backward compatibility with some of the extensions that don't register all statuses they use with
	 * WooCommerce.
	 *
	 * @param string $order_type The type of order.
	 *
	 * @return string[]
	 */
	private function get_saved_statuses_for_type( string $order_type ) {
		$statuses = wp_cache_get( $this->get_saved_statuses_cache_key( $order_type ) );
		if ( ! is_array( $statuses ) ) {
			$statuses = array();
		}

		return $statuses;
	}

	/**
	 * Adds the given statuses to the cached statuses array for the order type if they are not already stored.
	 *
	 * @param string   $order_type     The order type to save with.
	 * @param string[] $order_statuses One or more normalised statuses to add.
	 *
	 * @return void
	 */
	private function ensure_statuses_for_type( string $order_type, array $order_statuses ) {
		if ( empty( $order_statuses ) ) {
			return;
		}

		$existing     = $this->get_saved_statuses_for_type( $order_type );
		$new_statuses = array_diff( $order_statuses, $existing );
		if ( empty( $new_statuses ) ) {
			return;
		}
		$merged = array_unique( array_merge( $existing, $new_statuses ) );

		wp_cache_set( $this->get_saved_statuses_cache_key( $order_type ), $merged, '', $this->expiration );
	}

	/**
	 * Get the default statuses.
	 *
	 * @return string[]
	 *
	 * @deprecated 10.1.0 This method will be removed in the future.
	 */
	public function get_default_statuses() {
		return array_merge(
			array_keys( wc_get_order_statuses() ),
			array( OrderStatus::TRASH )
		);
	}

	/**
	 * Get the cache key for a given order type and status.
	 *
	 * @param string $order_type The type of order.
	 * @param string $order_status The status of the order.
	 * @return string The cache key.
	 */
	private function get_cache_key( $order_type, $order_status ) {
		return $this->cache_prefix . '_' . $order_type . '_' . $order_status;
	}

	/**
	 * Get the cache key saved statuses of the given order type.
	 *
	 * @param string $order_type The type of order.
	 *
	 * @return string The cache key.
	 */
	private function get_saved_statuses_cache_key( string $order_type ) {
		return $this->cache_prefix . '_' . $order_type . '_statuses';
	}

	/**
	 * Check if the cache has a value for a given order type and status.
	 *
	 * @param string $order_type The type of order.
	 * @param string $order_status The status of the order.
	 * @return bool True if the cache has a value, false otherwise.
	 */
	public function is_cached( $order_type, $order_status ) {
		$cache_key = $this->get_cache_key( $order_type, $order_status );
		return wp_cache_get( $cache_key ) !== false;
	}

	/**
	 * Set the cache value for a given order type and status.
	 *
	 * @param string $order_type The type of order.
	 * @param string $order_status The status slug of the order.
	 * @param int $value The value to set.
	 * @return bool True if the value was set, false otherwise.
	 */
	public function set( $order_type, $order_status, int $value ): bool {
		$this->ensure_statuses_for_type( (string) $order_type, array( (string) $order_status ) );
		$cache_key = $this->get_cache_key( $order_type, $order_status );
		return wp_cache_set( $cache_key, $value, '', $this->expiration );
	}


	/**
	 * Set the cache count value for multiple statuses at once.
	 *
	 * @param string $order_type The order type being set.
	 * @param array  $counts     Normalized counts keyed by status slug
	 *                           (e.g. [ 'wc-processing' => 10, 'wc-pending' => 5 ]).
	 *
	 * @return array|bool[]      Success map from wp_cache_set_multiple().
	 */
	public function set_multiple( string $order_type, array $counts ) {
		if ( empty( $counts ) ) {
			return array();
		}

		$this->ensure_statuses_for_type( $order_type, array_keys( $counts ) );

		$mapped_counts = array();
		foreach ( $counts as $status => $count ) {
			$mapped_counts[ $this->get_cache_key( $order_type, $status ) ] = (int) $count;
		}

		return wp_cache_set_multiple( $mapped_counts, '', $this->expiration );
	}


	/**
	 * Get the cache value for a given order type and set of statuses.
	 *
	 * @param string $order_type The type of order.
	 * @param string[] $order_statuses The statuses of the order.
	 * @return int[] The cache value.
	 */
	public function get( $order_type, $order_statuses = array() ) {
		$order_type = (string) $order_type;
		if ( empty( $order_statuses ) ) {
			$order_statuses = $this->get_saved_statuses_for_type( $order_type );
			if ( empty( $order_statuses ) ) {
				return null;
			}
		}

		$cache_keys = array_map( function( $order_statuses ) use ( $order_type ) {
			return $this->get_cache_key( $order_type, $order_statuses );
		}, $order_statuses );

		$cache_values  = wp_cache_get_multiple( $cache_keys );
		$status_values = array();

		foreach ( $cache_values as $key => $value ) {
			// Return null for the entire cache if any of the requested statuses are not found because they fell out of cache.
			if ( $value === false ) {
				return null;
			}

			$order_status                   = str_replace( $this->get_cache_key( $order_type, '' ), '', $key );
			$status_values[ $order_status ] = $value;
		}

		return $status_values;
	}

	/**
	 * Increment the cache value for a given order status.
	 *
	 * @param string $order_type The type of order.
	 * @param string $order_status The status of the order.
	 * @param int $offset The amount to increment by.
	 * @return int The new value of the cache.
	 */
	public function increment( $order_type, $order_status, $offset = 1 ) {
		$cache_key = $this->get_cache_key( $order_type, $order_status );
		return wp_cache_incr( $cache_key, $offset );
	}

	/**
	 * Decrement the cache value for a given order status.
	 *
	 * @param string $order_type The type of order.
	 * @param string $order_status The status of the order.
	 * @param int $offset The amount to decrement by.
	 * @return int The new value of the cache.
	 */
	public function decrement( $order_type, $order_status, $offset = 1 ) {
		$cache_key = $this->get_cache_key( $order_type, $order_status );
		return wp_cache_decr( $cache_key, $offset );
	}

	/**
	 * Flush the cache for a given order type and statuses.
	 *
	 * @param string $order_type The type of order.
	 * @param string[] $order_statuses The statuses of the order.
	 * @return void
	 */
	public function flush( $order_type = 'shop_order', $order_statuses = array() ) {
		$order_type           = (string) $order_type;
		$flush_saved_statuses = false;
		if ( empty( $order_statuses ) ) {
			$order_statuses       = $this->get_saved_statuses_for_type( $order_type );
			$flush_saved_statuses = true;
		}

		$cache_keys = array_map( function( $order_statuses ) use ( $order_type ) {
			return $this->get_cache_key( $order_type, $order_statuses );
		}, $order_statuses );

		if ( $flush_saved_statuses ) {
			// If all statuses are being flushed, go ahead and flush the status list so any permanently removed statuses are cleared out.
			$cache_keys[] = $this->get_saved_statuses_cache_key( $order_type );
		}

		wp_cache_delete_multiple( $cache_keys );
	}
}
