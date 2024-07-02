<?php

namespace Automattic\WooCommerce\RestApi\UnitTests;

require_once __DIR__ . '/SerializingCacheProxy.php';

/**
 * TestCase trait that allows the replacement of the default WP_Object_Cache with an instance that will
 * serialize and deserialize objects stored in cache instead of just keeping in memory to better mimic
 * most real world caching implementations.
 */
trait SerializingCacheTrait {

	/**
	 * Replace the global WP_Object_Cache instance with a proxy instance that will run any retrieved data through
	 * serialization.
	 *
	 * @return void
	 */
	public function setup_serializing_cache() {
		if ( ! $GLOBALS['wp_object_cache'] instanceof Serializing_Cache_Proxy ) {
			$original_object_cache_instance = $GLOBALS['wp_object_cache'];
			$GLOBALS['wp_object_cache']     = new Serializing_Cache_Proxy( $original_object_cache_instance );  // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		}
	}

	/**
	 * Clean up after a test that used $this::setup_mock_cache() by restoring the initial WP_Object_Cache instance.
	 * This is run automatically after each test.
	 *
	 * @return void
	 *
	 * @after Force cleanup of serializing cache instance if being used.
	 */
	public function cleanup_mock_cache() {
		if ( $GLOBALS['wp_object_cache'] instanceof Serializing_Cache_Proxy ) {
			$GLOBALS['wp_object_cache'] = $GLOBALS['wp_object_cache']->original_cache_instance; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		}
	}
}
