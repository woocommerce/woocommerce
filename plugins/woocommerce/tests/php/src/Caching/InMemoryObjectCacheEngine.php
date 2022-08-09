<?php

namespace Automattic\WooCommerce\Tests\Caching;

use Automattic\WooCommerce\Caching\CacheEngine;
use Automattic\WooCommerce\Utilities\ArrayUtil;

/**
 * An implementation of CacheEngine that simply stores cached objects in an array.
 */
class InMemoryObjectCacheEngine implements CacheEngine {

	/**
	 * The cached objects.
	 *
	 * @var array
	 */
	public $cache = array();

	/**
	 * Whether calls to 'cache_object' will succeed or not.
	 *
	 * @var bool
	 */
	public $caching_succeeds = true;

	/**
	 * Value of the expiration time that was passed to 'cache_object' the last time it was called.
	 *
	 * @var int
	 */
	public $last_expiration;

	// phpcs:disable Squiz.Commenting

	public function get_cached_object( string $key ) {
		return ArrayUtil::get_value_or_default( $this->cache, $key, null );
	}

	public function cache_object( string $key, $object, int $expiration ): bool {
		if ( ! $this->caching_succeeds ) {
			return false;
		}
		$this->cache[ $key ]   = $object;
		$this->last_expiration = $expiration;
		return true;
	}

	public function delete_cached_object( string $key ): bool {
		if ( array_key_exists( $key, $this->cache ) ) {
			unset( $this->cache[ $key ] );
			return true;
		}

		return false;
	}

	public function is_cached( $key ): bool {
		return array_key_exists( $key, $this->cache );
	}

	// phpcs:enable Squiz.Commenting
}
