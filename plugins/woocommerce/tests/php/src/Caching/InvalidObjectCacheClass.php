<?php

namespace Automattic\WooCommerce\Tests\Caching;

use Automattic\WooCommerce\Caching\ObjectCache;

/**
 * An implementation of CacheEngine that is invalid (returns an empty object type).
 */
class InvalidObjectCacheClass extends ObjectCache {


	// phpcs:disable Squiz.Commenting

	public function get_object_type(): string {
		return '';
	}

	// phpcs:enable Squiz.Commenting
}
