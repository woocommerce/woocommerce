<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\DependencyManagement\ExampleClasses;

/**
 * An example of a class that throws an exception in the 'init' method.
 */
class ClassThatThrowsOnInit {
	/**
	 * The exception to throw.
	 *
	 * @var \Exception
	 */
	public static $exception;

	/**
	 * Initialize the class instance.
	 *
	 * @internal
	 */
	final public function init() {
		throw self::$exception;
	}
}
