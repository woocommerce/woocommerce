<?php
/**
 * ClassWithScalarInjectionMethodArgument class file.
 *
 * @package Automattic\WooCommerce\Tests\Internal\DependencyManagement\ExampleClasses
 */

namespace Automattic\WooCommerce\Tests\Internal\DependencyManagement\ExampleClasses;

/**
 * An example class that has an injector method argument with a scalar type but without a default value.
 */
class ClassWithScalarInjectionMethodArgument {

	// phpcs:disable Squiz.Commenting.FunctionComment.InvalidTypeHint

	/**
	 * Initialize the class instance.
	 *
	 * @internal
	 *
	 * @param mixed $scalar_argument_without_default_value Anything, really.
	 */
	final public function init( int $scalar_argument_without_default_value ) {
	}
}
