<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\Fulfillments;

use Automattic\WooCommerce\Internal\DataStores\Fulfillments\FulfillmentsDataStore;
use Automattic\WooCommerce\Internal\Fulfillments\FulfillmentsSettings;
use WC_Order;
use WC_Order_Item_Product;
use WC_Unit_Test_Case;

/**
 * Class FulfillmentsSettingsTest
 *
 * Tests for the FulfillmentsSettings class.
 */
class FulfillmentsSettingsTest extends WC_Unit_Test_Case {

	/**
	 * Set up the test environment.
	 */
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		update_option( 'woocommerce_feature_fulfillments_enabled', 'yes' );
		$controller = wc_get_container()->get( \Automattic\WooCommerce\Internal\Fulfillments\FulfillmentsController::class );
		$controller->register();
		$controller->initialize_fulfillments();
	}

	/**
	 * Tear down the test environment.
	 */
	public static function tearDownAfterClass(): void {
		update_option( 'woocommerce_feature_fulfillments_enabled', 'no' );
		parent::tearDownAfterClass();
	}

	/**
	 * Tests if the hooks are added correctly in the constructor.
	 */
	public function test_hooks_added() {
		$fulfillments_settings = wc_get_container()->get( FulfillmentsSettings::class );

		// Check if the admin_init filter is added.
		$this->assertNotFalse( has_filter( 'admin_init', array( $fulfillments_settings, 'init_settings_auto_fulfill' ) ) > 0 );

		// Check if the order status hooks are added.
		$this->assertNotFalse( has_action( 'woocommerce_order_status_processing', array( $fulfillments_settings, 'auto_fulfill_items_on_processing' ) ) > 0 );
		$this->assertNotFalse( has_action( 'woocommerce_order_status_completed', array( $fulfillments_settings, 'auto_fulfill_items_on_completed' ) ) > 0 );
	}

	/**
	 * Tests the add_auto_fulfill_settings method.
	 */
	public function test_add_auto_fulfill_settings() {
		$settings = array(
			array(
				'type' => 'sectionend',
				'id'   => 'catalog_options',
			),
		);

		$fulfillments_settings = wc_get_container()->get( FulfillmentsSettings::class );
		$modified_settings     = $fulfillments_settings->add_auto_fulfill_settings( $settings, '' );

		$this->assertCount( 5, $modified_settings );
		$this->assertEquals( 'catalog_options', $modified_settings[0]['id'] );
		$this->assertEquals( 'sectionend', $modified_settings[0]['type'] );
		$this->assertEquals( 'auto_fulfill_options', $modified_settings[1]['id'] );
		$this->assertEquals( 'title', $modified_settings[1]['type'] );
		$this->assertEquals( 'auto_fulfill_downloadable', $modified_settings[2]['id'] );
		$this->assertEquals( 'checkbox', $modified_settings[2]['type'] );
		$this->assertEquals( 'auto_fulfill_virtual', $modified_settings[3]['id'] );
		$this->assertEquals( 'checkbox', $modified_settings[3]['type'] );
		$this->assertEquals( 'auto_fulfill_options', $modified_settings[4]['id'] );
		$this->assertEquals( 'sectionend', $modified_settings[4]['type'] );
	}

	/**
	 * Tests the auto_fulfill_items_on_processing method when an order doesn't exist.
	 */
	public function test_auto_fulfill_items_on_processing_bails_out_if_no_order_exists() {
		$fulfillments_settings = wc_get_container()->get( FulfillmentsSettings::class );
		$mock_order            = $this->createMock( WC_Order::class );
		$mock_order->expects( $this->never() )->method( 'get_items' );
		// Simulate an order status change without an order.
		$fulfillments_settings->auto_fulfill_items_on_processing( 0, null );
	}

	/**
	 * Tests the auto_fulfill_items_on_processing method with an order that has no items.
	 */
	public function test_auto_fulfill_items_on_processing_bails_out_if_order_has_no_items() {
		$hook_called = false;
		add_filter(
			'woocommerce_fulfillments_auto_fulfill_products',
			function ( $products ) use ( &$hook_called ) {
				$hook_called = true;
				return $products;
			}
		);
		$fulfillments_settings = wc_get_container()->get( FulfillmentsSettings::class );
		$mock_order            = $this->createMock( WC_Order::class );
		$mock_order->expects( $this->once() )->method( 'get_items' )->willReturn( array() );
		// Simulate an order status change with an order that has no items.
		$fulfillments_settings->auto_fulfill_items_on_processing( 0, $mock_order );
		$this->assertFalse( $hook_called, 'Hook should not be called if there are no items.' );
	}

	/**
	 * Data provider for auto_fulfill_items_on_processing test.
	 *
	 * @return array
	 */
	public function auto_fulfill_items_on_processing_data_provider() {
		return array(
			/**
			 * auto fulfill downloadable items
			 * auto fulfill virtual items
			 * auto fulfill product IDs
			 * expected fulfillments count
			 */
			array( false, false, array(), 0, 0 ),
			array( true, false, array(), 1, 1 ),
			array( false, true, array(), 1, 1 ),
			array( true, true, array(), 1, 2 ),
			array( false, false, array( 123 ), 1, 1 ),
			array( false, false, array( 124 ), 0, 0 ),
			array( true, true, array( 789 ), 1, 3 ),
		);
	}

	/**
	 * Test the auto_fulfill_items_on_processing method with an order that has downloadable items.
	 *
	 * @param bool  $auto_fulfill_downloadable Whether to auto-fulfill downloadable items.
	 * @param bool  $auto_fulfill_virtual Whether to auto-fulfill virtual items.
	 * @param array $auto_fulfill_products Products to auto-fulfill.
	 * @param int   $fulfillments_expected Expected number of fulfillments.
	 * @param int   $fulfilled_items_count Expected number of fulfilled items.
	 *
	 * @dataProvider auto_fulfill_items_on_processing_data_provider
	 */
	public function test_auto_fulfill_items_on_processing_calls_hook_with_item_and_setting_combinatinos(
		$auto_fulfill_downloadable = true,
		$auto_fulfill_virtual = false,
		$auto_fulfill_products = array(),
		$fulfillments_expected = 0,
		$fulfilled_items_count = 0
	) {
		$hook_called = false;
		add_filter(
			'woocommerce_fulfillments_auto_fulfill_products',
			function ( $products ) use ( &$hook_called, $auto_fulfill_products ) {
				$hook_called = true;
				$products    = array_merge( $products, $auto_fulfill_products );
				return $products;
			}
		);
		$fulfillments_settings = wc_get_container()->get( FulfillmentsSettings::class );
		$mock_order            = $this->createMock( WC_Order::class );

		$mock_items = array();

		// Add a downloadable item to the order.
		$mock_downloadable_item         = $this->createMock( WC_Order_Item_Product::class );
		$mock_downloadable_item_product = $this->createMock( \WC_Product::class );
		$mock_downloadable_item_product->method( 'is_downloadable' )->willReturn( true );
		$mock_downloadable_item_product->method( 'is_virtual' )->willReturn( false );
		$mock_downloadable_item_product->method( 'get_id' )->willReturn( 123 );
		$mock_downloadable_item->method( 'get_product' )->willReturn( $mock_downloadable_item_product );
		$mock_downloadable_item->method( 'get_id' )->willReturn( 1123 );
		$mock_downloadable_item->method( 'get_quantity' )->willReturn( 2 );

		$mock_items[] = $mock_downloadable_item;

		// Add a virtual item to the order.
		$mock_virtual_item         = $this->createMock( WC_Order_Item_Product::class );
		$mock_virtual_item_product = $this->createMock( \WC_Product::class );
		$mock_virtual_item_product->method( 'is_downloadable' )->willReturn( false );
		$mock_virtual_item_product->method( 'is_virtual' )->willReturn( true );
		$mock_virtual_item_product->method( 'get_id' )->willReturn( 456 );
		$mock_virtual_item->method( 'get_product' )->willReturn( $mock_virtual_item_product );
		$mock_virtual_item->method( 'get_id' )->willReturn( 2456 );
		$mock_virtual_item->method( 'get_quantity' )->willReturn( 3 );
		$mock_items[] = $mock_virtual_item;

		// Add a regular item to the order.
		// This item should not trigger auto-fulfillment.
		$mock_regular_item         = $this->createMock( WC_Order_Item_Product::class );
		$mock_regular_item_product = $this->createMock( \WC_Product::class );
		$mock_regular_item_product->method( 'is_downloadable' )->willReturn( false );
		$mock_regular_item_product->method( 'is_virtual' )->willReturn( false );
		$mock_regular_item_product->method( 'get_id' )->willReturn( 789 );
		$mock_regular_item->method( 'get_product' )->willReturn( $mock_regular_item_product );
		$mock_regular_item->method( 'get_id' )->willReturn( 3789 );
		$mock_regular_item->method( 'get_quantity' )->willReturn( 4 );
		$mock_items[] = $mock_regular_item;

		// Set the options for auto-fulfill settings.
		update_option( 'auto_fulfill_downloadable', $auto_fulfill_downloadable ? 'yes' : 'no' );
		update_option( 'auto_fulfill_virtual', $auto_fulfill_virtual ? 'yes' : 'no' );

		$mock_order->expects( $this->exactly( 2 ) )->method( 'get_items' )->willReturn( $mock_items );

		// Simulate an order status change with an order that has items.
		$fulfillments_settings->auto_fulfill_items_on_processing( 123, $mock_order );

		$this->assertTrue( $hook_called, 'Hook should be called if there are items.' );

		$data_store   = new FulfillmentsDataStore();
		$fulfillments = $data_store->read_fulfillments( WC_Order::class, '123' );
		$this->assertCount( $fulfillments_expected, $fulfillments, 'Fulfillments count does not match expected.' );

		if ( $fulfillments_expected > 0 ) {
			$fulfillment = reset( $fulfillments );
			$this->assertEquals( 'fulfilled', $fulfillment->get_status(), 'Fulfillment status should be fulfilled.' );
			$this->assertTrue( $fulfillment->get_is_fulfilled(), 'Fulfillment should be marked as fulfilled.' );

			$items = $fulfillment->get_items();
			$this->assertCount( $fulfilled_items_count, $items, 'Fulfillment items count does not match expected.' );
		}
	}

	/**
	 * Tests the auto_fulfill_items_on_completed method when order doesn't exist.
	 */
	public function test_auto_fulfill_items_on_completed_bails_out_if_no_order_exists() {
		$fulfillments_settings = wc_get_container()->get( FulfillmentsSettings::class );
		$mock_order            = $this->createMock( WC_Order::class );
		$mock_order->expects( $this->never() )->method( 'get_items' );
		// Simulate an order status change without an order.
		$fulfillments_settings->auto_fulfill_items_on_completed( 0, null );
	}

	/**
	 * Tests the auto_fulfill_items_on_completed method with an order that has no items.
	 */
	public function test_auto_fulfill_items_on_completed_bails_out_if_order_has_no_items() {
		$fulfillments_settings = wc_get_container()->get( FulfillmentsSettings::class );
		$mock_order            = $this->createMock( WC_Order::class );
		$mock_order->expects( $this->once() )->method( 'get_items' )->willReturn( array() );
		$mock_order->expects( $this->never() )->method( 'get_meta' );
		// Simulate an order status change with an order that has no items.
		$fulfillments_settings->auto_fulfill_items_on_completed( 0, $mock_order );
	}

	/**
	 * Tests the auto_fulfill_items_on_completed method with an order that has items.
	 */
	public function test_auto_fulfill_items_on_completed_calls_hook_with_item_and_setting_combinations() {
		$mock_sut = $this->getMockBuilder( \Automattic\WooCommerce\Internal\Fulfillments\FulfillmentsSettings::class )
			->onlyMethods( array( 'auto_fulfill_items_on_processing' ) )
			->getMock();
		$mock_sut->register();
		$mock_sut->expects( $this->once() )
			->method( 'auto_fulfill_items_on_processing' );
		$mock_order = $this->createMock( WC_Order::class );
		$mock_item  = $this->createMock( WC_Order_Item_Product::class );
		$mock_order->expects( $this->once() )
			->method( 'get_items' )
			->willReturn( array( $mock_item ) );
		$mock_sut->auto_fulfill_items_on_completed( 123, $mock_order );
	}
}
