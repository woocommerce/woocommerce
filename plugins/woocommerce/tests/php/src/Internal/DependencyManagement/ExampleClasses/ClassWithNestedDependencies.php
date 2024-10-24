<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\DependencyManagement\ExampleClasses;

/**
 * An example of a class with dependencies that are supplied via constructor arguments,
 * with the depended on classes themselves having dependencies too.
 */
class ClassWithNestedDependencies {
	/**
	 * Count of instances of the class created so far.
	 *
	 * @var int
	 */
	public static $instances_count = 0;

	/**
	 * Value supplied to constructor in $dependency_class argument.
	 *
	 * @var DependencyClassWithInnerDependency
	 */
	public $dependency_class = null;

	/**
	 * Creates a new instance of the class.
	 */
	public function __construct() {
		++self::$instances_count;
	}

	/**
	 * Initialize the class instance.
	 *
	 * @internal
	 *
	 * @param DependencyClassWithInnerDependency $dependency_class A class we depend on.
	 */
	final public function init( DependencyClassWithInnerDependency $dependency_class ) {
		$this->dependency_class = $dependency_class;
	}
}
