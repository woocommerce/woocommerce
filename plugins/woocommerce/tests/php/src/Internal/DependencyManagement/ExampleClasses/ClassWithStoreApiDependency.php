<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\DependencyManagement\ExampleClasses;

use Automattic\WooCommerce\StoreApi\Schemas\ExtendSchema;

/**
 * Example class with a dependency that is to be resolved by the Store API.
 */
class ClassWithStoreApiDependency {
	/**
	 * Dependency passed to the 'init' method.
	 *
	 * @var ExtendSchema
	 */
	public $dependency_class;

	/**
	 * Initialize the class instance.
	 *
	 * @internal
	 *
	 * @param ExtendSchema $dependency_class A class we depend on.
	 */
	final public function init( ExtendSchema $dependency_class ) {
		$this->dependency_class = $dependency_class;
	}
}
