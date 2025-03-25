<?php

namespace Automattic\WooCommerce\Caches;

use Automattic\WooCommerce\Caching\ObjectCache;
use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;

/**
 * A class to cache order objects.
 */
class OrderCache extends ObjectCache {

	/**
	 * Get the cache key and prefix to use for Orders.
	 *
	 * @return string
	 */
	public function get_object_type(): string {
		if ( 'yes' === get_option( CustomOrdersTableController::HPOS_DATASTORE_CACHING_ENABLED_OPTION ) ) {
			/**
			 * The use of datastore caching moves persistent data caching to the datastore. Order object caching then only
			 * acts as request level caching as the `order_objects` cache group is set as non-persistent.
			 */
			return 'order_objects';
		} else {
			return 'orders';
		}
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
}
