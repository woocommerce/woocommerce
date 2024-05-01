<?php

namespace Automattic\WooCommerce\Caches;

use Automattic\WooCommerce\Caching\CacheException;
use Automattic\WooCommerce\Caching\ObjectCache;

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
	 * Remove an object from the cache.
	 *
	 * @param int|string $id The id of the object to remove.
	 * @return bool True if the object is removed from the cache successfully, false otherwise (because the object wasn't cached or for other reason).
	 */
	public function remove( $id ): bool {
		$datastore = \WC_Data_Store::load( $this->get_object_type() );
		if ( method_exists( $datastore, 'invalidate_cache_for_objects' ) ) {
			$datastore->invalidate_cache_for_objects( array( $id ) );
		}

		return parent::remove( $id ); //$this->get_cache_engine()->delete_cached_object( $id, $this->get_object_type() );
	}
}
