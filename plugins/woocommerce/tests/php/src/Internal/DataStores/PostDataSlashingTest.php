<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\DataStores;

use Automattic\WooCommerce\Internal\DataStores\PostDataSlashing;
use WC_Unit_Test_Case;

/**
 * Tests for the PostDataSlashing class.
 */
class PostDataSlashingTest extends WC_Unit_Test_Case {

	/**
	 * Values and the result of slashing them.
	 *
	 * @return array<string, array{0: string, 1: string}>
	 */
	public function provider_values(): array {
		return array(
			'backslash'                       => array( 'Cable 1\\2 inch', 'Cable 1\\\\2 inch' ),
			'quotes'                          => array( 'Men\'s 12" shirt', 'Men\\\'s 12\\" shirt' ),
			'backslash and quote'             => array( 'C:\\temp, it\'s', 'C:\\\\temp, it\\\'s' ),
			'output of wp_filter_post_kses()' => array( 'Men\\\'s 12\\" shirt', 'Men\\\'s 12\\" shirt' ),
			'no special characters'           => array( 'Plain name', 'Plain name' ),
		);
	}

	/**
	 * @testdox Should slash string values unless they are already slashed.
	 * @dataProvider provider_values
	 *
	 * @param string $value    Value to slash.
	 * @param string $expected Expected result.
	 */
	public function test_slashes_values_unless_already_slashed( string $value, string $expected ): void {
		$result = PostDataSlashing::slash( array( 'post_title' => $value ) );

		$this->assertSame( $expected, $result['post_title'] );
	}

	/**
	 * @testdox Should leave non-string values and non-array data unchanged.
	 */
	public function test_leaves_non_string_values_and_non_array_data_unchanged(): void {
		$data = array(
			'menu_order' => 3,
			'meta_input' => array( '_note' => 'it\'s' ),
		);

		$this->assertSame( $data, PostDataSlashing::slash( $data ), 'Non-string values should be unchanged.' );
		$this->assertSame( 'post_title=it\'s', PostDataSlashing::slash( 'post_title=it\'s' ), 'Non-array data should be unchanged.' );
	}
}
