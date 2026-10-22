<?php declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Admin\Features\Fulfillments;

use Automattic\WooCommerce\Admin\Features\Fulfillments\DataStore\FulfillmentsDataStore;
use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;
use Automattic\WooCommerce\Admin\Features\Fulfillments\Fulfillment;
use Automattic\WooCommerce\Admin\Features\Fulfillments\FulfillmentsRenderer;
use Automattic\WooCommerce\Admin\Features\Fulfillments\Providers\AmazonLogisticsShippingProvider;
use Automattic\WooCommerce\Admin\Features\Fulfillments\Providers\DHLShippingProvider;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper;
use Automattic\WooCommerce\Tests\Admin\Features\Fulfillments\Helpers\FulfillmentsHelper;
use WC_Helper_Order;
use WC_Helper_Product;
use WC_Order;
use WP_Query;

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
	 * Original value of the fulfillments feature flag.
	 *
	 * @var mixed
	 */
	private static $original_fulfillments_flag;

	/**
	 * Set up the test environment.
	 */
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		self::$original_fulfillments_flag = get_option( 'woocommerce_feature_fulfillments_enabled' );
		update_option( 'woocommerce_feature_fulfillments_enabled', 'yes' );
		$controller = wc_get_container()->get( \Automattic\WooCommerce\Admin\Features\Fulfillments\FulfillmentsController::class );
		$controller->register();
		$controller->initialize_fulfillments();
	}

	/**
	 * Tear down the test environment.
	 */
	public static function tearDownAfterClass(): void {
		if ( false === self::$original_fulfillments_flag ) {
			delete_option( 'woocommerce_feature_fulfillments_enabled' );
		} else {
			update_option( 'woocommerce_feature_fulfillments_enabled', self::$original_fulfillments_flag );
		}
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
		$fulfillment->set_tracking_number( '123456789' );
		$fulfillment->set_tracking_url( 'https://example.com/track/123456789' );
		$fulfillment->set_shipment_provider( 'UPS' );
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
		$this->assertStringContainsString( '<mark class="fulfillment-status fulfillments-trigger"', $output );
		$this->assertStringContainsString( 'data-order-id="' . $order->get_id() . '"', $output );
		$this->assertStringContainsString( "<a href='#' class='fulfillments-trigger' data-order-id='" . $order->get_id() . "' title='" . esc_attr__( 'View Fulfillments', 'woocommerce' ) . "'>", $output );
		$this->assertStringContainsString( '<svg ', $output );
		$this->assertStringContainsString( '<path ', $output );
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
	 * Test bulk action for fulfilling orders with all items in an unfulfilled fulfillment.
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
	 * Test bulk action for fulfilling orders with all items in a fulfilled fulfillment.
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

	/**
	 * @testdox Should list only unrecognized shipping providers under the "Other" filter.
	 * @testWith [true]
	 *           [false]
	 *
	 * @param bool $is_known_providers Whether the known shipping provider list is populated.
	 */
	public function test_other_shipping_provider_filter_excludes_fulfillments_without_a_provider( bool $is_known_providers ): void {
		add_filter(
			'woocommerce_fulfillment_shipping_providers',
			static function () use ( $is_known_providers ): array {
				return $is_known_providers ? array( DHLShippingProvider::class ) : array();
			},
			999
		);

		$unknown_provider_order_id = $this->create_order_with_shipment_provider( 'totally-unknown-carrier' );
		$empty_provider_order_id   = $this->create_order_with_shipment_provider( '' );
		$known_provider_order_id   = $this->create_order_with_shipment_provider( 'dhl' );

		$_GET['shipping_provider'] = '__other__';
		$args                      = $this->renderer->filter_orders_by_shipping_provider( array() );
		unset( $_GET['shipping_provider'] );

		$this->assertContains(
			$unknown_provider_order_id,
			$args['post__in'],
			'A provider outside the known list should match the "Other" filter'
		);
		$this->assertNotContains(
			$empty_provider_order_id,
			$args['post__in'],
			'A fulfillment saved without a shipping provider should not match the "Other" filter'
		);

		if ( $is_known_providers ) {
			$this->assertNotContains(
				$known_provider_order_id,
				$args['post__in'],
				'A known provider should not match the "Other" filter'
			);
		} else {
			$this->assertContains(
				$known_provider_order_id,
				$args['post__in'],
				'With no known providers, any non-empty provider should match the "Other" filter'
			);
		}
	}

	/**
	 * Create an order with a single fulfillment carrying the given shipping provider.
	 *
	 * @param string $shipment_provider The provider key to store on the fulfillment.
	 * @return int The order ID.
	 */
	private function create_order_with_shipment_provider( string $shipment_provider ): int {
		$order = OrderHelper::create_order( get_current_user_id() );

		FulfillmentsHelper::create_fulfillment(
			array(
				'entity_type' => WC_Order::class,
				'entity_id'   => (string) $order->get_id(),
				'status'      => 'fulfilled',
			),
			array(
				'_shipment_provider' => $shipment_provider,
				'_items'             => array(
					array(
						'item_id' => 1,
						'qty'     => 1,
					),
				),
			)
		);

		return $order->get_id();
	}

	/**
	 * @testdox Filtering by a specific provider returns only the orders whose fulfillment uses it.
	 */
	public function test_get_order_ids_matches_specific_provider(): void {
		$this->seed_fulfillment( 101, 'acme-couriers' );
		$this->seed_fulfillment( 102, 'other-co' );

		$this->assertSame( array( 101 ), $this->get_order_ids( 'acme-couriers' ) );
	}

	/**
	 * @testdox The filter finds an order whose provider was saved through the fulfillment CRUD path.
	 */
	public function test_get_order_ids_matches_provider_saved_through_crud(): void {
		$order = WC_Helper_Order::create_order( get_current_user_id() );

		$fulfillment = new Fulfillment();
		$fulfillment->set_entity_type( WC_Order::class );
		$fulfillment->set_entity_id( (string) $order->get_id() );
		$fulfillment->set_shipment_provider( 'acme-couriers' );
		$fulfillment->set_items(
			array(
				array(
					'item_id' => 1,
					'qty'     => 1,
				),
			)
		);
		$fulfillment->set_status( 'unfulfilled' );
		$fulfillment->save();

		$this->assertSame(
			array( $order->get_id() ),
			$this->get_order_ids( 'acme-couriers' ),
			'The filter must match the provider meta the data store actually persists.'
		);

		WC_Helper_Order::delete_order( $order->get_id() );
	}

	/**
	 * @testdox A soft-deleted fulfillment is excluded from the provider filter.
	 */
	public function test_get_order_ids_excludes_soft_deleted_fulfillment(): void {
		$this->seed_fulfillment( 103, 'acme-couriers' );
		$this->seed_fulfillment( 104, 'acme-couriers', true );

		$this->assertSame( array( 103 ), $this->get_order_ids( 'acme-couriers' ) );
	}

	/**
	 * @testdox A fulfillment whose provider meta row is soft-deleted is excluded from the filter.
	 */
	public function test_get_order_ids_excludes_soft_deleted_meta(): void {
		$this->seed_fulfillment( 105, 'acme-couriers' );
		$this->seed_fulfillment( 106, 'acme-couriers', false, true );

		$this->assertSame( array( 105 ), $this->get_order_ids( 'acme-couriers' ) );
	}

	/**
	 * @testdox The __other__ sentinel returns orders whose provider is not a known built-in or custom key.
	 */
	public function test_get_order_ids_other_excludes_known_providers(): void {
		// Register a known provider so the query uses the NOT IN branch rather than the
		// empty-known-keys fallback, which would otherwise return every providered order.
		$register_known = function ( array $providers ): array {
			$providers[] = AmazonLogisticsShippingProvider::class;
			return $providers;
		};
		add_filter( 'woocommerce_fulfillment_shipping_providers', $register_known );

		try {
			$this->seed_fulfillment( 201, 'amazon-logistics' );
			$this->seed_fulfillment( 202, 'ghost-provider' );

			$this->assertSame( array( 202 ), $this->get_order_ids( '__other__' ) );
		} finally {
			remove_filter( 'woocommerce_fulfillment_shipping_providers', $register_known );
		}
	}

	/**
	 * @testdox The HPOS orders query is filtered to the matching order ids when no post__in exists yet.
	 */
	public function test_filter_orders_sets_post_in_when_absent(): void {
		$this->seed_fulfillment( 301, 'acme-couriers' );

		$this->with_provider_param(
			'acme-couriers',
			function () {
				$args = $this->renderer->filter_orders_by_shipping_provider( array() );
				$this->assertSame( array( 301 ), $args['post__in'] );
			}
		);
	}

	/**
	 * @testdox The HPOS filter intersects an existing post__in with the provider matches.
	 */
	public function test_filter_orders_intersects_existing_post_in(): void {
		$this->seed_fulfillment( 401, 'acme-couriers' );
		$this->seed_fulfillment( 402, 'acme-couriers' );

		$this->with_provider_param(
			'acme-couriers',
			function () {
				$args = $this->renderer->filter_orders_by_shipping_provider( array( 'post__in' => array( 401, 999 ) ) );
				$this->assertSame( array( 401 ), array_values( $args['post__in'] ), 'Only the id present in both sets should remain.' );
			}
		);
	}

	/**
	 * @testdox The HPOS filter returns a no-match sentinel when the provider has no orders.
	 */
	public function test_filter_orders_returns_zero_when_no_match(): void {
		$this->with_provider_param(
			'provider-with-no-orders',
			function () {
				$args = $this->renderer->filter_orders_by_shipping_provider( array() );
				$this->assertSame( array( 0 ), $args['post__in'], 'An unmatched provider should force an empty result set.' );
			}
		);
	}

	/**
	 * @testdox The HPOS filter leaves the query arguments untouched when no provider is requested.
	 */
	public function test_filter_orders_is_noop_without_param(): void {
		$original = array( 'post__in' => array( 7, 8 ) );

		$this->assertSame( $original, $this->renderer->filter_orders_by_shipping_provider( $original ) );
	}

	/**
	 * @testdox The legacy orders query is filtered to the matching order ids.
	 */
	public function test_filter_legacy_orders_sets_post_in(): void {
		$this->seed_fulfillment( 501, 'acme-couriers' );

		$query = new WP_Query();
		$query->set( 'post_type', 'shop_order' );

		$this->with_main_query(
			$query,
			function () use ( $query ) {
				$this->with_provider_param(
					'acme-couriers',
					function () use ( $query ) {
						$this->renderer->filter_legacy_orders_by_shipping_provider( $query );
						$this->assertSame( array( 501 ), $query->get( 'post__in' ) );
					}
				);
			}
		);
	}

	/**
	 * @testdox The legacy filter does nothing when the query is not the admin main orders query.
	 */
	public function test_filter_legacy_orders_skips_non_main_query(): void {
		$this->seed_fulfillment( 601, 'acme-couriers' );

		// A secondary query: not registered as the main query, so the guard must bail.
		$query = new WP_Query();
		$query->set( 'post_type', 'shop_order' );

		// Set the admin screen so is_admin() passes and the bail is isolated to the is_main_query() guard.
		$original_screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		set_current_screen( 'edit-shop_order' );

		try {
			$this->with_provider_param(
				'acme-couriers',
				function () use ( $query ) {
					$this->renderer->filter_legacy_orders_by_shipping_provider( $query );
					$this->assertEmpty( $query->get( 'post__in' ), 'A non-main query should not be filtered.' );
				}
			);
		} finally {
			if ( $original_screen ) {
				$GLOBALS['current_screen'] = $original_screen; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			} else {
				unset( $GLOBALS['current_screen'] );
			}
		}
	}

	/**
	 * Invoke the private provider-to-order-ids query.
	 *
	 * @param string $provider The provider key or the __other__ sentinel.
	 * @return array<int> The matching order ids.
	 */
	private function get_order_ids( string $provider ): array {
		$method = new \ReflectionMethod( FulfillmentsRenderer::class, 'get_order_ids_by_shipping_provider' );
		$method->setAccessible( true );

		return $method->invoke( $this->renderer, $provider );
	}

	/**
	 * Run a callback with the shipping_provider request parameter set, then restore it.
	 *
	 * @param string   $provider The provider value to place in the request.
	 * @param callable $callback The assertions to run while the parameter is set.
	 */
	private function with_provider_param( string $provider, callable $callback ): void {
		$_GET['shipping_provider'] = $provider;
		try {
			$callback();
		} finally {
			unset( $_GET['shipping_provider'] );
		}
	}

	/**
	 * Run a callback with the given query registered as the admin main orders query, then restore state.
	 *
	 * @param WP_Query $query    The query to treat as the main query.
	 * @param callable $callback The assertions to run while the query is active.
	 */
	private function with_main_query( WP_Query $query, callable $callback ): void {
		$original_main_query = $GLOBALS['wp_the_query'] ?? null;
		$original_screen     = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		// is_main_query() compares against $wp_the_query, and is_admin() reads the current screen;
		// the test restores both in the finally block.
		$GLOBALS['wp_the_query'] = $query; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		set_current_screen( 'edit-shop_order' );
		try {
			$callback();
		} finally {
			$GLOBALS['wp_the_query'] = $original_main_query; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			if ( $original_screen ) {
				$GLOBALS['current_screen'] = $original_screen; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			} else {
				unset( $GLOBALS['current_screen'] );
			}
		}
	}

	/**
	 * Insert a fulfillment referencing a provider slug, as the filter query expects.
	 *
	 * @param int    $entity_id     The order id the fulfillment belongs to.
	 * @param string $provider_slug The provider slug the fulfillment references.
	 * @param bool   $deleted       Whether the fulfillment row is soft-deleted.
	 * @param bool   $meta_deleted  Whether the provider meta row is soft-deleted.
	 */
	private function seed_fulfillment( int $entity_id, string $provider_slug, bool $deleted = false, bool $meta_deleted = false ): void {
		global $wpdb;
		$now = current_time( 'mysql', true );

		$wpdb->insert(
			$wpdb->prefix . 'wc_order_fulfillments',
			array(
				'entity_type'  => WC_Order::class,
				'entity_id'    => $entity_id,
				'status'       => 'unfulfilled',
				'is_fulfilled' => 0,
				'date_updated' => $now,
				'date_deleted' => $deleted ? $now : null,
			)
		);

		// Column names of a custom fulfillments table, not a slow postmeta query.
		$wpdb->insert(
			$wpdb->prefix . 'wc_order_fulfillment_meta',
			array(
				'fulfillment_id' => (int) $wpdb->insert_id,
				'meta_key'       => '_shipment_provider', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'     => wp_json_encode( $provider_slug ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'date_updated'   => $now,
				'date_deleted'   => $meta_deleted ? $now : null,
			)
		);
	}
}
