<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\Caches;

use Automattic\WooCommerce\Proxies\LegacyProxy;

/**
 * Entity versions cache class.
 *
 * Provides a generic mechanism for caching versions of mutable entities.
 */
class EntityVersionsCache {

	/**
	 * Is the entity versions cache enabled?
	 *
	 * @var bool|null
	 */
	private ?bool $is_enabled = null;


	/**
	 * Tells whether the entity versions cache is enabled or not.
	 * The other methods of this class should be used only if this method returns true.
	 *
	 * @return bool
	 */
	public function is_enabled(): bool {
		if ( ! is_null( $this->is_enabled ) ) {
			return $this->is_enabled;
		}

		/**
		 * Filter whether to enable the entity versions cache.
		 *
		 * By default, the entity versions cache will be enabled only if an external
		 * object cache is configured in WordPress. Enabling it otherwise is not recommended
		 * (outside of development and testing scenarios) since cached entries would be stored
		 * directly in the options table, causing high stress in the database.
		 *
		 * @since 10.4.0
		 *
		 * @param bool   $use_output_cache Whether to use output cache. Default is the result of wp_using_ext_object_cache().
		 * @return bool True to enable output cache, false to disable.
		 */
		$this->is_enabled = apply_filters(
			'woocommerce_enable_entity_versions_cache',
			wc_get_container()->get( LegacyProxy::class )->call_function( 'wp_using_ext_object_cache' ) ?? false
		);

		return $this->is_enabled;
	}

	/**
	 * Get the current version of an entity.
	 *
	 * @param string     $entity_type Entity type.
	 * @param string|int $entity_id Entity ID.
	 * @return string Entity version.
	 */
	public function get_entity_version( string $entity_type, $entity_id ): string {
		$transient_name = "wc_entity_version_{$entity_type}_{$entity_id}";
		$version        = $this->get_cached( $transient_name );
		if ( false === $version ) {
			$version = $this->modify_entity_version( $entity_type, $entity_id );
		} else {
			// Refresh the transient lifetime.
			$this->store_entity_version( $entity_type, $entity_id, $version, false );
		}
		return $version;
	}

	/**
	 * Generate and store a new version for an entity.
	 *
	 * @param string     $entity_type Entity type.
	 * @param string|int $entity_id   Entity ID.
	 * @return string The new entity version.
	 */
	public function modify_entity_version( string $entity_type, $entity_id ): string {
		$version = wp_generate_uuid4();
		$this->store_entity_version( $entity_type, $entity_id, $version, true );
		return $version;
	}

	/**
	 * Store the entity version in a transient with a filterable TTL.
	 *
	 * @param string     $entity_type Entity type.
	 * @param string|int $entity_id   Entity ID.
	 * @param string     $version     The version to store.
	 * @param bool       $is_new      Whether this is a new version (true) or a refresh (false).
	 * @return bool True on success, false on failure.
	 */
	protected function store_entity_version( string $entity_type, $entity_id, string $version, bool $is_new ): bool {
		$transient_name = "wc_entity_version_{$entity_type}_{$entity_id}";

		/**
		 * Filter the TTL for entity version cache.
		 *
		 * @param int        $ttl         Time to live in seconds. Default 1 day.
		 * @param string     $entity_type The type of the entity.
		 * @param string|int $entity_id   The ID of the entity.
		 *
		 * @since 10.4.0
		 */
		$ttl    = apply_filters( 'woocommerce_cached_entity_version_ttl', DAY_IN_SECONDS, $entity_type, $entity_id );
		$result = $this->set_cached( $transient_name, $version, $ttl );

		/**
		 * Fires after an entity version has been generated or modified.
		 *
		 * @since 10.4.0
		 *
		 * @param string     $entity_type The type of the entity.
		 * @param string|int $entity_id   The ID of the entity.
		 * @param int        $ttl         Time to live in seconds.
		 * @param bool       $is_new      Whether this is a new version (true) or a refresh of existing version (false).
		 */
		do_action( 'woocommerce_entity_version_cached', $entity_type, $entity_id, $ttl, $is_new );

		return $result;
	}

	/**
	 * Forget the version of an entity by deleting its transient.
	 *
	 * @param string     $entity_type Entity type.
	 * @param string|int $entity_id   Entity ID.
	 * @return bool True on success, false on failure.
	 */
	public function forget_entity_version( string $entity_type, $entity_id ): bool {
		$transient_name = "wc_entity_version_{$entity_type}_{$entity_id}";
		$result         = $this->delete_cached( $transient_name );

		/**
		 * Fires after an entity version has been explicitly deleted.
		 *
		 * @since 10.4.0
		 *
		 * @param string     $entity_type The type of the entity.
		 * @param string|int $entity_id   The ID of the entity.
		 */
		do_action( 'woocommerce_entity_version_cache_deleted', $entity_type, $entity_id );

		return $result;
	}

	/**
	 * Get a value from the cache.
	 *
	 * @param string $cache_key The cache key.
	 * @return mixed The cached value or false if not found.
	 */
	protected function get_cached( string $cache_key ) {
		return get_transient( $cache_key );
	}

	/**
	 * Set a value in the cache.
	 *
	 * @param string $cache_key The cache key.
	 * @param mixed  $value     The value to cache.
	 * @param int    $ttl       Time to live in seconds.
	 * @return bool True on success, false on failure.
	 */
	protected function set_cached( string $cache_key, $value, int $ttl ): bool {
		return set_transient( $cache_key, $value, $ttl );
	}

	/**
	 * Delete a value from the cache.
	 *
	 * @param string $cache_key The cache key.
	 * @return bool True on success, false on failure.
	 */
	protected function delete_cached( string $cache_key ): bool {
		return delete_transient( $cache_key );
	}
}
