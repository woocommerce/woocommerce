<?php
/**
 * ClassWithPrivateInjectionMethod class file.
 *
 * @package Automattic\WooCommerce\Tests\Internal\DependencyManagement\ExampleClasses
 */

namespace Automattic\WooCommerce\Tests\Internal\DependencyManagement\ExampleClasses;

/**
 * An example of a class with a private injection method.
 */
class ClassWithPrivateInjectionMethod {

	// phpcs:disable WooCommerce.Functions.InternalInjectionMethod.MissingPublic, WooCommerce.Functions.InternalInjectionMethod.MissingFinal

	/**
	 * Initialize the class instance.
	 *
	 * @internal
	 */
	private function init() {
	}

	// phpcs:enable
}
