<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\DependencyManagement\ExampleClasses;

/**
 * An example of a class with a dependency that is passed by reference in the 'init' method.
 */
class ClassThatHasReferenceArgumentsInInit {

	/**
	 * Initialize the class instance.
	 *
	 * @internal
	 *
	 * @param DependencyClass $dependency_by_reference A class we depend on.
	 */
	final public function init( DependencyClass &$dependency_by_reference ) {
	}
}
