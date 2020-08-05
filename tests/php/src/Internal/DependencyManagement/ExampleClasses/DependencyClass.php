<?php
/**
 * DependencyClass class file.
 */

namespace Automattic\WooCommerce\Tests\Internal\DependencyManagement\ExampleClasses;

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
