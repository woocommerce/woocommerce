<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Utilities;

use Automattic\WooCommerce\Internal\Utilities\InCartQuantity;
use WC_Unit_Test_Case;

/**
 * Tests for the Internal\Utilities\InCartQuantity class.
 */
class InCartQuantityTest extends WC_Unit_Test_Case {

	/**
	 * Remove the published `woocommerce` state, which the base class does not reset.
	 */
	public function tearDown(): void {
		try {
			$interactivity = wp_interactivity();
			$property      = new \ReflectionProperty( $interactivity, 'state_data' );
			$property->setAccessible( true );
			$state_data = $property->getValue( $interactivity );
			unset( $state_data['woocommerce'] );
			$property->setValue( $interactivity, $state_data );
		} finally {
			parent::tearDown();
		}
	}

	/**
	 * @testdox Should sum the product's published cart lines and skip declared children and empty entries.
	 */
	public function test_sums_product_lines_and_skips_declared_children(): void {
		wp_interactivity_state(
			'woocommerce',
			array(
				'cart' => array(
					'items' => array(
						array(),
						array(
							'id'       => 10,
							'quantity' => 1.5,
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
					),
				),
			)
		);

		$this->assertSame(
			10.5,
			InCartQuantity::for_product( 10 ),
			'Lines without a non-empty parent key should count, keeping fractional quantities; child lines, empty entries, and other products should not'
		);
	}

	/**
	 * @testdox Should return zero when the published cart has no items, as after a failed cart hydration.
	 */
	public function test_returns_zero_without_published_cart_items(): void {
		wp_interactivity_state( 'woocommerce', array( 'cart' => array() ) );

		$this->assertSame( 0, InCartQuantity::for_product( 10 ), 'A cart without items should count nothing' );
	}
}
