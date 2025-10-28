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
	 * Cache group name.
	 */
	private const CACHE_GROUP = 'woocommerce_entity_version_keys';

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
	 * since otherwise the cached entries will only persist for the current request.
	 *
	 * @return bool
	 */
	public function should_use(): bool {
		if ( ! is_null( $this->should_use ) ) {
			return $this->should_use;
		}

		$this->should_use = is_null( $this->legacy_proxy ) ? false : ( $this->legacy_proxy->call_function( 'wp_using_ext_object_cache' ) ?? false );

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
		$found     = false;
		$version   = wp_cache_get( $cache_key, self::CACHE_GROUP, false, $found );

		if ( ! $found ) {
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

		return wp_cache_set( $cache_key, $version, self::CACHE_GROUP, $ttl );
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
		return wp_cache_delete( $cache_key, self::CACHE_GROUP );
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
}
