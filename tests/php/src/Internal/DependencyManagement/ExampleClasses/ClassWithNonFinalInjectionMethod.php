<?php
/**
 * ClassWithNonFinalInjectionMethod class file.
 *
 * @package Automattic\WooCommerce\Tests\Internal\DependencyManagement\ExampleClasses
 */

namespace Automattic\WooCommerce\Tests\Internal\DependencyManagement\ExampleClasses;

/**
 * An example of a class with a private injection method.
 */
class ClassWithNonFinalInjectionMethod {

	// phpcs:disable WooCommerce.Functions.InternalInjectionMethod.MissingFinal

	/**
	 * Initialize the class instance.
	 *
	 * @internal
	 */
	public function init() {
	}
}
