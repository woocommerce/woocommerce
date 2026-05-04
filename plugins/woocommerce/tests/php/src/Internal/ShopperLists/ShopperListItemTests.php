<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\ShopperLists;

use Automattic\WooCommerce\Internal\ShopperLists\ShopperListItem;
use WC_Unit_Test_Case;

/**
 * Unit tests for ShopperListItem.
 */
class ShopperListItemTests extends WC_Unit_Test_Case {
	/**
	 * @var \WC_Product
	 */
	private $product;

	/**
	 * Set up.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->product = \WC_Helper_Product::create_simple_product(
			true,
			array(
				'name'          => 'Item SUT Product',
				'regular_price' => 19.99,
			)
		);
	}

	/**
	 * Tear down.
	 */
	public function tearDown(): void {
		if ( $this->product ) {
			$this->product->delete( true );
		}
		parent::tearDown();
	}

	/**
	 * @testdox from_product should build an item from a live product, snapshotting the title.
	 */
	public function test_from_product_builds_item_from_live_product(): void {
		$item = ShopperListItem::from_product( $this->product->get_id(), array(), 3 );

		$this->assertInstanceOf( ShopperListItem::class, $item );
		$arr = $item->to_array();
		$this->assertSame( $this->product->get_title(), $arr['product_title_at_save'] );
		$this->assertSame( 3, $arr['quantity'], 'Quantity should reflect the value passed to from_product.' );
	}

	/**
	 * @testdox from_product should default quantity to 1 and coerce zero/negative values up to 1.
	 */
	public function test_from_product_normalizes_quantity_floor(): void {
		$default = ShopperListItem::from_product( $this->product->get_id() );
		$this->assertInstanceOf( ShopperListItem::class, $default );
		$this->assertSame( 1, $default->to_array()['quantity'] );

		$zero = ShopperListItem::from_product( $this->product->get_id(), array(), 0 );
		$this->assertInstanceOf( ShopperListItem::class, $zero );
		$this->assertSame( 1, $zero->to_array()['quantity'] );

		$negative = ShopperListItem::from_product( $this->product->get_id(), array(), -5 );
		$this->assertInstanceOf( ShopperListItem::class, $negative );
		$this->assertSame( 1, $negative->to_array()['quantity'] );
	}

	/**
	 * @testdox from_product should return null when the product can't be resolved.
	 */
	public function test_from_product_returns_null_for_missing_product(): void {
		$this->assertNull( ShopperListItem::from_product( 99999999 ) );
	}

	/**
	 * @testdox to_array round-trips through from_array.
	 */
	public function test_round_trips_through_from_array(): void {
		$original = ShopperListItem::from_product( $this->product->get_id() );
		$rebuilt  = ShopperListItem::from_array( $original->to_array() );

		$this->assertSame( $original->to_array(), $rebuilt->to_array() );
	}

	/**
	 * @testdox from_product validates the variation array against the variation product, like cart does.
	 */
	public function test_from_variation_validates_against_variation_product(): void {
		$variable = \WC_Helper_Product::create_variation_product();

		$find = function ( array $attrs ) use ( $variable ): int {
			foreach ( $variable->get_children() as $variation_id ) {
				$expected = wc_get_product_variation_attributes( (int) $variation_id );
				if ( empty( array_diff_assoc( $attrs, $expected ) ) ) {
					return (int) $variation_id;
				}
			}
			$this->fail( 'No variation matched the requested attribute set.' );
		};

		$all_specific = $find(
			array(
				'attribute_pa_size'   => 'huge',
				'attribute_pa_colour' => 'red',
				'attribute_pa_number' => '0',
			)
		);
		$any_number   = $find(
			array(
				'attribute_pa_size'   => 'huge',
				'attribute_pa_colour' => 'blue',
				'attribute_pa_number' => '',
			)
		);

		// Specific attrs: server fills them in even when the caller passes nothing.
		$variation = ShopperListItem::from_product( $all_specific, array() )->to_array()['variation'];
		$this->assertSame( 'huge', $variation['attribute_pa_size'] );
		$this->assertSame( 'red', $variation['attribute_pa_colour'] );
		$this->assertSame( '0', $variation['attribute_pa_number'] );

		// Specific attrs: client value mismatching the variation is rejected.
		try {
			ShopperListItem::from_product( $all_specific, array( 'attribute_pa_colour' => 'blue' ) );
			$this->fail( 'Expected mismatched specific value to throw.' );
		} catch ( \InvalidArgumentException $e ) {
			$this->addToAssertionCount( 1 );
		}

		// "Any" slot: missing client value is rejected.
		try {
			ShopperListItem::from_product( $any_number, array() );
			$this->fail( 'Expected missing any-slot value to throw.' );
		} catch ( \InvalidArgumentException $e ) {
			$this->addToAssertionCount( 1 );
		}

		// "Any" slot: a value present on the parent is accepted and stored.
		$variation = ShopperListItem::from_product( $any_number, array( 'attribute_pa_number' => '2' ) )->to_array()['variation'];
		$this->assertSame( '2', $variation['attribute_pa_number'] );

		// "Any" slot: a value not in the parent's slugs is rejected.
		try {
			ShopperListItem::from_product( $any_number, array( 'attribute_pa_number' => '99' ) );
			$this->fail( 'Expected invalid any-slot value to throw.' );
		} catch ( \InvalidArgumentException $e ) {
			$this->addToAssertionCount( 1 );
		}
	}
}
