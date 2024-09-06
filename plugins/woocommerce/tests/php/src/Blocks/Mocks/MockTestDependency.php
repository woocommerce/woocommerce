<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\Mocks;

/**
 * A mock class.
 */
class MockTestDependency {
	public $dependency;

	public function __construct( $dependency = null ) {
		$this->dependency = $dependency;
	}
}
