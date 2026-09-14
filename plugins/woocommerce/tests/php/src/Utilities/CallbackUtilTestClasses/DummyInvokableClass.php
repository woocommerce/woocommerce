<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Utilities\CallbackUtilTestClasses;

/**
 * Dummy invokable class for testing __invoke callbacks.
 */
class DummyInvokableClass {
	/**
	 * Invoke method.
	 *
	 * @return string
	 */
	public function __invoke() {
		return 'invokable';
	}
}
