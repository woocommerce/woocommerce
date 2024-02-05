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
			'h' => [
				'i' => '<script>alert("i");</script>',
				'j' => '&lt;script&gt;alert("i");&lt;/script&gt;',
				'k' => '<div <script>alert("malformed HTML");</s<div>cript><img src= />',
			],
			'l' => false,
			'm' => null,
			'n' => 0,
			'o' => [],
			'p' => [ [ [ [ [ [ [ [ [] ] ] ] ] ] ] ] ],
			'q' => [ [ [ [ [ [ [ [ [ 'really_nested' => 'boo' ] ] ] ] ] ] ] ] ],
			'r' => '',
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
			'h' => [
				'i' => 'alert("i");',
				'j' => '&lt;script&gt;alert("i");&lt;/script&gt;',
				'k' => '&lt;div alert("malformed HTML");&lt;/script&gt;',
			],
			'l' => false,
			'm' => null,
			'n' => 0,
			'o' => [],
			'p' => [ [ [ [ [ [ [ [ [] ] ] ] ] ] ] ] ],
			'q' => [ [ [ [ [ [ [ [ [ 'really_nested' => 'boo' ] ] ] ] ] ] ] ] ],
			'r' => '',
		];
		$this->assertEquals( $expected, $sanitization_utils->wp_kses_array( $input ) );
	}
}
