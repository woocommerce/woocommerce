<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Caches;

use Automattic\WooCommerce\Caching\CacheNameSpaceTrait;

/**
 * Stores request-scoped values in non-persistent WordPress cache groups.
 *
 * @since 11.1.0
 *
 * @internal
 */
final class RequestCache {

	use CacheNameSpaceTrait;

	/**
	 * Cache groups registered as non-persistent during this request.
	 *
	 * @since 11.1.0
	 *
	 * @var array<string, true>
	 */
	private array $registered_groups = array();

	/**
	 * Get a request-cached value.
	 *
	 * @since 11.1.0
	 *
	 * @param string    $key   Cache key.
	 * @param string    $group Cache group.
	 * @param bool|null $found Whether the key was found.
	 *
	 * @return mixed The cached value, or false on a miss.
	 */
	public function get( string $key, string $group, ?bool &$found = null ) {
		$this->register_group( $group );

		$found = false;
		return wp_cache_get( self::get_prefixed_key( $key, $group ), $group, false, $found );
	}

	/**
	 * Store a request-cached value.
	 *
	 * @since 11.1.0
	 *
	 * @param string $key   Cache key.
	 * @param mixed  $value Value to cache.
	 * @param string $group Cache group.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function set( string $key, $value, string $group ): bool {
		$this->register_group( $group );

		return wp_cache_set( self::get_prefixed_key( $key, $group ), $value, $group );
	}

	/**
	 * Return a cached value or resolve and store it on a miss.
	 *
	 * @since 11.1.0
	 *
	 * @template T
	 * @param string       $key      Cache key.
	 * @param string       $group    Cache group.
	 * @param callable():T $resolver Value resolver.
	 *
	 * @return T The cached or resolved value.
	 */
	public function remember( string $key, string $group, callable $resolver ) {
		$found = false;
		$value = $this->get( $key, $group, $found );
		if ( $found ) {
			return $value;
		}

		$value = $resolver();
		$this->set( $key, $value, $group );

		return $value;
	}

	/**
	 * Delete one request-cached value.
	 *
	 * @since 11.1.0
	 *
	 * @param string $key   Cache key.
	 * @param string $group Cache group.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function delete( string $key, string $group ): bool {
		$this->register_group( $group );

		return wp_cache_delete( self::get_prefixed_key( $key, $group ), $group );
	}

	/**
	 * Clear every request-cached value in a group.
	 *
	 * @since 11.1.0
	 *
	 * @param string $group Cache group.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function clear_group( string $group ): bool {
		$this->register_group( $group );

		return self::invalidate_cache_group( $group );
	}

	/**
	 * Register a cache group as non-persistent once during the request.
	 *
	 * @since 11.1.0
	 *
	 * @param string $group Cache group.
	 *
	 * @return void
	 */
	private function register_group( string $group ): void {
		if ( isset( $this->registered_groups[ $group ] ) ) {
			return;
		}

		wp_cache_add_non_persistent_groups( array( $group ) );
		$this->registered_groups[ $group ] = true;
	}
}
