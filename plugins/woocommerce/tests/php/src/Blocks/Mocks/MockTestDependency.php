<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\Mocks;

/**
 * A mock class.
 */
class MockTestDependency {
	/**
	 * Dependency.
	 *
	 * @var object
	 */
	public $dependency;

	/**
	 * Constructor.
	 *
	 * @param object $dependency Another dependency.
	 */
	public function __construct( $dependency = null ) {
		$this->dependency = $dependency;
	}
}
