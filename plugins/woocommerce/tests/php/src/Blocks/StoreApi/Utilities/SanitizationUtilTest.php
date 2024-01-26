<?php

namespace Automattic\WooCommerce\Tests\Utilities;

use Automattic\WooCommerce\StoreApi\Utilities\SanitizationUtils;

/**
 * A collection of tests for the array utility class.
 */
class SanitizationUtilTest extends \WC_Unit_Test_Case {


	/**
	 * @testdox `kses_array` should return an array of the same shape, but strings should be cleaned with `wp_kses`.
	 */
	public function test_kses_array() {
		$sanitization_utils = new SanitizationUtils();
		$input              = [
			'a' => true,
			'b' => [
				'c' => 'c',
				'd' => [
					'e' => 'e',
				],
			],
			'f' => 1,
			'g' => '<script>alert("hello");</script>',
		];
		$expected           = [
			'a' => 'a',
			'b' => [
				'c' => 'c',
				'd' => [
					'e' => 'e',
				],
			],
			'f' => 1,
			'g' => 'alert("hello");',
		];
		$this->assertEquals( $expected, $sanitization_utils->wp_kses_array( $input ) );
	}
}
