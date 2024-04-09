<?php

namespace Automattic\WooCommerce\RestApi\UnitTests;

use PHPUnit\Framework\MockObject\MockBuilder;

/**
 * TestCase trait that allows the replacement of the default WP_Object_Cache with an instance that will
 * serialize and deserialize objects stored in cache instead of just keeping in memory to better mimic
 * most real world caching implementations.
 */
trait SerializingCacheTrait {

	private $wp_object_cache_instance;

	abstract public function getMockBuilder( string $className ): MockBuilder;


	public function setup_mock_cache() {
		if ( is_null( $this->wp_object_cache_instance ) ) {
			$this->wp_object_cache_instance = $GLOBALS['wp_object_cache'];

			$GLOBALS['wp_object_cache'] = new Serializing_Cache_Proxy( $this->wp_object_cache_instance );
		}
	}

	/**
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

/**
 * Proxy class used to mimic object cache instances that serialize/deserialized stored data.
 */
class Serializing_Cache_Proxy {
	public $original_cache_instance;

	public function __construct( $original_cache_instance ) {
		$this->original_cache_instance = $original_cache_instance;
	}

	public function __call( $function, $args ) {
		return $this->original_cache_instance->$function( ...$args );
	}

	public function get( $key, $group = 'default', $force = false, &$found = null ) {
		$data = $this->original_cache_instance->get( $key, $group, $found, $found );
		if ( is_object( $data ) || is_array( $data ) ) {
			return unserialize( serialize( $data ) );
		}

		return $data;
	}
}
