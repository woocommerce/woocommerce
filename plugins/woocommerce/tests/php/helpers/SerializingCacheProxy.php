<?php

namespace Automattic\WooCommerce\RestApi\UnitTests;

/**
 * Proxy class used to mimic object cache instances that serialize/deserialized stored data.
 */
class Serializing_Cache_Proxy {

	/**
	 * A reference to the original global object cache instance.
	 *
	 * @var \WP_Object_Cache
	 */
	public $original_cache_instance;

	/**
	 * @param \WP_Object_Cache $original_cache_instance
	 */
	public function __construct( $original_cache_instance ) {
		$this->original_cache_instance = $original_cache_instance;
	}

	/**
	 * Proxy method to route all method calls to the underlying cache object.
	 *
	 * @param string $func The function name being called.
	 * @param array $args The arguments to pass to the underlying class.
	 *
	 * @return mixed
	 */
	public function __call( $func, $args ) {
		return $this->original_cache_instance->$func( ...$args );
	}

	/**
	 * Retrieves the cache contents, if it exists. This is a wrapper to the underlying WP_Object_Cache
	 * instance and will serialize and deserialize any arrays or objects prior to returning in order to
	 * mimic behaviour of caching where data is stored serialized.
	 *
	 * @param int|string $key The key under which the cache contents are stored.
	 * @param string     $group Optional. Where the cache contents are grouped. Default 'default'.
	 * @param bool       $force Optional. Unused. Whether to force an update of the local cache
	 *                    from the persistent cache. Default false.
	 * @param bool       $found Optional. Whether the key was found in the cache (passed by reference).
	 *                    Disambiguates a return of false, a storable value. Default null.
	 *
	 * @return mixed|false The cache contents on success, false on failure to retrieve contents.
	 */
	public function get( $key, $group = 'default', $force = false, &$found = null ) {
		$data = $this->original_cache_instance->get( $key, $group, $found, $found );
		if ( is_object( $data ) || is_array( $data ) ) {
			return unserialize( serialize( $data ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize,WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
		}

		return $data;
	}
}
