<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\Caches;

use Automattic\WooCommerce\Proxies\LegacyProxy;

/**
 * Entity version keys cache class.
 *
 * Provides a generic mechanism for caching version keys of mutable entities.
 * An "entity" is any item (object, array of data...) that can be uniquely
 * identified by a type and an ID, and whose data can change over time.
 * A "version key" is a unique identifier (UUID) that changes whenever the
 * entity data is modified, allowing efficient cache invalidation.
 */
class EntityVersionKeysCache {

	/**
	 * Should the entity version keys cache be used?
	 *
	 * @var bool|null
	 */
	private ?bool $should_use = null;

	/**
	 * Legacy proxy instance.
	 *
	 * @var LegacyProxy|null
	 */
	private ?LegacyProxy $legacy_proxy = null;

	/**
	 * Initialize the class dependencies.
	 *
	 * @internal
	 *
	 * @param LegacyProxy $legacy_proxy Legacy proxy instance.
	 */
	final public function init( LegacyProxy $legacy_proxy ) {
		$this->legacy_proxy = $legacy_proxy;
	}

	/**
	 * Tells whether the entity version keys cache should be used or not.
	 *
	 * By default this will return true only if an external object cache is configured in WordPress,
	 * since otherwise the cached entries will only persist for the current session.
	 *
	 * @return bool
	 */
	public function should_use(): bool {
		if ( ! is_null( $this->should_use ) ) {
			return $this->should_use;
		}

		$default_value = is_null( $this->legacy_proxy ) ? false : ( $this->legacy_proxy->call_function( 'wp_using_ext_object_cache' ) ?? false );

		/**
		 * Filter whether to use the entity version keys cache.
		 * By default returns true only if an external object cache is configured in WordPress.
		 *
		 * To use a different storing mechanism for the stored version keys
		 * (like e.g. transients) for testing purposes (not recommended for production):
		 *
		 * 1. Hook on this filter to return true.
		 * 2. Hook on the woocommerce_pre_entity_version_keys_cache_get/set/delete filters,
		 *    where you handle the storage and return a non-null value.
		 *
		 * @since 10.4.0
		 *
		 * @param bool   $should_use_cache Whether to use the cache. Default is the result of wp_using_ext_object_cache().
		 * @return bool True to use the cache, false otherwise.
		 */
		$this->should_use = apply_filters(
			'woocommerce_should_use_entity_version_keys_cache',
			$default_value
		);

		return $this->should_use;
	}

	/**
	 * Get the current version key of an entity.
	 *
	 * @param string     $entity_type Entity type.
	 * @param string|int $entity_id Entity ID.
	 * @return string Entity version key.
	 * @throws \InvalidArgumentException If entity_type or entity_id are invalid.
	 */
	public function get_entity_version( string $entity_type, $entity_id ): string {
		$this->validate_input( $entity_type, $entity_id );

		$cache_key = "wc_entity_version_key_{$entity_type}_{$entity_id}";
		$version   = $this->get_cached( $cache_key );
		if ( is_null( $version ) ) {
			$version = $this->regenerate_entity_version( $entity_type, $entity_id );
		} else {
			// Refresh the cache lifetime.
			$this->store_entity_version( $entity_type, $entity_id, $version );
		}
		return $version;
	}

	/**
	 * Regenerate and store a new version key for an entity.
	 *
	 * @param string     $entity_type Entity type.
	 * @param string|int $entity_id   Entity ID.
	 * @return string The new entity version key.
	 * @throws \InvalidArgumentException If entity_type or entity_id are invalid.
	 */
	public function regenerate_entity_version( string $entity_type, $entity_id ): string {
		$this->validate_input( $entity_type, $entity_id );

		$version = wp_generate_uuid4();
		$this->store_entity_version( $entity_type, $entity_id, $version );
		return $version;
	}

	/**
	 * Store the entity version key in cache with a filterable TTL.
	 *
	 * @param string     $entity_type Entity type.
	 * @param string|int $entity_id   Entity ID.
	 * @param string     $version     The version key to store.
	 * @return bool True on success, false on failure.
	 */
	protected function store_entity_version( string $entity_type, $entity_id, string $version ): bool {
		$cache_key = "wc_entity_version_key_{$entity_type}_{$entity_id}";

		/**
		 * Filter the TTL for entity version key cache.
		 *
		 * @param int        $ttl         Time to live in seconds. Default 1 day.
		 * @param string     $entity_type The type of the entity.
		 * @param string|int $entity_id   The ID of the entity.
		 *
		 * @since 10.4.0
		 */
		$ttl = apply_filters( 'woocommerce_entity_version_key_ttl', DAY_IN_SECONDS, $entity_type, $entity_id );
		$ttl = max( 0, (int) $ttl );

		return $this->set_cached( $cache_key, $version, $ttl );
	}

