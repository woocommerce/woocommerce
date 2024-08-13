<?php

namespace Automattic\WooCommerce\Caches;

use Automattic\WooCommerce\Caching\CacheEngine;
use Automattic\WooCommerce\Caching\ObjectCache;
use Automattic\WooCommerce\Caching\RequestLevelCacheEngine;
use Automattic\WooCommerce\Utilities\OrderUtil;

/**
 * A class to cache order objects.
 */
class OrderCache extends ObjectCache {

	/**
	 * Get the identifier for the type of the cached objects.
	 *
	 * @return string
	 */
	public function get_object_type(): string {
		return 'orders';
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
	 * Validate an object before caching it.
	 *
	 * @param array|object $object The object to validate.
	 * @return string[]|null An array of error messages, or null if the object is valid.
	 */
	protected function validate( $object ): ?array {
		if ( ! $object instanceof \WC_Abstract_Order ) {
			return array( 'The supplied order is not an instance of WC_Abstract_Order, ' . gettype( $object ) );
		}

		return null;
	}

	/**
	 * Get the instance of the cache engine to use.
	 *
	 * @return CacheEngine
	 */
	protected function get_cache_engine_instance(): CacheEngine {
		$container = wc_get_container();
		if ( OrderUtil::custom_orders_table_datastore_cache_enabled() ) {
			return $container->get( RequestLevelCacheEngine::class );
		}
		return parent::get_cache_engine_instance();
	}
}
