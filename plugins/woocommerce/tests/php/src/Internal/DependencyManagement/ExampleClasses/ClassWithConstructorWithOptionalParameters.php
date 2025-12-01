<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\DependencyManagement\ExampleClasses;

/**
 * An example of a class with a constructor that has optional parameters.
 */
class ClassWithConstructorWithOptionalParameters {
	// phpcs:disable Squiz.Commenting
	public int $the_num;
	public ?string $the_string;

	public function __construct( int $some_parameter = 34, ?string $other_parameter = null ) {
		$this->the_num    = $some_parameter;
		$this->the_string = $other_parameter;
	}
	// phpcs:enable Squiz.Commenting
}
