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
	 * @testdox from_product should build an item from a live product, snapshotting title/price and persisting item_data.
	 */
	public function test_from_product_builds_item_from_live_product(): void {
		$item = ShopperListItem::from_product(
			$this->product->get_id(),
			array(),
			array(
				array(
					'key'   => 'source',
					'value' => 'manual',
				),
			)
		);

		$this->assertInstanceOf( ShopperListItem::class, $item );
		$arr = $item->to_array();
		$this->assertSame( $this->product->get_title(), $arr['product_title_at_save'] );
		$this->assertSame( (string) $this->product->get_price(), $arr['price_at_save'] );
		$this->assertSame( 1, $arr['quantity'], 'Quantity is hardcoded to 1 in v1.' );
		$this->assertSame( 'manual', $arr['item_data'][0]['value'] );
	}

	/**
	 * @testdox from_product should return null when the product can't be resolved.
	 */
	public function test_from_product_returns_null_for_missing_product(): void {
		$this->assertNull( ShopperListItem::from_product( 99999999 ) );
	}

	/**
	 * @testdox The same product+item_data should always produce the same key, and different item_data should produce different keys.
	 */
	public function test_key_is_deterministic_and_varies_by_inputs(): void {
		$first  = ShopperListItem::from_product( $this->product->get_id() );
		$second = ShopperListItem::from_product( $this->product->get_id() );
		$noted  = ShopperListItem::from_product(
			$this->product->get_id(),
			array(),
			array(
				array(
					'key'   => 'note',
					'value' => 'gift',
				),
			)
		);

		$this->assertSame( $first->key(), $second->key(), 'Same inputs must produce the same key.' );
		$this->assertNotSame( $first->key(), $noted->key(), 'Different item_data must produce different keys.' );
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
