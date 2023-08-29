<?php

namespace Automattic\WooCommerce\Caches;

use Automattic\WooCommerce\Caching\CacheException;
use Automattic\WooCommerce\Caching\ObjectCache;

/**
 * A class to cache order objects.
 */
class OrderCache extends ObjectCache {

	/**
	 * The instance of OrderDataCache to use when cached order data needs to be removed.
	 *
	 * @var OrderDataCache
	 */
	private OrderDataCache $order_data_cache;

	/**
	 * Initialize the class.
	 *
	 * @param OrderDataCache $order_data_cache Instance of order data cache to use when cached order data needs to be removed.
	 *
	 * @internal
	 */
	final public function init( OrderDataCache $order_data_cache ) {
		$this->order_data_cache = $order_data_cache;
	}

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
	 * Add an order to the cache, or update an already cached order.
	 *
	 * @param \WC_Abstract_Order $object The order to be cached.
	 * @param int|string|null    $id Id of the order to be cached, if null, get_object_id will be used to get it.
	 * @param int                $expiration Expiration of the cached data in seconds from the current time, or DEFAULT_EXPIRATION to use the default value.
	 * @return bool True on success, false on error.
	 * @throws CacheException Invalid parameter, or null id was passed and get_object_id returns null too.
	 */
	public function set( $object, $id = null, int $expiration = self::DEFAULT_EXPIRATION ): bool {
		$result = parent::set( $object, $id, $expiration );
		$this->order_data_cache->remove( $id ?? $this->get_object_id( $object ) );
		return $result;
	}

	/**
	 * Update an order in the cache, but only if an order object is already cached with the same id.
	 *
	 * @param \WC_Abstract_Order $object The new order that will replace the already cached one.
	 * @param int|string|null    $id Id of the order to be cached, if null, get_object_id will be used to get it.
	 * @param int                $expiration Expiration of the cached data in seconds from the current time, or DEFAULT_EXPIRATION to use the default value.
	 * @return bool True on success, false on error or if no order with the supplied id was cached.
	 * @throws CacheException Invalid parameter, or null id was passed and get_object_id returns null too.
	 */
	public function update_if_cached( $object, $id = null, int $expiration = self::DEFAULT_EXPIRATION ): bool {
		$result = parent::update_if_cached( $object, $id, $expiration );
		$this->order_data_cache->remove( $id ?? $this->get_object_id( $object ) );
		return $result;
	}

	/**
	 * Remove an order from the cache.
	 *
	 * @param int|string $id The id of the order to remove.
	 * @return bool True if the order is removed from the cache successfully, false otherwise (because the order wasn't cached or for other reason).
	 */
	public function remove( $id ): bool {
		$result = parent::remove( $id );
		$this->order_data_cache->remove( $id );
		return $result;
	}

	/**
	 * Remove all the orders from the cache.
	 *
	 * @return bool True on success, false on error.
	 */
	public function flush(): bool {
		$result = parent::flush();
		$this->order_data_cache->flush();
		return $result;
	}
}
