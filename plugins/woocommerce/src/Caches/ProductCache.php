<?php
declare( strict_types=1 );

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
	 * @param array|object $product The product to be cached.
	 *
	 * @return int|string|null The id of the object, or null if it can't be determined.
	 */
	protected function get_object_id( $product ) {
		return $product->get_id();
	}

	/**
	 * Validate an object before caching it.
	 *
	 * @param WC_Product $product The product to validate.
	 *
	 * @return string[]|null An array of error messages, or null if the object is valid.
	 */
	protected function validate( $product ): ?array {
		if ( ! $product instanceof WC_Product ) {
			return array( 'The supplied product is not an instance of WC_Product, ' . gettype( $product ) );
		}

		return null;
	}

	/**
	 * Add a product to the cache, or update an already cached product.
	 *
	 * Sets the clone mode to CACHE before storing to ensure meta IDs are preserved
	 * when WordPress object cache clones the object.
	 *
	 * @param WC_Product      $product The product to be cached.
	 * @param int|string|null $id Id of the product to be cached, if null, get_object_id will be used to get it.
	 * @param int             $expiration Expiration of the cached data in seconds from the current time, or DEFAULT_EXPIRATION to use the default value.
	 *
	 * @return bool True on success, false on error.
	 * @throws \Automattic\WooCommerce\Caching\CacheException Invalid parameter, or null id was passed and get_object_id returns null too.
	 */
	public function set( $product, $id = null, int $expiration = self::DEFAULT_EXPIRATION ): bool {
		$original_mode = $product->get_clone_mode();
		$product->set_clone_mode( \WC_Data::CLONE_MODE_CACHE );
		$result = parent::set( $product, $id, $expiration );
		$product->set_clone_mode( $original_mode );

		return $result;
	}

	/**
	 * Retrieve a cached product, and if no product is cached with the given id,
	 * try to get one via get_from_datastore callback and then cache it.
	 *
	 * After retrieval, resets the clone mode to DUPLICATE to maintain backward compatibility
	 * for code that expects cloning to clear meta IDs.
	 *
	 * @param int|string    $id The id of the product to retrieve.
	 * @param int           $expiration Expiration of the cached data in seconds from the current time, used if a product is retrieved from datastore and cached.
	 * @param callable|null $get_from_datastore_callback Optional callback to get the product if it's not cached, it must return a WC_Product or null.
	 *
	 * @return WC_Product|null Cached product, or null if it's not cached and can't be retrieved from datastore or via callback.
	 * @throws \Automattic\WooCommerce\Caching\CacheException Invalid id parameter.
	 */
	public function get( $id, int $expiration = self::DEFAULT_EXPIRATION, ?callable $get_from_datastore_callback = null ) {
		$product = parent::get( $id, $expiration, $get_from_datastore_callback );

		if ( $product instanceof \WC_Product ) {
			$product->set_clone_mode( \WC_Data::CLONE_MODE_DUPLICATE );
		}

		return $product;
	}
}
