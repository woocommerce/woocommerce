<?php
/**
 * ClassWithConstructorArgumentWithoutTypeHint class file.
 */

namespace Automattic\WooCommerce\Tests\Internal\DependencyManagement\ExampleClasses;

/**
 * An example class that has a constructor argument without type hint.
 */
class ClassWithConstructorArgumentWithoutTypeHint {

	/**
	 * Class constructor.
	 *
	 * @param mixed $argument_without_type_hint Anything, really.
	 */
	public function __construct( $argument_without_type_hint ) {
	}
}
