<?php

namespace Automattic\WooCommerce\Blueprint\Tests;

use PHPUnit\Framework\TestCase as PHPUnitTestCase;

require_once __DIR__ . '/helpers.php';

/**
 * Class TestCase
 */
abstract class TestCase extends PHPUnitTestCase {

	/**
	 * Get the path to a fixture file.
	 *
	 * @param string $filename The filename.
	 *
	 * @return string The path to the fixture file.
	 */
	public function get_fixture_path( $filename ) {
		return __DIR__ . '/fixtures/' . $filename;
	}
}
