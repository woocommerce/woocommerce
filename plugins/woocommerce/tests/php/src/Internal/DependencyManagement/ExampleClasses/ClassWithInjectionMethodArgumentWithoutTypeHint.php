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
	 * Initialize the class instance.
	 *
	 * @internal
	 *
	 * @param mixed $argument_without_type_hint Anything, really.
	 */
	final public function init( $argument_without_type_hint ) {
	}
}
