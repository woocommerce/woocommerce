<?php
namespace Automattic\WooCommerce\Blocks\Tests\BlockTypes;

use Automattic\WooCommerce\Blocks\Tests\Mocks\CartCheckoutUtilsMock;

/**
 * Tests for the Cart block type
 *
 * @since $VID:$
 */
class Cart extends \WP_UnitTestCase {
	/**
	 * We ensure deep sort works with all sort of arrays.
	 */
	public function test_deep_sort_with_accents() {
		$test_array_1 = array(
			'0',
			'1',
			array( '2', '3' ),
		);
		$test_array_2 = array(
			array( '0', '1' ),
			'2',
			'3',
		);
		$this->assertEquals( $test_array_1, CartCheckoutUtilsMock::deep_sort_test( $test_array_1 ), '' );
		$this->assertEquals( $test_array_2, CartCheckoutUtilsMock::deep_sort_test( $test_array_2 ), '' );
	}
}
