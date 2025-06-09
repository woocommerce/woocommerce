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
	 * Default value for the duration of the objects in the cache, in seconds
	 * (may not be used depending on the cache engine used WordPress cache implementation).
	 *
	 * @var int
	 */
	protected $expiration = DAY_IN_SECONDS;

	/**
	 * Cache prefix.
	 *
	 * @var string
	 */
	private $cache_prefix = 'order-count';

	/**
	 * Get valid statuses.
	 *
	 * @return string[]
	 */
	private function get_valid_statuses() {
		return array_merge(
			array_keys( wc_get_order_statuses() ),
			array_keys( get_post_stati() ),
		);
	}

	/**
	 * Get the default statuses.
	 *
	 * @return string[]
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
	 * @param string $order_status The status of the order.
	 * @param int $value The value to set.
	 * @return bool True if the value was set, false otherwise.
	 */
	public function set( $order_type, $order_status, int $value ): bool {
		$cache_key = $this->get_cache_key( $order_type, $order_status );
		return wp_cache_set( $cache_key, $value, '', $this->expiration );
	}

	/**
	 * Get the cache value for a given order type and status.
	 *
	 * @param string $order_type The type of order.
	 * @param string[] $order_statuses The statuses of the order.
	 * @return int[] The cache value.
	 */
	public function get( $order_type, $order_statuses = array() ) {
		if ( empty( $order_statuses ) ) {
			$order_statuses = $this->get_default_statuses();
		}

		$cache_keys = array_map( function( $order_statuses ) use ( $order_type ) {
			return $this->get_cache_key( $order_type, $order_statuses );
		}, $order_statuses );

		$cache_values  = wp_cache_get_multiple( $cache_keys );
		$status_values = array();

		foreach ( $cache_values as $key => $value ) {
			// Return null for the entire cache if any of the requested statuses are not found.
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
		if ( empty( $order_statuses ) ) {
			$order_statuses = $this->get_default_statuses();
		}

		$cache_keys = array_map( function( $order_statuses ) use ( $order_type ) {
			return $this->get_cache_key( $order_type, $order_statuses );
		}, $order_statuses );

		wp_cache_delete_multiple( $cache_keys );
	}
}
