<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\DependencyManagement\ExampleClasses;

/**
 * An example of a class with a dependency that ends up being recursive (level 3/3).
 */
class ClassWithRecursiveDependencies3 {
	/**
	 * Initialize the class instance.
	 *
	 * @internal
	 *
	 * @param ClassWithRecursiveDependencies1 $dependency_class A class we depend on.
	 */
	final public function init( ClassWithRecursiveDependencies1 $dependency_class ) {
	}
}
