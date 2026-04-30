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
	 * @testdox from_product should build an item from a live product, snapshotting title/price.
	 */
	public function test_from_product_builds_item_from_live_product(): void {
		$item = ShopperListItem::from_product( $this->product->get_id(), array(), 3 );

		$this->assertInstanceOf( ShopperListItem::class, $item );
		$arr = $item->to_array();
		$this->assertSame( $this->product->get_title(), $arr['product_title_at_save'] );
		$this->assertSame( (string) $this->product->get_price(), $arr['price_at_save'] );
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
	}

	/**
	 * @testdox from_product should return null when the product can't be resolved.
	 */
	public function test_from_product_returns_null_for_missing_product(): void {
		$this->assertNull( ShopperListItem::from_product( 99999999 ) );
	}

	/**
	 * @testdox Same product+variation should always produce the same key, regardless of variation key order.
	 */
	public function test_key_is_stable_under_variation_reordering(): void {
		$id    = $this->product->get_id();
		$first = ShopperListItem::from_product(
			$id,
			array(
				'attribute_size'  => 'L',
				'attribute_color' => 'red',
			)
		);
		$other = ShopperListItem::from_product(
			$id,
			array(
				'attribute_color' => 'red',
				'attribute_size'  => 'L',
			)
		);

		$this->assertSame( $first->get_key(), $other->get_key(), 'Variation attribute order must not affect the key.' );
	}

	/**
	 * @testdox Different variation values should produce different keys.
	 */
	public function test_key_varies_with_variation_values(): void {
		$id  = $this->product->get_id();
		$red = ShopperListItem::from_product( $id, array( 'attribute_color' => 'red' ) );
		$blu = ShopperListItem::from_product( $id, array( 'attribute_color' => 'blue' ) );

		$this->assertNotSame( $red->get_key(), $blu->get_key(), 'Different variation values must produce different keys.' );
	}

	/**
	 * @testdox to_array round-trips through from_array.
	 */
	public function test_round_trips_through_from_array(): void {
		$original = ShopperListItem::from_product( $this->product->get_id() );
		$rebuilt  = ShopperListItem::from_array( $original->to_array() );

		$this->assertSame( $original->to_array(), $rebuilt->to_array() );
	}
}
