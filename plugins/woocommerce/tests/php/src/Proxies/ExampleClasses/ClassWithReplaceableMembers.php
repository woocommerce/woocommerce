<?php
/**
 * ClassWithReplaceableMembers class file
 */

namespace Automattic\WooCommerce\Tests\Proxies\ExampleClasses;

/**
 * An example class with members that can be replaceable using the DynamicDecorator class.
 */
class ClassWithReplaceableMembers {
	// phpcs:disable Squiz.Commenting

	public $some_property;

	public $some_other_property = 'hello';

	public function __construct() {
		$this->some_property = 'Initial value of $some_property';
	}

	public function some_method( $a, $b ) {
		return "\$some_method invoked with \$a=$a, \$b=$b";
	}

	// phpcs:enable Squiz.Commenting
}
