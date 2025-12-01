<?php

namespace Automattic\WooCommerce\Caches;

use Automattic\WooCommerce\Caching\ObjectCache;
use WC_Product;

/**
 * A class to cache Product objects.
 */
class ProductCache extends ObjectCache {

	/**
	 * Get the cache key and prefix to use for Products.
	 *
	 * @return string
	 */
	public function get_object_type(): string {
		return 'product_objects';
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
	 * @param WC_Product $object The object to validate.
	 * @return string[]|null An array of error messages, or null if the object is valid.
	 */
	protected function validate( $object ): ?array {
		if ( ! $object instanceof WC_Product ) {
			return array( 'The supplied product is not an instance of WC_Product, ' . gettype( $object ) );
		}

		return null;
	}
}
