<?php

namespace Automattic\WooCommerce\Caches;

use Automattic\WooCommerce\Caching\CacheEngine;
use Automattic\WooCommerce\Caching\ObjectCache;
use Automattic\WooCommerce\Caching\TransientsEngine;

/**
 * A class to cache order objects.
 */
class OrderCache extends ObjectCache {

	/**
	 * The cache engine to use.
	 *
	 * @var CacheEngine
	 */
	private $cache_engine;

	/**
	 * Initializes the class.
	 *
	 * @internal
	 * @param TransientsEngine $cache_engine The cache engine to use.
	 */
	final public function init( TransientsEngine $cache_engine ) {
		$this->cache_engine = $cache_engine;
	}

	/**
	 * Get the identifier for the type of the cached objects.
	 *
	 * @return string
	 */
	public function get_object_type(): string {
		return 'order';
	}

	/**
	 * Get the id of an object to be cached.
	 *
	 * @param array|object $object The object to be cached.
	 * @return int|string|null The id of the object, or null if it can't be determined.
	 */
	protected function get_object_id( $object ) {
		return $object->get_id();
	}

	/**
	 * Get the cache engine to use.
	 *
	 * @return CacheEngine
	 */
	protected function get_cache_engine_instance(): CacheEngine {
		return $this->cache_engine;
	}

	/**
	 * Validate an object before caching it.
	 *
	 * @param array|object $object The object to validate.
	 * @return string[]|null An array of error messages, or null if the object is valid.
	 */
	protected function validate( $object ): ?array {
		if ( ! $object instanceof \WC_Abstract_Legacy_Order ) {
			return array( 'The supplied order is not an instance of WC_Order, ' . gettype( $object ) );
		}

		return null;
	}
}
