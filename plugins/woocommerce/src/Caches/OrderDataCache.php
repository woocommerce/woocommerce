<?php

namespace Automattic\WooCommerce\Caches;

use Automattic\WooCommerce\Caching\ObjectCache;

/**
 * Cache class for short-lived order operational data.
 * Entries are associative arrays having at last an "order_id" key.
 */
class OrderDataCache extends ObjectCache {

	/**
	 * Get the identifier for the type of the cached objects.
	 *
	 * @return string
	 */
	public function get_object_type(): string {
		return 'order_data';
	}

	/**
	 * Get the id of an object to be cached.
	 *
	 * @param array|object $object The object to be cached.
	 * @return int|string|null The id of the object, or null if it can't be determined.
	 */
	protected function get_object_id( $object ) {
		return $object['order_id'];
	}

	/**
	 * Validate an object before caching it.
	 *
	 * @param array|object $object The object to validate.
	 * @return string[]|null An array of error messages, or null if the object is valid.
	 */
	protected function validate( $object ): ?array {
		if ( ! is_array( $object ) || ! isset( $object['order_id'] ) ) {
			return array( "The supplied object must be an array containing an 'order_id' key" );
		}

		return null;
	}
}
