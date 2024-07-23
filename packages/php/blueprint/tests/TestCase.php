<?php

namespace Automattic\WooCommerce\Blueprint\Tests;

use PHPUnit\Framework\TestCase as PHPUnitTestCase;

require_once __DIR__.'/helpers.php';

abstract class TestCase extends PHPUnitTestCase
{
	protected bool $load_wp = false;
	public function __construct() {
		if ( $this->load_wp ) {
		}
		parent::__construct();
	}

	public function get_fixture_path( $filename ) {
		return __DIR__ . '/fixtures/' . $filename;
	}
}
