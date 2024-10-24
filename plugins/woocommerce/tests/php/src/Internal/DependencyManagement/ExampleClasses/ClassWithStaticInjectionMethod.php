<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\DependencyManagement\ExampleClasses;

/**
 * An example of a class with a static injection method.
 */
class ClassWithStaticInjectionMethod {

	// phpcs:disable WooCommerce.Functions.InternalInjectionMethod.MissingPublic, WooCommerce.Functions.InternalInjectionMethod.MissingFinal

	/**
	 * Tells whether the 'init' method has been executed.
	 *
	 * @var bool
	 */
	public static $init_executed = false;

	/**
	 * Initialize the class instance.
	 *
	 * @internal
	 */
	public static function init() {
		self::$init_executed = true;
	}

	// phpcs:enable
}
