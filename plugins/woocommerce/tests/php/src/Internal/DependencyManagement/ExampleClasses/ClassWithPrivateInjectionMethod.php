<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\DependencyManagement\ExampleClasses;

/**
 * An example of a class with a private injection method.
 */
class ClassWithPrivateInjectionMethod {

	// phpcs:disable WooCommerce.Functions.InternalInjectionMethod.MissingPublic, WooCommerce.Functions.InternalInjectionMethod.MissingFinal

	/**
	 * Tells whether the 'init' method has been executed.
	 *
	 * @var bool
	 */
	public $init_executed = false;

	/**
	 * Initialize the class instance.
	 *
	 * @internal
	 */
	private function init() {
		$this->init_executed = true;
	}

	// phpcs:enable
}
