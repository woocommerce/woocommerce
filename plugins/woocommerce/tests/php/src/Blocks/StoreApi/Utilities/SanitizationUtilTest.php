<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\StoreApi\Utilities;

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
			'g' => '<span>alert("hello");</span>',
			'h' => [
				'i' => '<span>alert("i");</span>',
				'j' => '&lt;script&gt;alert("i");&lt;/script&gt;',
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
			'a' => true,
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
			],
			'l' => false,
			'm' => null,
			'n' => 0,
			'o' => [],
			'p' => [ [ [ [ [ [ [ [ [] ] ] ] ] ] ] ] ],
			'q' => [ [ [ [ [ [ [ [ [ 'really_nested' => 'boo' ] ] ] ] ] ] ] ] ],
			'r' => '',
		];
		$this->assertSame( $expected, $sanitization_utils->wp_kses_array( $input ) );
	}
}
