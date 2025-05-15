<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\DependencyManagement\ExampleClasses;

/**
 * An example of a class with a constructor that has non-optional parameters.
 */
class ClassWithConstructorWithParameters {
	// phpcs:ignore Squiz.Commenting.FunctionComment.Missing
	public function __construct( int $some_parameter, string $other_parameter ) {
	}
}
