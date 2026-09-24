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
	 * @testdox Should sum product lines and exclude lines declared as children.
	 */
	public function test_sums_product_lines_and_excludes_declared_children(): void {
		$items = array(
			array(
				'key'      => 'plain-line',
				'id'       => 10,
				'type'     => 'simple',
				'quantity' => 3,
			),
			array(
				'key'             => 'item-data-line',
				'id'              => 10,
				'type'            => 'simple',
				'quantity'        => 2,
				'item_data'       => array( 'name' => 'Gift wrap' ),
				'parent_item_key' => null,
			),
			array(
				'key'             => 'child-line',
				'id'              => 10,
				'type'            => 'simple',
				'quantity'        => 1,
				'parent_item_key' => 'parent-a',
			),
		);

		$this->assertSame(
			5,
			InCartQuantity::for_product( $items, 10 ),
			'Plain and item-data lines should count while declared child lines are excluded'
		);
		$this->assertSame(
			0,
			InCartQuantity::for_product( array( $items[2] ), 10 ),
			'A cart containing only a declared child line should have no eligible quantity'
		);
	}

	/**
	 * @testdox Should include lines without a non-empty string parent key.
	 */
	public function test_includes_lines_without_non_empty_string_parent_keys(): void {
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
				'parent_item_key' => 'missing-parent',
			),
		);

		$this->assertSame(
			6,
			InCartQuantity::for_product( $items, 10 ),
			'Missing, null, and empty parent keys should count, but any non-empty string should not'
		);
	}

	/**
	 * @testdox Should match variation lines by ID without counting unrelated products.
	 */
	public function test_matches_variation_lines_by_id(): void {
		$items = array(
			array(
				'id'        => 101,
				'type'      => 'variation',
				'quantity'  => 2,
				'variation' => array( 'attribute_pa_color' => 'blue' ),
			),
			array(
				'id'       => 102,
				'type'     => 'variation',
				'quantity' => 8,
			),
		);

		$this->assertSame(
			2,
			InCartQuantity::for_product( $items, 101 ),
			'A matching variation ID should contribute without inspecting its selection'
		);
		$this->assertSame(
			0,
			InCartQuantity::for_product( $items, 10 ),
			'A variable parent without its own matching line should not inherit variation quantities'
		);
	}

	/**
	 * @testdox Should return zero for empty, child-only, and unusable cart entries without diagnostics.
	 */
	public function test_returns_zero_for_empty_or_unusable_cart_entries_without_diagnostics(): void {
		$this->assertSame( 0, InCartQuantity::for_product( array(), 10 ), 'An empty cart should have zero quantity' );

		$items = array(
			array(
				'id'              => 10,
				'quantity'        => 3,
				'parent_item_key' => 'parent-a',
			),
			array(),
			'not-an-array',
			array(
				'quantity' => 5,
			),
			array(
				'id'       => '10',
				'quantity' => 7,
			),
			array(
				'id'       => 10,
				'quantity' => '9',
			),
		);

		$this->assertSame(
			0,
			InCartQuantity::for_product( $items, 10 ),
			'Invalid lines and non-numeric quantities should not contribute or cause PHP diagnostics'
		);
	}

	/**
	 * @testdox Should preserve integer and fractional quantity sums.
	 */
	public function test_preserves_integer_and_fractional_quantity_sums(): void {
		$this->assertSame(
			3,
			InCartQuantity::for_product(
				array(
					array(
						'id'       => 10,
						'quantity' => 1,
					),
					array(
						'id'       => 10,
						'quantity' => 2,
					),
				),
				10
			),
			'An integer-only sum should remain an integer'
		);
		$this->assertSame(
			3.5,
			InCartQuantity::for_product(
				array(
					array(
						'id'       => 10,
						'quantity' => 1.5,
					),
					array(
						'id'       => 10,
						'quantity' => 2,
					),
				),
				10
			),
			'Fractional quantity should not be truncated'
		);
	}
}
