<?php
/**
 * ClassWithConstructorArgumentWithoutTypeHint class file.
 *
 * @package Automattic\WooCommerce\Tests\Internal\DependencyManagement\ExampleClasses
 */

namespace Automattic\WooCommerce\Tests\Internal\DependencyManagement\ExampleClasses;

/**
 * An example class that has a constructor argument with a scalar type but without a default value.
 */
class ClassWithScalarConstructorArgument {

	// phpcs:disable Squiz.Commenting.FunctionComment.InvalidTypeHint

	/**
	 * Class constructor.
	 *
	 * @param mixed $scalar_argument_without_default_value Anything, really.
	 */
	public function __construct( int $scalar_argument_without_default_value ) {
	}
}
