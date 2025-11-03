<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Utilities\CallbackUtilTestClasses;

/**
 * Dummy class for testing callbacks.
 */
class DummyCallbackClass {
	/**
	 * Test instance method.
	 *
	 * @return string
	 */
	public function my_method() {
		return 'instance method';
	}

	/**
	 * Another test instance method.
	 *
	 * @return string
	 */
	public function another_method() {
		return 'another instance method';
	}

	/**
	 * Test static method.
	 *
	 * @return string
	 */
	public static function static_method() {
		return 'static method';
	}
}
