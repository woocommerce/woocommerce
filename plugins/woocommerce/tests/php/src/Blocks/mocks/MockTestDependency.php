<?php

namespace Automattic\WooCommerce\Tests\Blocks\Mocks;

class MockTestDependency {
	public $dependency;

	public function __construct( $dependency = null ) {
		$this->dependency = $dependency;
	}
};
