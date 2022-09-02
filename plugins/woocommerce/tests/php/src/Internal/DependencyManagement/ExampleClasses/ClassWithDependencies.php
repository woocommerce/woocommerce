<?php
/**
 * ClassWithDependencies class file.
 */

namespace Automattic\WooCommerce\Tests\Internal\DependencyManagement\ExampleClasses;

/**
 * An example of a class with dependencies that are supplied via constructor arguments.
 */
class ClassWithDependencies {

	/**
	 * Default value for $some_number argument.
	 */
	const SOME_NUMBER = 34;

	/**
	 * Count of instances of the class created so far.
	 *
	 * @var int
	 */
	public static $instances_count = 0;

	/**
	 * Value supplied to constructor in $some_number argument.
	 *
	 * @var int
	 */
	public $some_number = 0;

	/**
	 * Value supplied to constructor in $dependency_class argument.
	 *
	 * @var DependencyClass
	 */
	public $dependency_class = null;

	/**
	 * Initialize the class instance.
	 *
	 * @internal
	 *
	 * @param DependencyClass $dependency_class A class we depend on.
	 * @param int             $some_number Some number we need for some reason.
	 */
	final public function init( DependencyClass $dependency_class, int $some_number = self::SOME_NUMBER ) {
		self::$instances_count++;
		$this->dependency_class = $dependency_class;
		$this->some_number      = self::SOME_NUMBER;
	}
}
