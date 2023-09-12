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
	 * Transforms an order right before being passed to the caching engine
	 * (needed to support get_data and set_data).
	 *
	 * @param object $object The order to transform.
	 * @return array The transformed order.
	 */
	protected function transform_for_caching($object ) {
		return ['order' => $object, 'order_data' => []];
	}

	/**
	 * Detransforms an order right after being retrieved from the caching engine
	 * (needed to support get_data and set_data).
	 *
	 * @param object|array $object The order to detransform.
	 * @return object|array The detransformed order.
	 */
	protected function detransform_from_cache($object ) {
		return $object['order'];
	}

	/**
	 * Get a piece of named data associated to a cached order
	 * and previously cached with set_data.
	 *
	 * @param int|string $order_id The order id.
	 * @param string $data_key The key that was used to cache the data.
	 * @param mixed $default The default value to return if there's no data cached under the specified key for the given order id.
	 * @return mixed|null The cached piece of data or the return value.
	 * @throws CacheException There's no order cached with the specified order id.
	 */
	public function get_data($order_id, string $data_key, $default = null) {
		$full_dataset = $this->get_dataset_for_order($order_id);

		return $full_dataset['order_data'][$data_key] ?? $default;
	}

	/**
	 * Cache a piece of named data associated to a cached order,
	 * it will be later retrievable with get_data.
	 *
	 * Note that all the data, including the order object itself, will be re-cached using the supplied expiration.
	 *
	 * @param int|string $order_id The order id.
	 * @param string $data_key The key to use to cache the data.
	 * @param int $expiration New expiration of the cached data (including the order itself) in seconds from the current time, or DEFAULT_EXPIRATION to use the default value.
	 * @return bool True on success, false on error.
	 * @throws CacheException Invalid parameter, or there's no order cached with the specified order id.
	 */
	public function set_data($order_id, string $data_key, $value, int $expiration = self::DEFAULT_EXPIRATION): bool	{
		$dataset = $this->get_dataset_for_order($order_id);

		$dataset['order_data'][$data_key] = $value;
		$dataset['order_data']['__expiration'] = $expiration;

		$this->verify_expiration_value( $expiration );
		return $this->get_cache_engine()->cache_object(
			$order_id,
			$dataset,
			self::DEFAULT_EXPIRATION === $expiration ? $this->default_expiration : $expiration,
			$this->get_object_type()
		);
	}

	/**
	 * Remove all the pieces of named data cached for a given order,
	 * while preserving the order object itself.
	 *
	 * Note that the order object will be re-cached using the last expiration value supplied to set_data, or the default expiration if that's not available.
	 *
	 * @param int|string $order_id The order id.
	 * @return bool True on success, false on error.
	 * @throws CacheException Invalid parameter, or there's no order cached with the specified order id.
	 */
	public function remove_all_data($order_id): bool {
		$dataset = $this->get_dataset_for_order($order_id);
		if(empty($dataset['order_data'])) {
			return true;
		}

		$expiration = $dataset['order_data']['__expiration'] ?? $this->default_expiration;
		$dataset['order_data'] = [];
		return $this->get_cache_engine()->cache_object(
			$order_id,
			$dataset,
			$expiration,
			$this->get_object_type()
		);
	}

	/**
	 * Auxiliary method to get the cached dataset (order object + order data array) for a given order,
	 * or to throw an exception when appropriate.
	 *
	 * @param int|string $order_id The order to get the cached dataset for.
	 * @return array The cached dataset for the order.
	 * @throws CacheException Invalid order id type, or there's no order cached with the specified id.
	 */
	private function get_dataset_for_order($order_id): array {
		if ( ! is_string( $order_id ) && ! is_int( $order_id ) ) {
			throw new CacheException( "Order id must be an int or a string", $this );
		}

		$dataset = $this->get_cache_engine_instance()->get_cached_object($order_id, $this->get_object_type());
		if(is_null($dataset)) {
			throw new CacheException("There's no order cached with id $order_id", $this);
		}

		return $dataset;
	}
}
