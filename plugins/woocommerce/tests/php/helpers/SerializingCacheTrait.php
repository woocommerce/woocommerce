<?php

namespace Automattic\WooCommerce\RestApi\UnitTests;

/**
 * TestCase trait that allows the replacement of the default WP_Object_Cache with an instance that will
 * serialize and deserialize objects stored in cache instead of just keeping in memory to better mimic
 * most real world caching implementations.
 */
trait SerializingCacheTrait {

	private $wp_object_cache_instance;

	/**
	 * Replace the global WP_Object_Cache instance with a proxy instance that will run any retrieved data through
	 * serialization.
	 *
	 * @return void
	 */
	public function setup_mock_cache() {
		if ( is_null( $this->wp_object_cache_instance ) ) {
			$this->wp_object_cache_instance = $GLOBALS['wp_object_cache'];
			require_once __DIR__ . '/SerializingCacheProxy.php';
			$GLOBALS['wp_object_cache'] = new Serializing_Cache_Proxy( $this->wp_object_cache_instance );
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
		if ( ! is_null( $this->wp_object_cache_instance ) ) {
			$GLOBALS['wp_object_cache'] = $this->wp_object_cache_instance;
			unset( $this->wp_object_cache_instance );
		}
	}

}

