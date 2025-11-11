<?php declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Fulfillments;

use Automattic\WooCommerce\Internal\DataStores\Fulfillments\FulfillmentsDataStore;
use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;
use Automattic\WooCommerce\Internal\Fulfillments\Fulfillment;
use Automattic\WooCommerce\Internal\Fulfillments\FulfillmentsRenderer;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper;
use WC_Helper_Order;
use WC_Helper_Product;
use WC_Order;

/**
 * Tests for Fulfillment object.
 */
class FulfillmentsRendererTest extends \WC_Unit_Test_Case {

	/**
	 * FulfillmentsRenderer instance.
	 *
	 * @var FulfillmentsRenderer
	 */
	private FulfillmentsRenderer $renderer;

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
	 * Set up the test case.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->renderer = wc_get_container()->get( FulfillmentsRenderer::class );
		$this->renderer->register();
	}

	/**
	 * Test the add_fulfillment_columns method.
	 */
	public function test_add_fulfillment_columns() {
		$this->renderer = wc_get_container()->get( FulfillmentsRenderer::class );
		$columns        = array(
			'order_status' => 'Order Status',
		);
		$result         = $this->renderer->add_fulfillment_columns( $columns );
		$this->assertArrayHasKey( 'fulfillment_status', $result );
		$this->assertArrayHasKey( 'shipment_tracking', $result );
		$this->assertArrayHasKey( 'shipment_provider', $result );
	}

	/**
	 * Test the render_fulfillment_column_row_data method.
	 */
	public function test_render_fulfillment_column_row_data_uses_cache() {
		$order = OrderHelper::create_order( get_current_user_id() );
		$order->update_meta_data( '_fulfillment_status', 'fulfilled' );
		$order->save();

		$fulfillment = new Fulfillment();
		$fulfillment->set_entity_type( WC_Order::class );
		$fulfillment->set_entity_id( (string) $order->get_id() );
		$fulfillment->add_meta_data( '_tracking_number', '123456789' );
		$fulfillment->add_meta_data( '_tracking_url', 'https://example.com/track/123456789' );
		$fulfillment->add_meta_data( '_shipment_provider', 'UPS' );
		$fulfillment->set_items(
			array(
				array(
					'item_id' => 1,
					'qty'     => 2,
				),
				array(
					'item_id' => 2,
					'qty'     => 1,
				),
			)
		);
		$fulfillment->set_status( 'fulfilled' );
		$fulfillment->save();

		$this->renderer = wc_get_container()->get( FulfillmentsRenderer::class );

		ob_start();
		$this->renderer->render_fulfillment_column_row_data( 'fulfillment_status', $order );
		$this->renderer->render_fulfillment_column_row_data( 'shipment_tracking', $order );
		$this->renderer->render_fulfillment_column_row_data( 'shipment_provider', $order );

		$output = ob_get_clean();
		$this->assertStringContainsString( 'Fulfilled', $output );
		$this->assertStringContainsString( '123456789', $output );
		$this->assertStringContainsString( 'UPS', $output );
		$this->assertStringContainsString( "<a href='#' class='fulfillments-trigger' data-order-id='" . $order->get_id() . "' title='" . esc_attr__( 'View Fulfillments', 'woocommerce' ) . "'>", $output );
		$this->assertStringContainsString( "<svg width='16' height='16' viewBox='0 0 12 14' xmlns='http://www.w3.org/2000/svg'>", $output );
		$this->assertStringContainsString( "<path d='M11.8333 2.83301L9.33329 0.333008L2.24996 7.41634L1.41663 10.7497L4.74996 9.91634L11.8333 2.83301ZM5.99996 12.4163H0.166626V13.6663H5.99996V12.4163Z' />", $output );
		$this->assertStringContainsString( '</svg>', $output );
		$this->assertStringContainsString( '</a>', $output );
	}

	/**
	 * Test the render_fulfillment_column_row_data method with no fulfillments.
	 */
	public function test_render_fulfillment_column_row_data_no_fulfillments() {
		$this->renderer = wc_get_container()->get( FulfillmentsRenderer::class );
		$order          = $this->createMock( \WC_Order::class );
		$order->method( 'get_id' )->willReturn( 1 );
		$order->method( 'meta_exists' )->willReturn( true );
		$order->method( 'get_meta' )->with( '_fulfillment_status' )->willReturn( 'unfulfilled' );

		ob_start();
		$this->renderer->render_fulfillment_column_row_data( 'fulfillment_status', $order );
		$this->renderer->render_fulfillment_column_row_data( 'shipment_tracking', $order );
		$this->renderer->render_fulfillment_column_row_data( 'shipment_provider', $order );

		$output = ob_get_clean();
		$this->assertStringContainsString( 'Unfulfilled', $output );
		$this->assertStringNotContainsString( '123456789', $output );
		$this->assertStringNotContainsString( 'UPS', $output );
	}

	/**
	 * Test the render_fulfillment_drawer_slot method.
	 */
	public function test_render_fulfillment_drawer_slot_doesnt_render_without_current_screen() {
		$this->renderer = wc_get_container()->get( FulfillmentsRenderer::class );
		set_current_screen( null );
		ob_start();
		$this->renderer->render_fulfillment_drawer_slot();
		$output = ob_get_clean();
		$this->assertStringNotContainsString( '<div id="wc_order_fulfillments_panel_container"></div>', $output );
	}

	/**
	 * Test the render_fulfillment_drawer_slot method.
	 */
	public function test_render_fulfillment_drawer_slot_doesnt_render_on_other_pages() {
		$this->renderer = wc_get_container()->get( FulfillmentsRenderer::class );
		set_current_screen( 'dashboard' );
		ob_start();
		$this->renderer->render_fulfillment_drawer_slot();
		$output = ob_get_clean();
		$this->assertStringNotContainsString( '<div id="wc_order_fulfillments_panel_container"></div>', $output );
	}

	/**
	 * Test the render_fulfillment_drawer_slot method.
	 */
	public function test_render_fulfillment_drawer_slot_renders_on_orders_page() {
		$this->renderer = wc_get_container()->get( FulfillmentsRenderer::class );
		set_current_screen( 'woocommerce_page_wc-orders' );
		ob_start();
		$this->renderer->render_fulfillment_drawer_slot();
		$output = ob_get_clean();
		$this->assertStringContainsString( '<div id="wc_order_fulfillments_panel_container"></div>', $output );
	}

	/**
	 * Test the test_handle_fulfillment_bulk_actions method fulfill action on an order without any fulfillments.
	 */
	public function test_handle_fulfillment_bulk_actions_fulfill_new_order() {
		$this->renderer = wc_get_container()->get( FulfillmentsRenderer::class );
		$order          = WC_Helper_Order::create_order( get_current_user_id() );

		$this->renderer->handle_fulfillment_bulk_actions( 'dummy_redirect', 'fulfill', array( $order->get_id() ) );

		$fulfillments = wc_get_container()
		->get( FulfillmentsDataStore::class )
		->read_fulfillments( WC_Order::class, (string) $order->get_id() );

		$this->assertCount( 1, $fulfillments, 'Fulfillment was not created.' );
		$this->assertEquals( 'fulfilled', $fulfillments[0]->get_status(), 'Fulfillment status is not set to Fulfilled.' );
		$this->assertTrue( $fulfillments[0]->get_is_fulfilled(), 'Fulfillment is not marked as fulfilled.' );

		// Check that the fulfillment has all the items in the order.
		$items = $fulfillments[0]->get_items();
		$this->assertCount( count( $order->get_items() ), $items, 'Fulfillment items do not match order items.' );
		foreach ( $order->get_items() as $item_id => $item ) {
			$fulfillment_item = array_filter( $items, fn( $item ) => $item['item_id'] === $item_id );
			$this->assertNotEmpty( $fulfillment_item, 'Fulfillment does not contain item with ID ' . $item_id );
			$this->assertEquals( $item->get_quantity(), $fulfillment_item[0]['qty'], 'Fulfillment item quantity does not match order item quantity.' );
		}

		WC_Helper_Order::delete_order( $order->get_id() );
	}

	/**
	 * Test the test_handle_fulfillment_bulk_actions method fulfill action on an order with existing fulfillments.
	 */
	public function test_handle_fulfillment_bulk_actions_fulfill_with_partial_fulfillments() {
		$this->renderer = wc_get_container()->get( FulfillmentsRenderer::class );

		$order = WC_Helper_Order::create_order( get_current_user_id() );
		$order->add_item( WC_Helper_Product::create_simple_product(), 2 );
		$order->calculate_totals();

		$order_items = array_values( $order->get_items() );

		// Create an initial fulfillment with only one item.
		$fulfillment = new Fulfillment();
		$fulfillment->set_entity_type( WC_Order::class );
		$fulfillment->set_entity_id( (string) $order->get_id() );
		$fulfillment->set_status( 'unfulfilled' );
		$fulfillment->set_items(
			array(
				array(
					'item_id' => $order_items[0]->get_id(),
					'qty'     => 1,
				),
			)
		);
		$fulfillment->save();

		// Now fulfill the order again.
		$this->renderer->handle_fulfillment_bulk_actions( 'dummy_redirect', 'fulfill', array( $order->get_id() ) );

		$fulfillments = wc_get_container()
		->get( FulfillmentsDataStore::class )
		->read_fulfillments( WC_Order::class, (string) $order->get_id() );

		$this->assertCount( 2, $fulfillments, 'Fulfillment was not created.' );
		foreach ( $fulfillments as $fulfillment ) {
			$this->assertEquals( 'fulfilled', $fulfillment->get_status(), 'Fulfillment status is not set to Fulfilled.' );
			$this->assertTrue( $fulfillment->get_is_fulfilled(), 'Fulfillment is not marked as fulfilled.' );
		}

		WC_Helper_Order::delete_order( $order->get_id() );
	}

	/**
	 * Test bulk action for fulfilling orders with all items in an unfullfilled fulfillment.
	 */
	public function test_handle_fulfillment_bulk_actions_fulfill_all_items_in_unfulfilled_fulfillment() {
		$this->renderer = wc_get_container()->get( FulfillmentsRenderer::class );
		$order          = WC_Helper_Order::create_order( get_current_user_id() );

		// Add multiple items to the order.
		for ( $i = 0; $i < 3; $i++ ) {
			$product = WC_Helper_Product::create_simple_product();
			$order->add_item( $product, 2 );
		}
		$order->calculate_totals();

		// Fulfill the order without calling the bulk action first.
		$fulfillment = new Fulfillment();
		$fulfillment->set_entity_type( WC_Order::class );
		$fulfillment->set_entity_id( (string) $order->get_id() );
		$fulfillment->set_status( 'unfulfilled' );
		$fulfillment->set_items(
			array_map(
				function ( $item ) {
					return array(
						'item_id' => $item->get_id(),
						'qty'     => $item->get_quantity(),
					);
				},
				array_values( $order->get_items() )
			)
		);
		$fulfillment->save();

		// Fulfill the order with bulk action. There should be no change except the existing fulfillment status.
		$this->renderer->handle_fulfillment_bulk_actions( 'dummy_redirect', 'fulfill', array( $order->get_id() ) );

		$fulfillments = wc_get_container()
		->get( FulfillmentsDataStore::class )
		->read_fulfillments( WC_Order::class, (string) $order->get_id() );

		$this->assertCount( 1, $fulfillments, 'Fulfillment was not created.' );
		$this->assertEquals( $fulfillment->get_id(), $fulfillments[0]->get_id(), 'Fulfillment ID does not match.' );
		$this->assertEquals( 'fulfilled', $fulfillments[0]->get_status(), 'Fulfillment status is not set to Fulfilled.' );
		$this->assertTrue( $fulfillments[0]->get_is_fulfilled(), 'Fulfillment is not marked as fulfilled.' );

		WC_Helper_Order::delete_order( $order->get_id() );
	}

	/**
	 * Test bulk action for fulfilling orders with all items in a fullfilled fulfillment.
	 */
	public function test_handle_fulfillment_bulk_actions_fulfill_all_items_in_fulfilled_fulfillment() {
		$this->renderer = wc_get_container()->get( FulfillmentsRenderer::class );
		$order          = WC_Helper_Order::create_order( get_current_user_id() );

		// Add multiple items to the order.
		for ( $i = 0; $i < 3; $i++ ) {
			$product = WC_Helper_Product::create_simple_product();
			$order->add_item( $product, 2 );
		}
		$order->calculate_totals();

		// Fulfill the order without calling the bulk action first.
		$fulfillment = new Fulfillment();
		$fulfillment->set_entity_type( WC_Order::class );
		$fulfillment->set_entity_id( (string) $order->get_id() );
		$fulfillment->set_status( 'fulfilled' );
		$fulfillment->set_items(
			array_map(
				function ( $item ) {
					return array(
						'item_id' => $item->get_id(),
						'qty'     => $item->get_quantity(),
					);
				},
				array_values( $order->get_items() )
			)
		);
		$fulfillment->save();

		// Fulfill the order with bulk action. There should be no change since all items are fulfilled.
		$this->renderer->handle_fulfillment_bulk_actions( 'dummy_redirect', 'fulfill', array( $order->get_id() ) );

		$fulfillments = wc_get_container()
		->get( FulfillmentsDataStore::class )
		->read_fulfillments( WC_Order::class, (string) $order->get_id() );

		$this->assertCount( 1, $fulfillments, 'Fulfillment was not created.' );
		$this->assertEquals( $fulfillment->get_id(), $fulfillments[0]->get_id(), 'Fulfillment ID does not match.' );
		$this->assertEquals( 'fulfilled', $fulfillments[0]->get_status(), 'Fulfillment status is not set to Fulfilled.' );
		$this->assertTrue( $fulfillments[0]->get_is_fulfilled(), 'Fulfillment is not marked as fulfilled.' );

		WC_Helper_Order::delete_order( $order->get_id() );
	}

	/**
	 * Test that the load_components method doesn't render on other pages.
	 */
	public function test_load_components_doesnt_render_on_other_pages() {
		$renderer_mock = $this->getMockBuilder( FulfillmentsRenderer::class )
			->onlyMethods( array( 'should_render_fulfillment_drawer', 'register_fulfillments_assets' ) )
			->getMock();
		$renderer_mock->method( 'should_render_fulfillment_drawer' )->willReturn( false );
		$renderer_mock->method( 'register_fulfillments_assets' )->willReturnCallback(
			function () {
				wp_enqueue_script( 'wc-admin-fulfillments', 'dummy-path', array(), '1.0.0', array( 'in_footer' => false ) );
			}
		);

		ob_start();
		$renderer_mock->load_components();
		wp_print_scripts();
		$output = ob_get_clean();
		$this->assertStringNotContainsString( 'wc-admin-fulfillments-js', $output );
		$this->assertStringNotContainsString( 'var wcFulfillmentSettings', $output );
	}

	/**
	 * Test that the load_components method renders on the orders page.
	 */
	public function test_load_components_renders_on_orders_page() {
		$renderer_mock = $this->getMockBuilder( FulfillmentsRenderer::class )
			->onlyMethods( array( 'should_render_fulfillment_drawer', 'register_fulfillments_assets' ) )
			->getMock();
		$renderer_mock->method( 'should_render_fulfillment_drawer' )->willReturn( true );
		$renderer_mock->method( 'register_fulfillments_assets' )->willReturnCallback(
			function () {
				wp_enqueue_script( 'wc-admin-fulfillments', 'dummy-path', array(), '1.0.0', array( 'in_footer' => false ) );
			}
		);

		ob_start();
		$renderer_mock->load_components();
		wp_print_scripts();
		$output = ob_get_clean();
		$this->assertStringContainsString( 'wc-admin-fulfillments-js', $output );
		$this->assertStringContainsString( 'var wcFulfillmentSettings', $output );
	}
}
