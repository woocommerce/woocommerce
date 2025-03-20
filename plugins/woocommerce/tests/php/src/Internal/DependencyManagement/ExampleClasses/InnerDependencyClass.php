<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\DependencyManagement\ExampleClasses;

/**
 * An example of a class other classes (that are themselves dependencies) depend on.
 */
class InnerDependencyClass {

	/**
	 * Count of instances of the class created so far.
	 *
	 * @var int
	 */
	public static $instances_count = 0;

	/**
	 * Creates a new instance of the class.
	 */
	public function __construct() {
		++self::$instances_count;
	}
}
