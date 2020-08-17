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

	// phpcs:disable WooCommerce.Functions.InternalInjectionMethod.MissingPublic

	/**
	 * Initialize the class instance.
	 *
	 * @internal
	 */
	final private function init() {
	}
}
