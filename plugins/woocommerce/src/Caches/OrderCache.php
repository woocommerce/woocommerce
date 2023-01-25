<?php

namespace Automattic\WooCommerce\Caches;

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
		if ( ! $object instanceof \WC_Abstract_Legacy_Order ) {
			return array( 'The supplied order is not an instance of WC_Order, ' . gettype( $object ) );
		}

		return null;
	}

	/**
	 * Get an object from an authoritative data store.
	 * This is used by 'get' if the object isn't cached and no custom object retrieval callback is suupplied.
	 *
	 * @param int|string $id The id of the object to get.
	 *
	 * @return array|object|null The retrieved object, or null if it's not possible to retrieve an object by the given id.
	 */
	protected function get_from_datastore( $id ) {
		return null;
	}
}
