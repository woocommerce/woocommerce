<?php

declare( strict_types=1 );

use Automattic\WooCommerce\Internal\CostOfGoodsSold\CogsAwareUnitTestSuiteTrait;

/**
 * Tests for the Abstract_WC_Order_Item_Type_Data_Store  class.
 *
 * @package WooCommerce\Tests\Order_Item
 */
class WC_Order_Item_Data_Store_Test extends WC_Unit_Test_Case {

	use CogsAwareUnitTestSuiteTrait;

	/**
	 * The instance of the order items data store to use.
	 *
	 * @var WC_Data_Store
	 */
	private static WC_Data_Store $order_item_data_store;

	/**
	 * Runs before all the tests of the class.
	 */
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		self::$order_item_data_store = WC_Data_Store::load( 'order-item' );
	}

	/**
	 * Runs after each test.
	 */
	public function tearDown(): void {
		parent::tearDown();
		$this->disable_cogs_feature();

		remove_all_filters( 'woocommerce_save_order_item_cogs_value' );
		remove_all_filters( 'woocommerce_load_order_item_cogs_value' );
	}

	/**
	 * @testdox The Cost of Goods Sold value for order items is not persisted when the item is saved if the feature is disabled.
	 */
	public function test_cogs_is_not_persisted_when_cogs_is_disabled() {
		$item = new WC_Order_Item_Product();
		$this->expect_doing_it_wrong_cogs_disabled( 'WC_Order_Item::set_cogs_value' );
		$item->set_cogs_value( 12.34 );
		$item->save();

		$this->assertEquals( '', self::$order_item_data_store->get_metadata( $item->get_id(), '_cogs_value', true ) );
	}

	/**
	 * @testdox The Cost of Goods Sold value for order items is not persisted when the item is saved if the feature is enabled but the item doesn't manage it.
	 */
	public function test_cogs_is_not_persisted_when_cogs_is_enabled_but_item_has_no_cogs() {
		$this->enable_cogs_feature();

		// phpcs:disable Squiz.Commenting
		$item = new class() extends WC_Order_Item_Product {
			public function has_cogs(): bool {
				return false;
			}
		};
		// phpcs:enable Squiz.Commenting

		$item->set_cogs_value( 12.34 );
		$item->save();

		$this->assertEquals( '', self::$order_item_data_store->get_metadata( $item->get_id(), '_cogs_value', true ) );
	}

	/**
	 * @testdox The Cost of Goods Sold value for order items is persisted when the item is saved, only when the value is not zero, if the feature is enabled and the item manages it.
	 */
	public function test_cogs_is_persisted_only_when_value_is_non_zero() {
		$this->enable_cogs_feature();

		$item = new WC_Order_Item_Product();
		$item->set_cogs_value( 12.34 );
		$item->save();

		$this->assertEquals( 12.34, (float) self::$order_item_data_store->get_metadata( $item->get_id(), '_cogs_value', true ) );

		$item->set_cogs_value( 0 );
		$item->save();

		$this->assertEquals( '', self::$order_item_data_store->get_metadata( $item->get_id(), '_cogs_value', true ) );
	}

	/**
	 * @testdox It's possible to modify the Cost of Goods Sold value that gets persisted for an order item using the 'woocommerce_save_order_item_cogs_value' filter, returning null suppresses the saving.
	 *
	 * @testWith [56.78, "56.78"]
	 *           [null, "12.34"]
	 *
	 * @param mixed  $filter_return_value The value that the filter will return.
	 * @param string $expected_saved_value The value that is expected to be persisted after the save attempt.
	 */
	public function test_saved_cogs_value_can_be_altered_via_filter_with_null_meaning_dont_save( $filter_return_value, string $expected_saved_value ) {
		$received_filter_cogs_value = null;
		$received_filter_item       = null;

		$this->enable_cogs_feature();

		$item = new WC_Order_Item_Product();
		$item->set_cogs_value( 12.34 );
		$item->save();

		add_filter(
			'woocommerce_save_order_item_cogs_value',
			function ( $cogs_value, $item ) use ( &$received_filter_cogs_value, &$received_filter_item, $filter_return_value ) {
				$received_filter_cogs_value = $cogs_value;
				$received_filter_item       = $item;
				return $filter_return_value;
			},
			10,
			2
		);

		$item->set_cogs_value( 56.78 );
		$item->save();

		$this->assertEquals( 56.78, $received_filter_cogs_value );
		$this->assertSame( $item, $received_filter_item );
		$this->assertEquals( $expected_saved_value, self::$order_item_data_store->get_metadata( $item->get_id(), '_cogs_value', true ) );
	}

	/**
	 * @testdox The Cost of Goods Sold value for order items is not retrieved from database when the item is loaded if the feature is disabled.
	 */
	public function test_cogs_is_not_loaded_when_cogs_is_disabled() {
		$item = new WC_Order_Item_Product();
		$item->save();

		self::$order_item_data_store->add_metadata( $item->get_id(), '_cogs_value', '12.34', true );

		$item2 = new WC_Order_Item_Product( $item->get_id() );

		$this->expect_doing_it_wrong_cogs_disabled( 'WC_Order_Item::get_cogs_value' );
		$this->assertEquals( 0, $item2->get_cogs_value() );
	}

	/**
	 * @testdox The Cost of Goods Sold value for order items is not retrieved from database when the item is loaded if the feature is enabled but the item doesn't manage it.
	 */
	public function test_cogs_is_not_loaded_when_cogs_is_enabled_but_item_has_no_cogs() {
		$this->enable_cogs_feature();

		$item = new WC_Order_Item_Product();
		$item->save();

		self::$order_item_data_store->add_metadata( $item->get_id(), '_cogs_value', '12.34', true );

		// phpcs:disable Squiz.Commenting
		$item2 = new class($item->get_id()) extends WC_Order_Item_Product {
			public function has_cogs(): bool {
				return false;
			}
		};
		// phpcs:enable Squiz.Commenting

		$this->assertEquals( 0, $item2->get_cogs_value() );
	}

	/**
	 * @testdox The Cost of Goods Sold value for order items is retrieved from database when the item is loaded if the feature is enabled and the item manages it.
	 */
	public function test_cogs_is_loaded_when_cogs_is_enabled_and_item_has_cogs() {
		$this->enable_cogs_feature();

		$item = new WC_Order_Item_Product();
		$item->save();

		self::$order_item_data_store->add_metadata( $item->get_id(), '_cogs_value', '12.34', true );

		$item2 = new WC_Order_Item_Product( $item->get_id() );

		$this->assertEquals( 12.34, $item2->get_cogs_value() );
	}

	/**
	 * @testdox It's possible to modify the Cost of Goods Sold value that gets loaded from the database for an order item using the 'woocommerce_load_order_item_cogs_value' filter.
	 */
	public function test_loaded_cogs_value_can_be_modified_via_filter() {
		$received_filter_cogs_value = null;
		$received_filter_item       = null;

		$this->enable_cogs_feature();

		$item = new WC_Order_Item_Product();
		$item->save();
		self::$order_item_data_store->add_metadata( $item->get_id(), '_cogs_value', '12.34', true );

		add_filter(
			'woocommerce_load_order_item_cogs_value',
			function ( $cogs_value, $item ) use ( &$received_filter_cogs_value, &$received_filter_item ) {
				$received_filter_cogs_value = $cogs_value;
				$received_filter_item       = $item;
				return 56.78;
			},
			10,
			2
		);

		$item2 = new WC_Order_Item_Product( $item->get_id() );

		$this->assertEquals( 12.34, $received_filter_cogs_value );
		$this->assertSame( $item2, $received_filter_item );

		$this->assertEquals( 56.78, $item2->get_cogs_value() );
	}
}
