<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\DependencyManagement\ExampleClasses;

/**
 * An example of a class with a dependency that ends up being recursive (level 1/3).
 */
class ClassWithRecursiveDependencies1 {
	/**
	 * Initialize the class instance.
	 *
	 * @internal
	 *
	 * @param ClassWithRecursiveDependencies2 $dependency_class A class we depend on.
	 */
	final public function init( ClassWithRecursiveDependencies2 $dependency_class ) {
	}
}
