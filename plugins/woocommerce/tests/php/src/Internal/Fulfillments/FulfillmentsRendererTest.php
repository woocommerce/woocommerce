<?php declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Fulfillments;

use Automattic\WooCommerce\Internal\DataStores\Fulfillments\FulfillmentsDataStore;
use Automattic\WooCommerce\Internal\Fulfillments\Fulfillment;
use Automattic\WooCommerce\Internal\Fulfillments\FulfillmentsRenderer;
use WC_Order;

/**
 * Tests for Fulfillment object.
 */
class FulfillmentsRendererTest extends \WC_Unit_Test_Case {

	/**
	 * Test hooks.
	 */
	public function test_hooks() {
		$renderer = new FulfillmentsRenderer();
		$this->assertNotFalse( has_filter( 'manage_woocommerce_page_wc-orders_columns', array( $renderer, 'add_fulfillment_columns' ) ) );
		$this->assertNotFalse( has_action( 'manage_woocommerce_page_wc-orders_custom_column', array( $renderer, 'render_fulfillment_column_row_data' ) ) );
		$this->assertNotFalse( has_action( 'admin_footer', array( $renderer, 'render_fulfillment_drawer_slot' ) ) );
		$this->assertNotFalse( has_action( 'admin_enqueue_scripts', array( $renderer, 'load_components' ) ) );
	}

	/**
	 * Test the add_fulfillment_columns method.
	 */
	public function test_add_fulfillment_columns() {
		$renderer = new FulfillmentsRenderer();
		$columns  = array(
			'order_status' => 'Order Status',
		);
		$result   = $renderer->add_fulfillment_columns( $columns );
		$this->assertArrayHasKey( 'fulfillment_status', $result );
		$this->assertArrayHasKey( 'shipment_tracking', $result );
		$this->assertArrayHasKey( 'shipment_provider', $result );
	}

	/**
	 * Test the render_fulfillment_column_row_data method.
	 */
	public function test_render_fulfillment_column_row_data_uses_cache() {
		// Mock the FulfillmentsDataStore class.
		$fulfillments_data_store = $this->createMock( FulfillmentsDataStore::class );

		$fulfillment = new Fulfillment();
		$fulfillment->set_entity_type( WC_Order::class );
		$fulfillment->set_entity_id( '1' );
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
		$fulfillment->set_is_fulfilled( true );
		$fulfillment->set_status( 'Fulfilled' );
		$fulfillment->save();

		/**
		 * @var TestingContainer $container
		 */
		$container = wc_get_container();
		$container->replace( FulfillmentsDataStore::class, $fulfillments_data_store );

		$fulfillments_data_store
			->expects( $this->once() )
			->method( 'read_fulfillments' )
			->with( WC_Order::class, '1' )
			->willReturn( array( $fulfillment ) );

		$renderer = new FulfillmentsRenderer();
		$order    = $this->createMock( \WC_Order::class );
		$order->method( 'get_id' )->willReturn( 1 );

		ob_start();
		$renderer->render_fulfillment_column_row_data( 'fulfillment_status', $order );
		$renderer->render_fulfillment_column_row_data( 'shipment_tracking', $order );
		$renderer->render_fulfillment_column_row_data( 'shipment_provider', $order );

		$output = ob_get_clean();
		$this->assertStringContainsString( 'Fulfilled', $output );
		$this->assertStringContainsString( '123456789', $output );
		$this->assertStringContainsString( 'UPS', $output );
		$this->assertStringContainsString( "<a href='#' class='fulfillments-trigger' data-order-id='1' title='" . esc_attr__( 'View Fulfillments', 'woocommerce' ) . "'>", $output );
		$this->assertStringContainsString( "<svg width='16' height='16' viewBox='0 0 12 14' fill='none' xmlns='http://www.w3.org/2000/svg'>", $output );
		$this->assertStringContainsString( "<path d='M11.8333 2.83301L9.33329 0.333008L2.24996 7.41634L1.41663 10.7497L4.74996 9.91634L11.8333 2.83301ZM5.99996 12.4163H0.166626V13.6663H5.99996V12.4163Z' fill='#3858E9'/>", $output );
		$this->assertStringContainsString( '</svg>', $output );
		$this->assertStringContainsString( '</a>', $output );

		// Cleanup.
		$container->reset_replacement( FulfillmentsDataStore::class );
	}

	/**
	 * Test the render_fulfillment_column_row_data method with no fulfillments.
	 */
	public function test_render_fulfillment_column_row_data_no_fulfillments() {
		$renderer = new FulfillmentsRenderer();
		$order    = $this->createMock( \WC_Order::class );
		$order->method( 'get_id' )->willReturn( 1 );

		ob_start();
		$renderer->render_fulfillment_column_row_data( 'fulfillment_status', $order );
		$renderer->render_fulfillment_column_row_data( 'shipment_tracking', $order );
		$renderer->render_fulfillment_column_row_data( 'shipment_provider', $order );

		$output = ob_get_clean();
		$this->assertStringContainsString( 'Unfulfilled', $output );
		$this->assertStringNotContainsString( '123456789', $output );
		$this->assertStringNotContainsString( 'UPS', $output );
	}

	/**
	 * Test the render_fulfillment_drawer_slot method.
	 */
	public function test_render_fulfillment_drawer_slot_doesnt_render_without_current_screen() {
		$renderer = new FulfillmentsRenderer();
		set_current_screen( null );
		ob_start();
		$renderer->render_fulfillment_drawer_slot();
		$output = ob_get_clean();
		$this->assertStringNotContainsString( '<div id="wc_order_fulfillments_panel_container"></div>', $output );
	}

	/**
	 * Test the render_fulfillment_drawer_slot method.
	 */
	public function test_render_fulfillment_drawer_slot_doesnt_render_on_other_pages() {
		$renderer = new FulfillmentsRenderer();
		set_current_screen( 'dashboard' );
		ob_start();
		$renderer->render_fulfillment_drawer_slot();
		$output = ob_get_clean();
		$this->assertStringNotContainsString( '<div id="wc_order_fulfillments_panel_container"></div>', $output );
	}

	/**
	 * Test the render_fulfillment_drawer_slot method.
	 */
	public function test_render_fulfillment_drawer_slot_renders_on_orders_page() {
		$renderer = new FulfillmentsRenderer();
		set_current_screen( 'woocommerce_page_wc-orders' );
		ob_start();
		$renderer->render_fulfillment_drawer_slot();
		$output = ob_get_clean();
		$this->assertStringContainsString( '<div id="wc_order_fulfillments_panel_container"></div>', $output );
	}
}
