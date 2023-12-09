<?php

namespace Automattic\WooCommerce\Blocks\Tests\Mocks;

class MockTestDependency {
	public $dependency;

	public function __construct( $dependency = null ) {
		$this->dependency = $dependency;
	}
};
