<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Blocks;

use Automattic\WooCommerce\Internal\Blocks\InCartQuantity;
use WC_Unit_Test_Case;

/**
 * Tests for the Internal\Blocks\InCartQuantity class.
 */
class InCartQuantityTest extends WC_Unit_Test_Case {

	/**
	 * @testdox Should sum lines for the product and skip lines declared as children.
	 */
	public function test_sums_product_lines_and_skips_declared_children(): void {
		$items = array(
			array(
				'id'       => 10,
				'quantity' => 1,
			),
			array(
				'id'              => 10,
				'quantity'        => 2,
				'parent_item_key' => null,
			),
			array(
				'id'              => 10,
				'quantity'        => 3,
				'parent_item_key' => '',
			),
			array(
				'id'              => 10,
				'quantity'        => 4,
				'item_data'       => array( 'name' => 'Gift wrap' ),
				'parent_item_key' => null,
			),
			array(
				'id'              => 10,
				'quantity'        => 5,
				'parent_item_key' => 'parent-a',
			),
			array(
				'id'       => 11,
				'quantity' => 6,
			),
		);

		$this->assertSame(
			10,
			InCartQuantity::for_product( $items, 10 ),
			'Lines without a non-empty parent key should count; child lines and other products should not'
		);
	}

	/**
	 * @testdox Should preserve fractional quantities.
	 */
	public function test_preserves_fractional_quantities(): void {
		$items = array(
			array(
				'id'       => 10,
				'quantity' => 1.5,
			),
			array(
				'id'       => 10,
				'quantity' => 2,
			),
		);

		$this->assertSame( 3.5, InCartQuantity::for_product( $items, 10 ), 'Fractional quantity should not be truncated' );
	}
}
