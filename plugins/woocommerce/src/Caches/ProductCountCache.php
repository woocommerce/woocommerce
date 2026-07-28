<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Caches;

/**
 * A class to cache counts for various product statuses.
 */
class ProductCountCache {

	/**
	 * Cache prefix.
	 *
	 * @var string
	 */
	private string $cache_prefix = 'product-count';

	/**
	 * Default value for the duration of the objects in the cache, in seconds
	 * (may not be used depending on the cache engine used WordPress cache implementation).
	 *
	 * @var int
	 */
	protected int $expiration = DAY_IN_SECONDS;

	/**
	 * Retrieves the list of known statuses by product type. A cached array of statuses is saved per product type for
	 * improved backward compatibility with some of the extensions that don't register all statuses they use with
	 * WooCommerce.
	 *
	 * @param string $product_type The post type (e.g. 'product', 'product_variation').
	 *
	 * @return string[]
	 */
	private function get_saved_statuses_for_type( string $product_type ): array {
		$statuses = wp_cache_get( $this->get_saved_statuses_cache_key( $product_type ) );

		return is_array( $statuses ) ? $statuses : array();
	}

	/**
	 * Adds the given statuses to the cached statuses array for the product type if they are not already stored.
	 *
	 * @param string   $product_type     The post type (e.g. 'product', 'product_variation').
	 * @param string[] $product_statuses One or more statuses to add.
	 *
	 * @return void
	 */
	private function ensure_statuses_for_type( string $product_type, array $product_statuses ): void {
		if ( empty( $product_statuses ) ) {
			return;
		}

		$existing     = $this->get_saved_statuses_for_type( $product_type );
		$new_statuses = array_diff( $product_statuses, $existing );
		if ( empty( $new_statuses ) ) {
			return;
		}

		$merged = array_unique( array_merge( $existing, $new_statuses ) );
		wp_cache_set( $this->get_saved_statuses_cache_key( $product_type ), $merged, '', $this->expiration );
	}

	/**
	 * Get the cache key for a given product type and status.
	 *
	 * @param string $product_type   The post type (e.g. 'product', 'product_variation').
	 * @param string $product_status The status of the product.
	 *
	 * @return string
	 */
	private function get_cache_key( string $product_type, string $product_status ): string {
		return $this->cache_prefix . '_' . $product_type . '_' . $product_status;
	}

	/**
	 * Get the cache key for saved statuses of the given product type.
	 *
	 * @param string $product_type The post type (e.g. 'product', 'product_variation').
	 *
	 * @return string
	 */
	private function get_saved_statuses_cache_key( string $product_type ): string {
		return $this->cache_prefix . '_' . $product_type . '_statuses';
	}

	/**
	 * Check if the cache has a value for a given product type and status.
	 *
	 * @param string $product_type   The post type (e.g. 'product', 'product_variation').
	 * @param string $product_status The status of the product.
	 *
	 * @return bool
	 */
	public function is_cached( string $product_type, string $product_status ): bool {
		return false !== wp_cache_get( $this->get_cache_key( $product_type, $product_status ) );
	}

	/**
	 * Set the cache value for a given product type and status.
	 *
	 * @param string $product_type   The post type (e.g. 'product', 'product_variation').
	 * @param string $product_status The status slug of the product.
	 * @param int    $value          The value to set.
	 *
	 * @return bool
	 */
	public function set( string $product_type, string $product_status, int $value ): bool {
		$this->ensure_statuses_for_type( $product_type, array( $product_status ) );
		return wp_cache_set( $this->get_cache_key( $product_type, $product_status ), $value, '', $this->expiration );
	}

	/**
	 * Set the cache count value for multiple statuses at once.
	 *
	 * @param string            $product_type The post type (e.g. 'product', 'product_variation').
	 * @param array<string,int> $counts       Counts keyed by status slug (e.g. [ 'publish' => 10, 'draft' => 5 ]).
	 *
	 * @return array<string,bool>
	 */
	public function set_multiple( string $product_type, array $counts ) {
		if ( empty( $counts ) ) {
			return array();
		}

		$this->ensure_statuses_for_type( $product_type, array_keys( $counts ) );

		$mapped_counts = array();
		foreach ( $counts as $status => $count ) {
			$mapped_counts[ $this->get_cache_key( $product_type, $status ) ] = (int) $count;
		}

		return wp_cache_set_multiple( $mapped_counts, '', $this->expiration );
	}

	/**
	 * Get the cache value for a given product type and set of statuses.
	 *
	 * @param string   $product_type     The post type (e.g. 'product', 'product_variation').
	 * @param string[] $product_statuses The statuses to retrieve.
	 *
	 * @return array<string,int>|null
	 */
	public function get( string $product_type, array $product_statuses = array() ) {
		if ( empty( $product_statuses ) ) {
			$product_statuses = $this->get_saved_statuses_for_type( $product_type );
			if ( empty( $product_statuses ) ) {
				return null;
			}
		}

		$cache_keys = array_map(
			fn( $product_status ) => $this->get_cache_key( $product_type, $product_status ),
			$product_statuses
		);

		$cache_values  = wp_cache_get_multiple( $cache_keys );
		$status_values = array();

		$cache_key_prefix = $this->get_cache_key( $product_type, '' );
		foreach ( $cache_values as $key => $value ) {
			// Return null for the entire cache if any of the requested statuses are not found because they fell out of cache.
			if ( false === $value ) {
				return null;
			}

			$status                   = substr( $key, strlen( $cache_key_prefix ) );
			$status_values[ $status ] = $value;
		}

		return $status_values;
	}

	/**
	 * Increment the cache value for a given product type and status.
	 *
	 * @param string $product_type   The post type (e.g. 'product', 'product_variation').
	 * @param string $product_status The status of the product.
	 * @param int    $offset         The amount to increment by.
	 *
	 * @return int|false
	 */
	public function increment( string $product_type, string $product_status, int $offset = 1 ) {
		return wp_cache_incr( $this->get_cache_key( $product_type, $product_status ), $offset );
	}

	/**
	 * Decrement the cache value for a given product type and status.
	 *
	 * @param string $product_type   The post type (e.g. 'product', 'product_variation').
	 * @param string $product_status The status of the product.
	 * @param int    $offset         The amount to decrement by.
	 *
	 * @return int|false
	 */
	public function decrement( string $product_type, string $product_status, int $offset = 1 ) {
		return wp_cache_decr( $this->get_cache_key( $product_type, $product_status ), $offset );
	}

	/**
	 * Flush the cache for a given product type and statuses.
	 *
	 * @param string   $product_type     The post type (e.g. 'product', 'product_variation').
	 * @param string[] $product_statuses The statuses to flush. Flushes all known statuses if empty.
	 *
	 * @return void
	 */
	public function flush( string $product_type = 'product', array $product_statuses = array() ): void {
		$flush_saved_statuses = false;
		if ( empty( $product_statuses ) ) {
			$product_statuses     = $this->get_saved_statuses_for_type( $product_type );
			$flush_saved_statuses = true;
		}

		$cache_keys = array_map(
			fn ( $product_status ) => $this->get_cache_key( $product_type, $product_status ),
			$product_statuses
		);

		if ( $flush_saved_statuses ) {
			// If all statuses are being flushed, go ahead and flush the status list so any permanently removed statuses are cleared out.
			$cache_keys[] = $this->get_saved_statuses_cache_key( $product_type );
		}

		wp_cache_delete_multiple( $cache_keys );
	}
}
