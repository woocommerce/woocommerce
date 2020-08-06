<?php
/**
 * ClassWithInjectionMethodArgumentWithoutTypeHint class file.
 *
 * @package Automattic\WooCommerce\Tests\Internal\DependencyManagement\ExampleClasses
 */

namespace Automattic\WooCommerce\Tests\Internal\DependencyManagement\ExampleClasses;

/**
 * An example class that has a injector method argument without type hint.
 */
class ClassWithInjectionMethodArgumentWithoutTypeHint {

	/**
	 * Sets class dependencies.
	 *
	 * @param mixed $argument_without_type_hint Anything, really.
	 */
	public function set_internal_dependencies( $argument_without_type_hint ) {
	}
}
