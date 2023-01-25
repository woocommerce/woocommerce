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

	/**
	 * @inheritDoc
	 */
	protected function get_object_id( $object ) {
		// TODO: Implement get_object_id() method.
	}

	/**
	 * @inheritDoc
	 */
	protected function validate( $object ): ?array {
		// TODO: Implement validate() method.
	}

	/**
	 * @inheritDoc
	 */
	protected function get_from_datastore( $id ) {
		// TODO: Implement get_from_datastore() method.
	}
}