	/**
	 * Delete the version key of an entity by deleting its cached entry.
	 *
	 * @param string     $entity_type Entity type.
	 * @param string|int $entity_id   Entity ID.
	 * @return bool True on success, false on failure.
	 * @throws \InvalidArgumentException If entity_type or entity_id are invalid.
	 */
	public function delete_entity_version( string $entity_type, $entity_id ): bool {
		$this->validate_input( $entity_type, $entity_id );

		$cache_key = "wc_entity_version_key_{$entity_type}_{$entity_id}";
		return $this->delete_cached( $cache_key );
	}

	/**
	 * Validate entity type and entity ID inputs.
	 *
	 * @param string     $entity_type Entity type.
	 * @param string|int $entity_id   Entity ID.
	 * @return void
	 * @throws \InvalidArgumentException If entity_type or entity_id are invalid.
	 */
	private function validate_input( string $entity_type, $entity_id ): void {
		if ( '' === $entity_type ) {
			throw new \InvalidArgumentException( 'Entity type cannot be empty.' );
		}

		if ( ! is_numeric( $entity_id ) && ! is_string( $entity_id ) ) {
			throw new \InvalidArgumentException( 'Entity ID must be a number or a string.' );
		}

		if ( is_string( $entity_id ) && '' === $entity_id ) {
			throw new \InvalidArgumentException( 'Entity ID cannot be an empty string.' );
		}
	}

	/**
	 * Get a value from the cache.
	 *
	 * @param string $cache_key The cache key.
	 * @return mixed The cached value or null if not found.
	 */
	protected function get_cached( string $cache_key ) {
		/**
		 * Short-circuit the cache get operation.
		 *
		 * Return null to proceed with normal cache retrieval, or return
		 * any other value to bypass the cache operation and use the returned
		 * value directly.
		 *
		 * @since 10.4.0
		 *
		 * @param mixed|null $pre_value  Value to return instead of the cached value. Null means proceed normally.
		 * @param string     $cache_key  The cache key.
		 * @return mixed|null Value to use, or null to proceed with normal cache retrieval.
		 */
		$pre_value = apply_filters( 'woocommerce_pre_entity_version_keys_cache_get', null, $cache_key );

		if ( ! is_null( $pre_value ) ) {
			return $pre_value;
		}

		$found = false;
		$value = $this->legacy_proxy->call_function( 'wp_cache_get', $cache_key, 'woocommerce_entity_version_keys', false, $found );
		return $found ? $value : null;
	}

	/**
	 * Store a value in the cache.
	 *
	 * @param string $cache_key The cache key.
	 * @param mixed  $value     The value to cache.
	 * @param int    $ttl       Time to live in seconds.
	 * @return bool True on success, false on failure.
	 */
	protected function set_cached( string $cache_key, $value, int $ttl ): bool {
		/**
		 * Short-circuit the cache set operation.
		 *
		 * Return null to proceed with normal cache storage, or return a boolean
		 * to bypass the cache operation and use the returned value as the result.
		 *
		 * @since 10.4.0
		 *
		 * @param bool|null  $pre_result Result to return. Null means proceed normally.
		 * @param string     $cache_key  The cache key.
		 * @param mixed      $value      The value to cache.
		 * @param int        $ttl        Time to live in seconds.
		 * @return bool|null Result to return, or null to proceed with normal cache storage.
		 */
		$pre_result = apply_filters( 'woocommerce_pre_entity_version_keys_cache_set', null, $cache_key, $value, $ttl );

		if ( ! is_null( $pre_result ) ) {
			return $pre_result;
		}

		return $this->legacy_proxy->call_function( 'wp_cache_set', $cache_key, $value, 'woocommerce_entity_version_keys', $ttl );
	}

	/**
	 * Delete a value from the cache.
	 *
	 * @param string $cache_key The cache key.
	 * @return bool True on success, false on failure.
	 */
	protected function delete_cached( string $cache_key ): bool {
		/**
		 * Short-circuit the cache delete operation.
		 *
		 * Return null to proceed with normal cache deletion, or return a boolean
		 * to bypass the cache operation and use the returned value as the result.
		 *
		 * @since 10.4.0
		 *
		 * @param bool|null  $pre_result Result to return. Null means proceed normally.
		 * @param string     $cache_key  The cache key.
		 * @return bool|null Result to return, or null to proceed with normal cache deletion.
		 */
		$pre_result = apply_filters( 'woocommerce_pre_entity_version_keys_cache_delete', null, $cache_key );

		if ( ! is_null( $pre_result ) ) {
			return $pre_result;
		}

		return $this->legacy_proxy->call_function( 'wp_cache_delete', $cache_key, 'woocommerce_entity_version_keys' );
	}
}
