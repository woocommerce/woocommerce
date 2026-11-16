<?php
/**
 * Object instantiation probe helper.
 *
 * @package WooCommerce\Tests
 */

declare( strict_types = 1 );

/**
 * Records whether a stored serialized value was decoded into an object of this class.
 */
final class ObjectInstantiationProbe {

	/**
	 * Whether an instance was created by unserialize().
	 *
	 * @var bool
	 */
	public static bool $was_unserialized = false;

	/**
	 * Record instantiation from a serialized value.
	 */
	public function __wakeup(): void {
		self::$was_unserialized = true;
	}
}
