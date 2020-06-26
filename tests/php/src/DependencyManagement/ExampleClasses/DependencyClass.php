<?php
/**
 * DependencyClass class file.
 *
 * @package Automattic\WooCommerce\Tests\DependencyManagement\ExampleClasses
 */

namespace Automattic\WooCommerce\Tests\DependencyManagement\ExampleClasses;

/**
 * An example of a class other classes depend on.
 */
class DependencyClass {

	/**
	 * Concatenates the supplied string parts just for fun.
	 *
	 * @param mixed ...$parts The parts.
	 *
	 * @return string The resulting concatenated string.
	 */
	public static function concat( ...$parts ) {
		return 'Parts: ' . join( ', ', $parts );
	}
}
