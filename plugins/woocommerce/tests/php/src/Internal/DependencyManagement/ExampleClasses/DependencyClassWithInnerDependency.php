<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\DependencyManagement\ExampleClasses;

/**
 * An example of a class other classes depend on, and has itself a dependency.
 */
class DependencyClassWithInnerDependency {

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

	/**
	 * The instance of the inner dependency.
	 *
	 * @var InnerDependencyClass
	 */
	public $inner_dependency;

	/**
	 * Initialize the class instance.
	 *
	 * @internal
	 *
	 * @param InnerDependencyClass $inner_dependency A class we depend on.
	 */
	final public function init( InnerDependencyClass $inner_dependency ) {
		$this->inner_dependency = $inner_dependency;
	}
}
