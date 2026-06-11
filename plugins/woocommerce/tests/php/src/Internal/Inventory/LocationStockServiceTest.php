<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Inventory;

use Automattic\WooCommerce\Enums\ProductStockStatus;
use Automattic\WooCommerce\Internal\Inventory\InventoryController;
use Automattic\WooCommerce\Internal\Inventory\LocationStockInstaller;
use Automattic\WooCommerce\Internal\Inventory\LocationStockService;
use Automattic\WooCommerce\Internal\Orders\OrderNoteGroup;
use Automattic\WooCommerce\Internal\ProductFeed\Integrations\POSCatalog\ProductMapper;
use Automattic\WooCommerce\Internal\Utilities\DatabaseUtil;
use WC_Helper_Product;
use WC_Order;
use WC_Order_Item_Product;
use WC_Product;
use WC_REST_Unit_Test_Case;

/**
 * Tests for POS location stock.
 *
 * @covers \Automattic\WooCommerce\Internal\Inventory\InventoryController
 * @covers \Automattic\WooCommerce\Internal\Inventory\LocationStockInstaller
 * @covers \Automattic\WooCommerce\Internal\Inventory\LocationStockRestApiHooks
 * @covers \Automattic\WooCommerce\Internal\Inventory\LocationStockService
 */
class LocationStockServiceTest extends WC_REST_Unit_Test_Case {

	/**
	 * Service under test.
	 *
	 * @var LocationStockService
	 */
	private LocationStockService $service;

	/**
	 * Controller under test.
	 *
	 * @var InventoryController
	 */
	private InventoryController $controller;

	/**
	 * Installer under test.
	 *
	 * @var LocationStockInstaller
	 */
	private LocationStockInstaller $installer;

	/**
	 * Database utility.
	 *
	 * @var DatabaseUtil
	 */
	private DatabaseUtil $database_util;

	/**
	 * Original feature option value.
	 *
	 * @var mixed
	 */
	private $previous_feature_option;

	/**
	 * Original manage stock option value.
	 *
	 * @var mixed
	 */
	private $previous_manage_stock_option;

	/**
	 * Original table latch option value.
	 *
	 * @var mixed
	 */
	private $previous_tables_created_option;

	/**
	 * Original POS location latch option value.
	 *
	 * @var mixed
	 */
	private $previous_pos_location_created_option;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->previous_feature_option              = get_option( InventoryController::FEATURE_OPTION, null );
		$this->previous_manage_stock_option         = get_option( 'woocommerce_manage_stock', null );
		$this->previous_tables_created_option       = get_option( LocationStockInstaller::TABLES_CREATED_OPTION, null );
		$this->previous_pos_location_created_option = get_option( LocationStockInstaller::POS_LOCATION_CREATED_OPTION, null );

		update_option( InventoryController::FEATURE_OPTION, 'yes' );
		update_option( 'woocommerce_manage_stock', 'yes' );

		$this->service       = wc_get_container()->get( LocationStockService::class );
		$this->controller    = wc_get_container()->get( InventoryController::class );
		$this->installer     = wc_get_container()->get( LocationStockInstaller::class );
		$this->database_util = wc_get_container()->get( DatabaseUtil::class );

		$this->create_inventory_tables();
		$this->empty_inventory_tables();
		$this->service->ensure_pos_location();

		// The controller is a shared singleton whose feature-hooks latch survives the
		// whole run, while WP_UnitTestCase restores $wp_filter between tests. Reset the
		// latch so the feature hooks re-register for every test rather than only the first.
		$feature_hooks_registered = new \ReflectionProperty( InventoryController::class, 'feature_hooks_registered' );
		$feature_hooks_registered->setAccessible( true );
		$feature_hooks_registered->setValue( $this->controller, false );

		$this->controller->register_feature_hooks();

		wp_set_current_user(
			$this->factory->user->create(
				array(
					'role' => 'administrator',
				)
			)
		);
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		$this->restore_option( InventoryController::FEATURE_OPTION, $this->previous_feature_option );
		$this->restore_option( 'woocommerce_manage_stock', $this->previous_manage_stock_option );
		$this->restore_option( LocationStockInstaller::TABLES_CREATED_OPTION, $this->previous_tables_created_option );
		$this->restore_option( LocationStockInstaller::POS_LOCATION_CREATED_OPTION, $this->previous_pos_location_created_option );

		parent::tearDown();
	}

	/**
	 * @testdox Should not create inventory tables when the feature is disabled.
	 */
	public function test_tables_are_not_created_when_feature_is_disabled(): void {
		update_option( InventoryController::FEATURE_OPTION, 'no' );
		delete_option( LocationStockInstaller::TABLES_CREATED_OPTION );
		$this->drop_inventory_tables();

		$this->installer->maybe_create_db_tables();

		$this->assertFalse( $this->service->tables_exist(), 'Inventory tables should not exist when the feature is disabled.' );
		$this->assertSame( 'no', get_option( LocationStockInstaller::TABLES_CREATED_OPTION, 'no' ) );
	}

	/**
	 * @testdox Should verify dbDelta results and configure the POS location when the feature is enabled.
	 */
	public function test_tables_are_created_and_verified_when_feature_is_enabled(): void {
		delete_option( LocationStockInstaller::TABLES_CREATED_OPTION );
		$this->drop_inventory_tables();

		$this->installer->maybe_create_db_tables();

		$this->assertTrue( $this->service->tables_exist(), 'Inventory tables should exist after dbDelta runs.' );
		$this->assertSame( 'yes', get_option( LocationStockInstaller::TABLES_CREATED_OPTION ) );
		$this->assertSame( 'POS', $this->service->get_location( LocationStockService::LOCATION_POS )['name'] );
		$this->assertNull( $this->service->get_location( 'web' ), 'The POS-only milestone should not seed a web location row.' );
	}

	/**
	 * @testdox Should configure the POS location when the inventory tables already exist.
	 */
	public function test_existing_tables_configure_pos_location_when_feature_is_enabled(): void {
		update_option( LocationStockInstaller::TABLES_CREATED_OPTION, 'yes' );
		delete_option( LocationStockInstaller::POS_LOCATION_CREATED_OPTION );
		$this->remove_pos_location();

		$this->installer->maybe_create_db_tables();

		$this->assertSame( 'POS', $this->service->get_location( LocationStockService::LOCATION_POS )['name'] );
	}

	/**
	 * @testdox Should not change legacy _stock when setting POS stock.
	 */
	public function test_set_pos_location_stock_does_not_change_legacy_stock(): void {
		$product = $this->create_managed_stock_product();

		$this->service->set_location_stock( $product, LocationStockService::LOCATION_POS, 4 );

		$this->assertEquals( 4, $this->service->get_location_stock( $product, LocationStockService::LOCATION_POS ) );
		$this->assertEquals( 15, wc_get_product( $product->get_id() )->get_stock_quantity() );
	}

	/**
	 * @testdox Should not allow negative POS stock through the direct set path.
	 */
	public function test_set_pos_location_stock_clamps_negative_stock(): void {
		$product = $this->create_managed_stock_product();

		$this->service->set_location_stock( $product, LocationStockService::LOCATION_POS, -5 );

		$this->assertEquals( 0, $this->service->get_location_stock( $product, LocationStockService::LOCATION_POS ) );
		$this->assertEquals( 15, wc_get_product( $product->get_id() )->get_stock_quantity() );
	}

	/**
	 * @testdox Should not decrease POS stock below zero.
	 */
	public function test_decrease_pos_location_stock_does_not_go_negative(): void {
		$product = $this->create_managed_stock_product();
		$this->service->set_location_stock( $product, LocationStockService::LOCATION_POS, 1 );

		$this->assertNull( $this->service->decrease_location_stock( $product, LocationStockService::LOCATION_POS, 2 ) );
		$this->assertEquals( 1, $this->service->get_location_stock( $product, LocationStockService::LOCATION_POS ) );
	}

	/**
	 * @testdox Should leave POS stock unchanged when Core stock is edited.
	 */
	public function test_core_stock_edits_do_not_change_pos_location_stock(): void {
		$product = $this->create_managed_stock_product();
		$this->service->set_location_stock( $product, LocationStockService::LOCATION_POS, 4 );

		$product->set_stock_quantity( 20 );
		$product->save();

		$this->assertEquals( 4, $this->service->get_location_stock( $product, LocationStockService::LOCATION_POS ) );
		$this->assertEquals( 20, wc_get_product( $product->get_id() )->get_stock_quantity() );
	}

	/**
	 * @testdox Should update the product modified date when POS stock is set.
	 */
	public function test_set_pos_location_stock_updates_product_modified_date(): void {
		$product         = $this->create_managed_stock_product();
		$modified_before = $this->set_product_modified_date_to_past( $product );

		$this->service->set_location_stock( $product, LocationStockService::LOCATION_POS, 4 );

		$this->assert_product_modified_after( $product->get_id(), $modified_before );
		$this->assertEquals( 15, wc_get_product( $product->get_id() )->get_stock_quantity() );
	}

	/**
	 * @testdox Should update the product modified date when POS stock is decreased.
	 */
	public function test_decrease_pos_location_stock_updates_product_modified_date(): void {
		$product = $this->create_managed_stock_product();
		$this->service->set_location_stock( $product, LocationStockService::LOCATION_POS, 5 );
		$modified_before = $this->set_product_modified_date_to_past( $product );

		$this->service->decrease_location_stock( $product, LocationStockService::LOCATION_POS, 2 );

		$this->assert_product_modified_after( $product->get_id(), $modified_before );
		$this->assertEquals( 15, wc_get_product( $product->get_id() )->get_stock_quantity() );
	}

	/**
	 * @testdox Should update the variation modified date when variation-managed POS stock changes.
	 */
	public function test_variation_managed_location_stock_updates_variation_modified_date(): void {
		$variation = $this->create_variation_with_own_stock();
		$parent_id = $variation->get_parent_id();
		$parent    = wc_get_product( $parent_id );
		$this->assertInstanceOf( WC_Product::class, $parent );

		$this->service->set_location_stock( $variation, LocationStockService::LOCATION_POS, 6 );
		$variation_modified_before = $this->set_product_modified_date_to_past( $variation );
		$parent_modified_before    = $this->set_product_modified_date_to_past( $parent );

		$this->service->decrease_location_stock( $variation, LocationStockService::LOCATION_POS, 1 );

		$this->assert_product_modified_after( $variation->get_id(), $variation_modified_before );
		$this->assertSame( $parent_modified_before, $this->get_product_modified_timestamp( $parent_id ) );
	}

	/**
	 * @testdox Should update parent and variation modified dates when parent-managed variation POS stock changes.
	 */
	public function test_parent_managed_variation_location_stock_updates_parent_and_variation_modified_dates(): void {
		$parent    = $this->create_parent_managed_variation_product();
		$variation = wc_get_product( $parent->get_children()[0] );
		$this->assertInstanceOf( WC_Product::class, $variation );

		$this->service->set_location_stock( $variation, LocationStockService::LOCATION_POS, 6 );
		$parent_modified_before    = $this->set_product_modified_date_to_past( $parent );
		$variation_modified_before = $this->set_product_modified_date_to_past( $variation );

		$this->service->decrease_location_stock( $variation, LocationStockService::LOCATION_POS, 1 );

		$this->assert_product_modified_after( $parent->get_id(), $parent_modified_before );
		$this->assert_product_modified_after( $variation->get_id(), $variation_modified_before );
	}

	/**
	 * @testdox Should update parent-managed variation modified dates when parent POS stock changes.
	 */
	public function test_parent_location_stock_updates_parent_managed_variation_modified_dates(): void {
		$parent    = $this->create_parent_managed_variation_product();
		$variation = wc_get_product( $parent->get_children()[0] );
		$this->assertInstanceOf( WC_Product::class, $variation );

		$this->service->set_location_stock( $parent, LocationStockService::LOCATION_POS, 6 );
		$parent_modified_before    = $this->set_product_modified_date_to_past( $parent );
		$variation_modified_before = $this->set_product_modified_date_to_past( $variation );

		$this->service->decrease_location_stock( $parent, LocationStockService::LOCATION_POS, 1 );

		$this->assert_product_modified_after( $parent->get_id(), $parent_modified_before );
		$this->assert_product_modified_after( $variation->get_id(), $variation_modified_before );
	}

	/**
	 * @testdox Should follow the variation when the variation manages stock.
	 */
	public function test_variation_location_stock_uses_variation_when_variation_manages_stock(): void {
		$variation = $this->create_variation_with_own_stock();

		$this->service->set_location_stock( $variation, LocationStockService::LOCATION_POS, 6 );

		$this->assertEquals( 6, $this->service->get_location_stock( $variation, LocationStockService::LOCATION_POS ) );
		$this->assertEquals( 6, $this->service->get_location_stock( $variation->get_id(), LocationStockService::LOCATION_POS ) );
	}

	/**
	 * @testdox Should follow the parent product when the variation uses parent-managed stock.
	 */
	public function test_parent_managed_variation_location_stock_uses_parent_product(): void {
		$parent    = $this->create_parent_managed_variation_product();
		$variation = wc_get_product( $parent->get_children()[0] );

		$this->service->set_location_stock( $variation, LocationStockService::LOCATION_POS, 6 );

		$this->assertEquals( 6, $this->service->get_location_stock( $variation, LocationStockService::LOCATION_POS ) );
		$this->assertEquals( 6, $this->service->get_location_stock( $parent, LocationStockService::LOCATION_POS ) );
	}

	/**
	 * @testdox Should render a POS stock field for variation-managed stock.
	 */
	public function test_variation_location_stock_field_renders_for_variation_managed_stock(): void {
		$variation = $this->create_variation_with_own_stock();
		$this->service->set_location_stock( $variation, LocationStockService::LOCATION_POS, 6 );

		$output = $this->get_rendered_variation_location_fields( $variation );

		$this->assertStringContainsString( 'name="variable_inventory_location_stock[pos][0]"', $output );
		$this->assertStringContainsString( 'id="variable_inventory_stock_pos0"', $output );
		$this->assertStringContainsString( 'value="6"', $output );
	}

	/**
	 * @testdox Should not render a variation POS stock field when the feature is disabled.
	 */
	public function test_variation_location_stock_field_is_gated_by_feature_flag(): void {
		$variation = $this->create_variation_with_own_stock();
		update_option( InventoryController::FEATURE_OPTION, 'no' );

		$output = $this->get_rendered_variation_location_fields( $variation );

		$this->assertSame( '', $output );
	}

	/**
	 * @testdox Should render a variation POS stock field when stock is parent-managed.
	 */
	public function test_variation_location_stock_field_renders_for_parent_managed_stock(): void {
		$parent    = $this->create_parent_managed_variation_product();
		$variation = wc_get_product( $parent->get_children()[0] );
		$this->assertInstanceOf( WC_Product::class, $variation );
		$this->service->set_location_stock( $parent, LocationStockService::LOCATION_POS, 4 );

		$output = $this->get_rendered_variation_location_fields( $variation );

		$this->assertStringContainsString( 'name="variable_inventory_location_stock[pos][0]"', $output );
		$this->assertStringContainsString( 'id="variable_inventory_stock_pos0"', $output );
		$this->assertStringContainsString( 'value="0"', $output );
		$this->assertStringNotContainsString( 'value="4"', $output );
	}

	/**
	 * @testdox Should render the variation's own saved POS stock value before variation stock management is re-enabled.
	 */
	public function test_variation_location_stock_field_uses_variation_record_for_parent_managed_stock(): void {
		$variation = $this->create_variation_with_own_stock();
		$parent    = wc_get_product( $variation->get_parent_id() );
		$this->assertInstanceOf( WC_Product::class, $parent );

		$this->service->set_location_stock( $variation, LocationStockService::LOCATION_POS, 30 );

		$parent->set_manage_stock( true );
		$parent->save();
		$variation->set_manage_stock( false );
		$variation->save();

		$this->service->set_location_stock( $parent, LocationStockService::LOCATION_POS, 4 );
		$variation_id = $variation->get_id();
		$this->clear_product_cache( $variation );
		$variation = wc_get_product( $variation_id );
		$this->assertInstanceOf( WC_Product::class, $variation );

		$output = $this->get_rendered_variation_location_fields( $variation );

		$this->assertEquals( 4, $this->service->get_location_stock( $variation, LocationStockService::LOCATION_POS ) );
		$this->assertStringContainsString( 'value="30"', $output );
		$this->assertStringNotContainsString( 'value="4"', $output );
	}

	/**
	 * @testdox Should save POS stock for variation-managed stock without changing Core stock.
	 */
	public function test_save_variation_location_stock_field_updates_pos_stock_only(): void {
		$variation     = $this->create_variation_with_own_stock();
		$previous_post = $_POST; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Test fixture snapshots posted form data.

		try {
			$_POST['variable_inventory_location_stock'] = array(
				LocationStockService::LOCATION_POS => array(
					0 => '8',
				),
			);

			$this->controller->save_variation_location_fields( $variation, 0 );
		} finally {
			$_POST = $previous_post;
		}

		$this->assertEquals( 8, $this->service->get_location_stock( $variation, LocationStockService::LOCATION_POS ) );
		$this->assertEquals( 11, wc_get_product( $variation->get_id() )->get_stock_quantity() );
	}

	/**
	 * @testdox Should ignore posted variation POS stock when the feature is disabled.
	 */
	public function test_save_variation_location_stock_field_is_gated_by_feature_flag(): void {
		$variation = $this->create_variation_with_own_stock();
		$this->service->set_location_stock( $variation, LocationStockService::LOCATION_POS, 6 );
		update_option( InventoryController::FEATURE_OPTION, 'no' );

		$previous_post = $_POST; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Test fixture snapshots posted form data.

		try {
			$_POST['variable_inventory_location_stock'] = array(
				LocationStockService::LOCATION_POS => array(
					0 => '8',
				),
			);

			$this->controller->save_variation_location_fields( $variation, 0 );
		} finally {
			$_POST = $previous_post;
		}

		$this->assertEquals( 6, $this->service->get_location_stock( $variation, LocationStockService::LOCATION_POS ) );
	}

	/**
	 * @testdox Should not save hidden variation POS stock when stock is parent-managed.
	 */
	public function test_save_variation_location_stock_field_ignores_parent_managed_variations(): void {
		$parent    = $this->create_parent_managed_variation_product();
		$variation = wc_get_product( $parent->get_children()[0] );
		$this->service->set_location_stock( $parent, LocationStockService::LOCATION_POS, 4 );

		$previous_post = $_POST; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Test fixture snapshots posted form data.

		try {
			$_POST['variable_inventory_location_stock'] = array(
				LocationStockService::LOCATION_POS => array(
					0 => '8',
				),
			);

			$this->controller->save_variation_location_fields( $variation, 0 );
		} finally {
			$_POST = $previous_post;
		}

		$this->assertEquals( 4, $this->service->get_location_stock( $parent, LocationStockService::LOCATION_POS ) );
		$this->assertEquals( 4, $this->service->get_location_stock( $variation, LocationStockService::LOCATION_POS ) );
	}

	/**
	 * @testdox Should keep orders without POS inventory location on Core's legacy _stock path.
	 *
	 * @dataProvider order_created_via_provider
	 *
	 * @param string $created_via Order created_via value.
	 */
	public function test_orders_without_pos_inventory_location_stay_on_legacy_stock_path( string $created_via ): void {
		$product = $this->create_managed_stock_product();
		$this->service->set_location_stock( $product, LocationStockService::LOCATION_POS, 5 );

		$order = $this->create_order_for_product( $product, 2, $created_via );

		wc_reduce_stock_levels( $order );

		$items = $order->get_items();
		$item  = reset( $items );

		$this->assertEquals( 5, $this->service->get_location_stock( $product, LocationStockService::LOCATION_POS ) );
		$this->assertEquals( 13, wc_get_product( $product->get_id() )->get_stock_quantity() );
		$this->assertEquals( 2, $item->get_meta( '_reduced_stock', true ) );
		$this->assertEmpty( $item->get_meta( '_reduced_location_stock', true ) );
	}

	/**
	 * Data provider for legacy stock-routing created_via values without explicit POS inventory location.
	 *
	 * @return array<string,array{string}>
	 */
	public function order_created_via_provider(): array {
		return array(
			'block checkout'        => array( 'store-api' ),
			'shortcode checkout'    => array( 'checkout' ),
			'admin order'           => array( 'admin' ),
			'generic REST'          => array( 'rest-api' ),
			'REST integration'      => array( 'square' ),
			'POS created_via'       => array( 'point-of-sale' ),
			'POS REST created_via'  => array( 'pos-rest-api' ),
		);
	}

	/**
	 * @testdox Should route existing orders with explicit POS inventory location meta to POS stock.
	 */
	public function test_existing_order_inventory_location_meta_routes_order_to_pos_stock(): void {
		$product = $this->create_managed_stock_product();
		$this->service->set_location_stock( $product, LocationStockService::LOCATION_POS, 5 );

		$order = $this->create_order_for_product(
			$product,
			2,
			'rest-api',
			array( '_inventory_location' => LocationStockService::LOCATION_POS )
		);

		$this->controller->maybe_reduce_location_stock_levels( $order->get_id() );

		$order = wc_get_order( $order->get_id() );
		$items = $order->get_items();
		$item  = reset( $items );

		$this->assertEquals( 3, $this->service->get_location_stock( $product, LocationStockService::LOCATION_POS ) );
		$this->assertEquals( 15, wc_get_product( $product->get_id() )->get_stock_quantity() );
		$this->assertEquals( 2, $item->get_meta( '_reduced_location_stock', true ) );
		$this->assertEmpty( $item->get_meta( '_reduced_stock', true ) );
	}

	/**
	 * @testdox Should route explicit REST inventory_location=pos orders to POS stock.
	 */
	public function test_explicit_rest_inventory_location_routes_order_to_pos_stock(): void {
		$product = $this->create_managed_stock_product();
		$this->service->set_location_stock( $product, LocationStockService::LOCATION_POS, 5 );

		$response = $this->create_rest_order(
			array(
				'created_via'        => 'rest-api',
				'inventory_location' => LocationStockService::LOCATION_POS,
				'line_items'         => array(
					array(
						'product_id' => $product->get_id(),
						'quantity'   => 2,
					),
				),
			)
		);

		$this->assertEquals( 201, $response->get_status() );
		$this->assertEquals( 3, $this->service->get_location_stock( $product, LocationStockService::LOCATION_POS ) );
		$this->assertEquals( 15, wc_get_product( $product->get_id() )->get_stock_quantity() );
		$this->assert_order_used_pos_stock( wc_get_order( $response->get_data()['id'] ), 2 );
	}

	/**
	 * @testdox Should use created_via as a POS stock fallback when POS stock is managed separately.
	 */
	public function test_pos_rest_created_via_without_inventory_location_uses_pos_stock_when_pos_location_exists(): void {
		$product = $this->create_managed_stock_product();
		$this->service->set_location_stock( $product, LocationStockService::LOCATION_POS, 5 );

		$response = $this->create_rest_order(
			array(
				'created_via' => 'point-of-sale',
				'line_items'  => array(
					array(
						'product_id' => $product->get_id(),
						'quantity'   => 2,
					),
				),
			)
		);

		$this->assertEquals( 201, $response->get_status() );
		$this->assertEquals( 3, $this->service->get_location_stock( $product, LocationStockService::LOCATION_POS ) );
		$this->assertEquals( 15, wc_get_product( $product->get_id() )->get_stock_quantity() );
		$this->assert_order_used_pos_stock( wc_get_order( $response->get_data()['id'] ), 2 );
	}

	/**
	 * @testdox Should use Core stock for older POS REST requests when POS stock is not configured.
	 */
	public function test_pos_rest_created_via_without_inventory_location_uses_core_stock_when_pos_location_is_missing(): void {
		$product = $this->create_managed_stock_product();
		$this->remove_pos_location();

		$response = $this->create_rest_order(
			array(
				'created_via' => 'point-of-sale',
				'line_items'  => array(
					array(
						'product_id' => $product->get_id(),
						'quantity'   => 2,
					),
				),
			)
		);

		$this->assertEquals( 201, $response->get_status() );
		$this->assertEquals( 13, wc_get_product( $product->get_id() )->get_stock_quantity() );

		$order = wc_get_order( $response->get_data()['id'] );
		$items = $order->get_items();
		$item  = reset( $items );

		$this->assertEmpty( $order->get_meta( '_inventory_location', true ) );
		$this->assertEquals( 2, $item->get_meta( '_reduced_stock', true ) );
		$this->assertEmpty( $item->get_meta( '_reduced_location_stock', true ) );
	}

	/**
	 * @testdox Should reject explicit inventory_location=pos when the POS location is unavailable.
	 */
	public function test_explicit_rest_inventory_location_is_rejected_when_pos_location_is_missing(): void {
		$product = $this->create_managed_stock_product();
		$this->remove_pos_location();

		$response = $this->create_rest_order(
			array(
				'created_via'        => 'point-of-sale',
				'inventory_location' => LocationStockService::LOCATION_POS,
				'line_items'         => array(
					array(
						'product_id' => $product->get_id(),
						'quantity'   => 2,
					),
				),
			)
		);

		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'woocommerce_rest_invalid_inventory_location', $response->get_data()['code'] );
		$this->assertEquals( 15, wc_get_product( $product->get_id() )->get_stock_quantity() );
	}

	/**
	 * @testdox Should reject POS REST orders when POS stock would go below zero.
	 */
	public function test_pos_rest_order_rejects_insufficient_pos_stock(): void {
		$product = $this->create_managed_stock_product();
		$this->service->set_location_stock( $product, LocationStockService::LOCATION_POS, 1 );

		$response = $this->create_rest_order(
			array(
				'created_via'        => 'point-of-sale',
				'inventory_location' => LocationStockService::LOCATION_POS,
				'line_items'         => array(
					array(
						'product_id' => $product->get_id(),
						'quantity'   => 2,
					),
				),
			)
		);

		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 1, $this->service->get_location_stock( $product, LocationStockService::LOCATION_POS ) );
		$this->assertEquals( 15, wc_get_product( $product->get_id() )->get_stock_quantity() );
	}

	/**
	 * @testdox Should revert a REST order item quantity increase that would take POS stock below zero.
	 */
	public function test_pos_rest_order_item_increase_beyond_stock_is_reverted(): void {
		$product = $this->create_managed_stock_product();
		$this->service->set_location_stock( $product, LocationStockService::LOCATION_POS, 2 );

		$create = $this->create_rest_order(
			array(
				'created_via'        => 'point-of-sale',
				'inventory_location' => LocationStockService::LOCATION_POS,
				'line_items'         => array(
					array(
						'product_id' => $product->get_id(),
						'quantity'   => 2,
					),
				),
			)
		);

		$this->assertEquals( 201, $create->get_status() );
		$order_id = $create->get_data()['id'];

		// The paid POS order reduced its two units, leaving the POS bucket empty.
		$this->assertEquals( 0, $this->service->get_location_stock( $product, LocationStockService::LOCATION_POS ) );

		$order   = wc_get_order( $order_id );
		$items   = $order->get_items();
		$item    = reset( $items );
		$item_id = $item->get_id();

		$this->update_rest_order_item_quantity( $order_id, $item_id, 5 );

		$order = wc_get_order( $order_id );
		$items = $order->get_items();
		$item  = reset( $items );

		$this->assertEquals( 2, $item->get_quantity() );
		$this->assertEquals( 0, $this->service->get_location_stock( $product, LocationStockService::LOCATION_POS ) );
		$this->assertEquals( 15, wc_get_product( $product->get_id() )->get_stock_quantity() );
		$this->assert_order_has_error_note( $order, 'Not enough stock at POS' );
	}

	/**
	 * @testdox Should add an error note when stock is no longer available at reduction time.
	 */
	public function test_pos_order_reduce_failure_adds_error_note_without_negative_stock(): void {
		$product = $this->create_managed_stock_product();
		$this->service->set_location_stock( $product, LocationStockService::LOCATION_POS, 1 );
		$first_order  = $this->create_pos_order_for_product( $product, 1 );
		$second_order = $this->create_pos_order_for_product( $product, 1 );

		$this->controller->maybe_reduce_location_stock_levels( $first_order->get_id() );
		$this->controller->maybe_reduce_location_stock_levels( $second_order->get_id() );

		$second_order = wc_get_order( $second_order->get_id() );
		$items        = $second_order->get_items();
		$item         = reset( $items );

		$this->assertEquals( 0, $this->service->get_location_stock( $product, LocationStockService::LOCATION_POS ) );
		$this->assert_order_has_error_note( $second_order, 'Not enough stock at POS' );
		$this->assertEmpty( $item->get_meta( '_reduced_location_stock', true ) );
		$this->assertEmpty( $item->get_meta( '_reduced_stock', true ) );
	}

	/**
	 * @testdox Should reject unknown explicit REST inventory locations.
	 */
	public function test_rest_order_rejects_unknown_inventory_location(): void {
		$product = $this->create_managed_stock_product();
		$this->service->set_location_stock( $product, LocationStockService::LOCATION_POS, 5 );

		$response = $this->create_rest_order(
			array(
				'created_via'        => 'point-of-sale',
				'inventory_location' => 'warehouse',
				'line_items'         => array(
					array(
						'product_id' => $product->get_id(),
						'quantity'   => 2,
					),
				),
			)
		);

		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'woocommerce_rest_invalid_inventory_location', $response->get_data()['code'] );
		$this->assertEquals( 5, $this->service->get_location_stock( $product, LocationStockService::LOCATION_POS ) );
		$this->assertEquals( 15, wc_get_product( $product->get_id() )->get_stock_quantity() );
	}

	/**
	 * @testdox Should not accept known non-POS locations as REST order routes.
	 */
	public function test_rest_order_rejects_known_non_pos_inventory_location(): void {
		global $wpdb;

		$product = $this->create_managed_stock_product();
		$this->service->set_location_stock( $product, LocationStockService::LOCATION_POS, 5 );
		$wpdb->insert(
			$this->service->get_locations_table_name(),
			array(
				'slug'           => 'warehouse',
				'name'           => 'Warehouse',
				'created_at_gmt' => gmdate( 'Y-m-d H:i:s' ),
			),
			array( '%s', '%s', '%s' )
		);

		$response = $this->create_rest_order(
			array(
				'created_via'        => 'point-of-sale',
				'inventory_location' => 'warehouse',
				'line_items'         => array(
					array(
						'product_id' => $product->get_id(),
						'quantity'   => 2,
					),
				),
			)
		);

		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'woocommerce_rest_invalid_inventory_location', $response->get_data()['code'] );
		$this->assertEquals( 5, $this->service->get_location_stock( $product, LocationStockService::LOCATION_POS ) );
		$this->assertEquals( 15, wc_get_product( $product->get_id() )->get_stock_quantity() );
	}

	/**
	 * @testdox Should ignore the generic REST location parameter.
	 */
	public function test_generic_location_rest_param_does_not_route_order_to_pos_stock(): void {
		$product = $this->create_managed_stock_product();
		$this->service->set_location_stock( $product, LocationStockService::LOCATION_POS, 5 );

		$response = $this->create_rest_order(
			array(
				'created_via' => 'rest-api',
				'location'    => LocationStockService::LOCATION_POS,
				'line_items'  => array(
					array(
						'product_id' => $product->get_id(),
						'quantity'   => 2,
					),
				),
			)
		);

		$order = wc_get_order( $response->get_data()['id'] );
		$items = $order->get_items();
		$item  = reset( $items );

		$this->assertEquals( 201, $response->get_status() );
		$this->assertEquals( 5, $this->service->get_location_stock( $product, LocationStockService::LOCATION_POS ) );
		$this->assertEquals( 13, wc_get_product( $product->get_id() )->get_stock_quantity() );
		$this->assertEmpty( $order->get_meta( '_inventory_location', true ) );
		$this->assertEquals( 2, $item->get_meta( '_reduced_stock', true ) );
		$this->assertEmpty( $item->get_meta( '_reduced_location_stock', true ) );
	}

	/**
	 * @testdox Should restore POS order stock to the POS bucket only.
	 */
	public function test_pos_order_restore_returns_stock_to_pos_bucket_only(): void {
		$product = $this->create_managed_stock_product();
		$this->service->set_location_stock( $product, LocationStockService::LOCATION_POS, 5 );
		$order = $this->create_pos_order_for_product( $product, 2 );

		$this->controller->maybe_reduce_location_stock_levels( $order->get_id() );
		$this->controller->maybe_restore_location_stock_levels( $order->get_id() );

		$order = wc_get_order( $order->get_id() );
		$items = $order->get_items();
		$item  = reset( $items );

		$this->assertEquals( 5, $this->service->get_location_stock( $product, LocationStockService::LOCATION_POS ) );
		$this->assertEquals( 15, wc_get_product( $product->get_id() )->get_stock_quantity() );
		$this->assertEmpty( $item->get_meta( '_reduced_location_stock', true ) );
	}

	/**
	 * @testdox Should restock refunded POS items to the POS bucket and block Core restock.
	 */
	public function test_pos_refunded_item_restock_returns_stock_to_pos_bucket_only(): void {
		$product = $this->create_managed_stock_product();
		$this->service->set_location_stock( $product, LocationStockService::LOCATION_POS, 5 );
		$order = $this->create_pos_order_for_product( $product, 2 );

		$this->controller->maybe_reduce_location_stock_levels( $order->get_id() );

		$order = wc_get_order( $order->get_id() );
		$items = $order->get_items();
		$item  = reset( $items );

		wc_restock_refunded_items(
			$order,
			array(
				$item->get_id() => array(
					'qty' => 1,
				),
			)
		);

		$order = wc_get_order( $order->get_id() );
		$items = $order->get_items();
		$item  = reset( $items );

		$this->assertEquals( 4, $this->service->get_location_stock( $product, LocationStockService::LOCATION_POS ) );
		$this->assertEquals( 15, wc_get_product( $product->get_id() )->get_stock_quantity() );
		$this->assertEquals( 1, $item->get_meta( '_reduced_location_stock', true ) );
		$this->assertEmpty( $item->get_meta( '_reduced_stock', true ) );
	}

	/**
	 * @testdox Should not reduce POS stock again after refunded stock is fully restored.
	 */
	public function test_fully_refunded_pos_order_does_not_reduce_pos_stock_again(): void {
		$product = $this->create_managed_stock_product();
		$this->service->set_location_stock( $product, LocationStockService::LOCATION_POS, 5 );
		$order = $this->create_pos_order_for_product( $product, 2 );

		$this->controller->maybe_reduce_location_stock_levels( $order->get_id() );

		$order = wc_get_order( $order->get_id() );
		$items = $order->get_items();
		$item  = reset( $items );

		wc_restock_refunded_items(
			$order,
			array(
				$item->get_id() => array(
					'qty' => 2,
				),
			)
		);

		$this->controller->maybe_reduce_location_stock_levels( $order->get_id() );

		$order = wc_get_order( $order->get_id() );
		$items = $order->get_items();
		$item  = reset( $items );

		$this->assertEquals( 5, $this->service->get_location_stock( $product, LocationStockService::LOCATION_POS ) );
		$this->assertEquals( 15, wc_get_product( $product->get_id() )->get_stock_quantity() );
		$this->assertEquals( 0, $item->get_meta( '_reduced_location_stock', true ) );
		$this->assertEmpty( $item->get_meta( '_reduced_stock', true ) );
	}

	/**
	 * @testdox Should reduce POS stock when a POS order item quantity increases.
	 */
	public function test_pos_order_item_quantity_increase_adjusts_pos_stock_delta(): void {
		$product = $this->create_managed_stock_product();
		$this->service->set_location_stock( $product, LocationStockService::LOCATION_POS, 5 );
		$order = $this->create_pos_order_for_product( $product, 2 );

		$this->controller->maybe_reduce_location_stock_levels( $order->get_id() );
		$item = $this->set_first_order_item_quantity( $order, 3 );

		require_once WC_ABSPATH . 'includes/admin/wc-admin-functions.php';
		$changed_stock = wc_maybe_adjust_line_item_product_stock( $item );

		$order = wc_get_order( $order->get_id() );
		$items = $order->get_items();
		$item  = reset( $items );

		$this->assertFalse( $changed_stock );
		$this->assertEquals( 2, $this->service->get_location_stock( $product, LocationStockService::LOCATION_POS ) );
		$this->assertEquals( 15, wc_get_product( $product->get_id() )->get_stock_quantity() );
		$this->assertEquals( 3, $item->get_meta( '_reduced_location_stock', true ) );
		$this->assertEmpty( $item->get_meta( '_reduced_stock', true ) );
	}

	/**
	 * @testdox Should revert a POS order item quantity increase when POS stock is unavailable.
	 */
	public function test_pos_order_item_quantity_increase_failure_reverts_quantity(): void {
		$product = $this->create_managed_stock_product();
		$this->service->set_location_stock( $product, LocationStockService::LOCATION_POS, 2 );
		$order = $this->create_pos_order_for_product( $product, 2 );
		$order->set_status( 'processing' );
		$order->save();
		$this->set_first_order_item_totals( $order, '20.00', '20.00' );

		$this->controller->maybe_reduce_location_stock_levels( $order->get_id() );
		$this->save_first_order_item_with_admin_values( $order, 3, '30.00', '30.00' );

		$order = wc_get_order( $order->get_id() );
		$items = $order->get_items();
		$item  = reset( $items );

		$this->assertEquals( 0, $this->service->get_location_stock( $product, LocationStockService::LOCATION_POS ) );
		$this->assertEquals( 2, $item->get_quantity() );
		$this->assertEquals( 20.0, (float) $item->get_total() );
		$this->assertEquals( 20.0, (float) $item->get_subtotal() );
		$this->assertEquals( 2, $item->get_meta( '_reduced_location_stock', true ) );
		$this->assert_order_has_error_note( $order, 'Not enough stock at POS' );
	}

	/**
	 * @testdox Should reduce POS stock and leave Core stock untouched when a pending order transitions to completed.
	 */
	public function test_pos_order_status_transition_to_completed_reduces_location_stock(): void {
		$product = $this->create_managed_stock_product();
		$this->service->set_location_stock( $product, LocationStockService::LOCATION_POS, 5 );
		$order = $this->create_pos_order_for_product( $product, 2 );

		$this->assertSame( 'pending', $order->get_status() );

		$order->set_status( 'completed' );
		$order->save();

		$order   = wc_get_order( $order->get_id() );
		$product = wc_get_product( $product->get_id() );

		$this->assertEquals( 3, $this->service->get_location_stock( $product, LocationStockService::LOCATION_POS ) );
		$this->assertEquals( 15, $product->get_stock_quantity() );
		$this->assertEquals( 'yes', $order->get_meta( '_location_stock_reduced', true ) );
	}

	/**
	 * @testdox Should update parent-managed variation modified dates when POS orders reduce stock.
	 */
	public function test_pos_order_reduction_updates_parent_managed_variation_modified_date(): void {
		$parent    = $this->create_parent_managed_variation_product();
		$variation = wc_get_product( $parent->get_children()[0] );
		$this->assertInstanceOf( WC_Product::class, $variation );
		$this->service->set_location_stock( $parent, LocationStockService::LOCATION_POS, 5 );
		$order = $this->create_pos_order_for_product( $variation, 2 );

		$parent_modified_before    = $this->set_product_modified_date_to_past( $parent );
		$variation_modified_before = $this->set_product_modified_date_to_past( $variation );

		$this->controller->maybe_reduce_location_stock_levels( $order->get_id() );

		$this->assertEquals( 3, $this->service->get_location_stock( $parent, LocationStockService::LOCATION_POS ) );
		$this->assert_product_modified_after( $parent->get_id(), $parent_modified_before );
		$this->assert_product_modified_after( $variation->get_id(), $variation_modified_before );
	}

	/**
	 * @testdox Should restore POS stock when a POS order item quantity decreases.
	 */
	public function test_pos_order_item_quantity_decrease_adjusts_pos_stock_delta(): void {
		$product = $this->create_managed_stock_product();
		$this->service->set_location_stock( $product, LocationStockService::LOCATION_POS, 5 );
		$order = $this->create_pos_order_for_product( $product, 3 );

		$this->controller->maybe_reduce_location_stock_levels( $order->get_id() );
		$item = $this->set_first_order_item_quantity( $order, 1 );

		require_once WC_ABSPATH . 'includes/admin/wc-admin-functions.php';
		$changed_stock = wc_maybe_adjust_line_item_product_stock( $item );

		$order = wc_get_order( $order->get_id() );
		$items = $order->get_items();
		$item  = reset( $items );

		$this->assertFalse( $changed_stock );
		$this->assertEquals( 4, $this->service->get_location_stock( $product, LocationStockService::LOCATION_POS ) );
		$this->assertEquals( 15, wc_get_product( $product->get_id() )->get_stock_quantity() );
		$this->assertEquals( 1, $item->get_meta( '_reduced_location_stock', true ) );
		$this->assertEmpty( $item->get_meta( '_reduced_stock', true ) );
	}

	/**
	 * @testdox Should expose location_stock on product REST responses.
	 */
	public function test_product_rest_response_exposes_location_stock(): void {
		$product = $this->create_managed_stock_product();
		$this->service->set_location_stock( $product, LocationStockService::LOCATION_POS, 7 );

		$request  = new \WP_REST_Request( 'GET', '/wc/v3/products/' . $product->get_id() );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertArrayNotHasKey( 'pos_stock_quantity', $response->get_data() );
		$this->assertEquals(
			array(
				array(
					'slug'         => LocationStockService::LOCATION_POS,
					'name'         => 'POS',
					'quantity'     => 7,
					'stock_status' => ProductStockStatus::IN_STOCK,
				),
			),
			$response->get_data()['location_stock']
		);
	}

	/**
	 * @testdox Should not expose location stock rows for products that do not manage stock.
	 */
	public function test_product_rest_response_exposes_empty_location_stock_for_unmanaged_stock(): void {
		$product = $this->create_unmanaged_stock_product();

		$request  = new \WP_REST_Request( 'GET', '/wc/v3/products/' . $product->get_id() );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( ProductStockStatus::IN_STOCK, $response->get_data()['stock_status'] );
		$this->assertSame( array(), $response->get_data()['location_stock'] );
	}

	/**
	 * @testdox Should expose variation location_stock without replacing Core REST stock_quantity.
	 */
	public function test_variation_rest_response_exposes_location_stock_without_replacing_core_stock_quantity(): void {
		$variation = $this->create_variation_with_own_stock();
		$this->service->set_location_stock( $variation, LocationStockService::LOCATION_POS, 6 );

		$request  = new \WP_REST_Request( 'GET', '/wc/v3/products/' . $variation->get_parent_id() . '/variations/' . $variation->get_id() );
		$response = $this->server->dispatch( $request );

		$response_data = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 11, $response_data['stock_quantity'] );
		$this->assertEquals(
			array(
				array(
					'slug'         => LocationStockService::LOCATION_POS,
					'name'         => 'POS',
					'quantity'     => 6,
					'stock_status' => ProductStockStatus::IN_STOCK,
				),
			),
			$response_data['location_stock']
		);
	}

	/**
	 * @testdox Should include variation-managed location_stock in POS catalog rows.
	 */
	public function test_pos_catalog_includes_variation_managed_location_stock(): void {
		$variation = $this->create_variation_with_own_stock();
		$this->service->set_location_stock( $variation, LocationStockService::LOCATION_POS, 6 );

		$row = $this->map_pos_catalog_variation( $variation, 'id,stock_quantity,stock_status,location_stock' );

		$this->assertEquals( 11, $row['data']['stock_quantity'] );
		$this->assertEquals( ProductStockStatus::IN_STOCK, $row['data']['stock_status'] );
		$this->assertEquals( 6, $row['data']['location_stock'][0]['quantity'] );
	}

	/**
	 * @testdox Should include parent-managed location_stock in POS catalog rows.
	 */
	public function test_pos_catalog_includes_parent_managed_variation_location_stock(): void {
		$parent    = $this->create_parent_managed_variation_product();
		$variation = wc_get_product( $parent->get_children()[0] );
		$this->assertInstanceOf( WC_Product::class, $variation );
		$this->service->set_location_stock( $parent, LocationStockService::LOCATION_POS, 4 );

		$row = $this->map_pos_catalog_variation( $variation, 'id,stock_quantity,stock_status,location_stock' );

		$this->assertEquals( 15, $row['data']['stock_quantity'] );
		$this->assertEquals( ProductStockStatus::IN_STOCK, $row['data']['stock_status'] );
		$this->assertEquals( 4, $row['data']['location_stock'][0]['quantity'] );
	}

	/**
	 * @testdox Should respect requested fields for POS catalog location_stock.
	 */
	public function test_pos_catalog_does_not_include_location_stock_when_not_requested(): void {
		$variation = $this->create_variation_with_own_stock();
		$this->service->set_location_stock( $variation, LocationStockService::LOCATION_POS, 6 );

		$row = $this->map_pos_catalog_variation( $variation, 'id,stock_quantity,stock_status' );

		$this->assertArrayNotHasKey( 'location_stock', $row['data'] );
	}

	/**
	 * @testdox Should expose empty POS catalog location_stock for unmanaged products.
	 */
	public function test_pos_catalog_exposes_empty_location_stock_for_unmanaged_stock(): void {
		$product = $this->create_unmanaged_stock_product();

		$row = $this->map_pos_catalog_product( $product, 'id,stock_status,location_stock' );

		$this->assertEquals( ProductStockStatus::IN_STOCK, $row['data']['stock_status'] );
		$this->assertSame( array(), $row['data']['location_stock'] );
	}

	/**
	 * @testdox Should derive product REST location stock status from location quantity.
	 */
	public function test_product_rest_location_stock_status_uses_location_quantity(): void {
		$product = $this->create_managed_stock_product();
		$this->service->set_location_stock( $product, LocationStockService::LOCATION_POS, 0 );

		$request  = new \WP_REST_Request( 'GET', '/wc/v3/products/' . $product->get_id() );
		$response = $this->server->dispatch( $request );

		$response_data = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( ProductStockStatus::IN_STOCK, $response_data['stock_status'] );
		$this->assertEquals(
			array(
				'slug'         => LocationStockService::LOCATION_POS,
				'name'         => 'POS',
				'quantity'     => 0,
				'stock_status' => ProductStockStatus::OUT_OF_STOCK,
			),
			$response_data['location_stock'][0]
		);
	}

	/**
	 * @testdox Should respect requested fields for product REST location_stock.
	 */
	public function test_product_rest_location_stock_respects_requested_fields(): void {
		$product = $this->create_managed_stock_product();
		$this->service->set_location_stock( $product, LocationStockService::LOCATION_POS, 7 );

		$request = new \WP_REST_Request( 'GET', '/wc/v3/products/' . $product->get_id() );
		$request->set_param( '_fields', 'id' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertArrayHasKey( 'id', $response->get_data() );
		$this->assertArrayNotHasKey( 'location_stock', $response->get_data() );
	}

	/**
	 * Restore an option value captured before the test.
	 *
	 * @param string $option_name Option name.
	 * @param mixed  $value       Previous option value.
	 */
	private function restore_option( string $option_name, $value ): void {
		if ( null === $value ) {
			delete_option( $option_name );
			return;
		}

		update_option( $option_name, $value );
	}

	/**
	 * Create the inventory tables.
	 */
	private function create_inventory_tables(): void {
		$this->database_util->dbdelta( $this->service->get_database_schema() );
	}

	/**
	 * Empty the inventory tables.
	 */
	private function empty_inventory_tables(): void {
		global $wpdb;

		$wpdb->query( "DELETE FROM {$wpdb->prefix}wc_product_inventory" );
		$wpdb->query( "DELETE FROM {$wpdb->prefix}wc_inventory_locations" );
	}

	/**
	 * Remove the configured POS location.
	 */
	private function remove_pos_location(): void {
		global $wpdb;

		$wpdb->delete(
			$this->service->get_locations_table_name(),
			array(
				'slug' => LocationStockService::LOCATION_POS,
			),
			array( '%s' )
		);
	}

	/**
	 * Drop the inventory tables.
	 */
	private function drop_inventory_tables(): void {
		global $wpdb;

		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}wc_product_inventory" );
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}wc_inventory_locations" );
	}

	/**
	 * Create a REST order.
	 *
	 * @param array<string,mixed> $params Request parameters.
	 */
	private function create_rest_order( array $params ): \WP_REST_Response {
		$request = new \WP_REST_Request( 'POST', '/wc/v3/orders' );
		$request->set_body_params(
			array_merge(
				array(
					'payment_method' => 'bacs',
					'set_paid'       => true,
				),
				$params
			)
		);

		return $this->server->dispatch( $request );
	}

	/**
	 * Update a single line item quantity on an order through the REST API.
	 *
	 * @param int $order_id Order ID.
	 * @param int $item_id  Line item ID.
	 * @param int $quantity New quantity.
	 */
	private function update_rest_order_item_quantity( int $order_id, int $item_id, int $quantity ): \WP_REST_Response {
		$request = new \WP_REST_Request( 'PUT', '/wc/v3/orders/' . $order_id );
		$request->set_body_params(
			array(
				'line_items' => array(
					array(
						'id'       => $item_id,
						'quantity' => $quantity,
					),
				),
			)
		);

		return $this->server->dispatch( $request );
	}

	/**
	 * Assert that an order used POS stock.
	 *
	 * @param WC_Order $order           Order object.
	 * @param int      $reduced_qty     Reduced item quantity.
	 */
	private function assert_order_used_pos_stock( WC_Order $order, int $reduced_qty ): void {
		$items = $order->get_items();
		$item  = reset( $items );

		$this->assertEquals( LocationStockService::LOCATION_POS, $order->get_meta( '_inventory_location', true ) );
		$this->assertEquals( $reduced_qty, $item->get_meta( '_reduced_location_stock', true ) );
		$this->assertEmpty( $item->get_meta( '_reduced_stock', true ) );
	}

	/**
	 * Assert that an order contains an error note.
	 *
	 * @param WC_Order $order            Order object.
	 * @param string   $message_fragment Expected note text fragment.
	 */
	private function assert_order_has_error_note( WC_Order $order, string $message_fragment ): void {
		$notes = wc_get_order_notes( array( 'order_id' => $order->get_id() ) );

		foreach ( $notes as $note ) {
			if ( str_contains( $note->content, $message_fragment ) ) {
				$this->assertSame( OrderNoteGroup::ERROR, get_comment_meta( $note->id, 'note_group', true ) );
				return;
			}
		}

		$this->fail( 'Expected an order error note containing: ' . $message_fragment );
	}

	/**
	 * Set a product's modified date to a known past timestamp.
	 *
	 * @param WC_Product $product Product object.
	 */
	private function set_product_modified_date_to_past( WC_Product $product ): int {
		global $wpdb;

		$timestamp = (int) strtotime( '2020-01-01 00:00:00' );
		$wpdb->update(
			$wpdb->posts,
			array(
				'post_modified'     => gmdate( 'Y-m-d H:i:s', $timestamp ),
				'post_modified_gmt' => gmdate( 'Y-m-d H:i:s', $timestamp ),
			),
			array(
				'ID' => $product->get_id(),
			),
			array( '%s', '%s' ),
			array( '%d' )
		);

		$this->clear_product_cache( $product );

		return $this->get_product_modified_timestamp( $product->get_id() );
	}

	/**
	 * Assert that a product has been modified after a timestamp.
	 *
	 * @param int $product_id Product ID.
	 * @param int $timestamp  Timestamp.
	 */
	private function assert_product_modified_after( int $product_id, int $timestamp ): void {
		$this->assertGreaterThan( $timestamp, $this->get_product_modified_timestamp( $product_id ) );
	}

	/**
	 * Get a product modified timestamp.
	 *
	 * @param int $product_id Product ID.
	 */
	private function get_product_modified_timestamp( int $product_id ): int {
		$product = wc_get_product( $product_id );
		$this->assertInstanceOf( WC_Product::class, $product );
		$date_modified = $product->get_date_modified( 'edit' );
		$this->assertNotNull( $date_modified );

		return $date_modified->getTimestamp();
	}

	/**
	 * Map a variation through the POS catalog mapper.
	 *
	 * @param WC_Product $variation Variation product.
	 * @param string     $fields    Variation fields.
	 * @return array<string,mixed>
	 */
	private function map_pos_catalog_variation( WC_Product $variation, string $fields ): array {
		$mapper = wc_get_container()->get( ProductMapper::class );
		$this->assertInstanceOf( ProductMapper::class, $mapper );

		try {
			$mapper->set_variation_fields( $fields );
			return $mapper->map_product( $variation );
		} finally {
			$mapper->set_variation_fields( null );
		}
	}

	/**
	 * Map a product through the POS catalog mapper.
	 *
	 * @param WC_Product $product Product.
	 * @param string     $fields  Product fields.
	 * @return array<string,mixed>
	 */
	private function map_pos_catalog_product( WC_Product $product, string $fields ): array {
		$mapper = wc_get_container()->get( ProductMapper::class );
		$this->assertInstanceOf( ProductMapper::class, $mapper );

		try {
			$mapper->set_fields( $fields );
			return $mapper->map_product( $product );
		} finally {
			$mapper->set_fields( null );
		}
	}

	/**
	 * Clear product caches after directly backdating a test product.
	 *
	 * @param WC_Product $product Product object.
	 */
	private function clear_product_cache( WC_Product $product ): void {
		$product_id = $product->get_id();
		$parent_id  = $product->get_parent_id( 'edit' );

		clean_post_cache( $product_id );
		wc_delete_product_transients( $product_id );
		\WC_Cache_Helper::invalidate_cache_group( 'product_' . $product_id );

		if ( $parent_id ) {
			wc_delete_product_transients( $parent_id );
			\WC_Cache_Helper::invalidate_cache_group( 'product_' . $parent_id );
		}

		if ( \Automattic\WooCommerce\Utilities\FeaturesUtil::feature_is_enabled( 'product_instance_caching' ) ) {
			$product_cache = wc_get_container()->get( \Automattic\WooCommerce\Internal\Caches\ProductCache::class );
			$product_cache->remove( $product_id );

			if ( $parent_id ) {
				$product_cache->remove( $parent_id );
			}
		}
	}

	/**
	 * Render the variation POS location stock fields.
	 *
	 * @param WC_Product $variation Variation product.
	 * @param int        $loop      Position in the loop.
	 */
	private function get_rendered_variation_location_fields( WC_Product $variation, int $loop = 0 ): string {
		require_once WC_ABSPATH . 'includes/admin/wc-meta-box-functions.php';

		$variation_post = get_post( $variation->get_id() );
		$this->assertInstanceOf( \WP_Post::class, $variation_post );

		ob_start();
		$this->controller->render_variation_location_fields( $loop, array(), $variation_post );

		return (string) ob_get_clean();
	}

	/**
	 * Create a managed-stock product.
	 */
	private function create_managed_stock_product(): WC_Product {
		return WC_Helper_Product::create_simple_product(
			true,
			array(
				'manage_stock'   => true,
				'stock_quantity' => 15,
			)
		);
	}

	/**
	 * Create a product that does not manage stock.
	 */
	private function create_unmanaged_stock_product(): WC_Product {
		return WC_Helper_Product::create_simple_product(
			true,
			array(
				'manage_stock' => false,
				'stock_status' => ProductStockStatus::IN_STOCK,
			)
		);
	}

	/**
	 * Create a variable product where a child variation manages its own stock.
	 */
	private function create_variation_with_own_stock(): WC_Product {
		$parent = WC_Helper_Product::create_variation_product();
		$parent->set_manage_stock( false );
		$parent->save();

		$variation = wc_get_product( $parent->get_children()[0] );
		$variation->set_manage_stock( true );
		$variation->set_stock_quantity( 11 );
		$variation->save();

		return $variation;
	}

	/**
	 * Create a variable product with parent-managed stock.
	 */
	private function create_parent_managed_variation_product(): WC_Product {
		$parent = WC_Helper_Product::create_variation_product();
		$parent->set_manage_stock( true );
		$parent->set_stock_quantity( 15 );
		$parent->save();

		$variation = wc_get_product( $parent->get_children()[0] );
		$variation->set_manage_stock( false );
		$variation->save();

		return $parent;
	}

	/**
	 * Create a POS order containing one product.
	 *
	 * @param WC_Product $product  Product object.
	 * @param int        $quantity Quantity.
	 */
	private function create_pos_order_for_product( WC_Product $product, int $quantity ): WC_Order {
		return $this->create_order_for_product(
			$product,
			$quantity,
			'point-of-sale',
			array( '_inventory_location' => LocationStockService::LOCATION_POS )
		);
	}

	/**
	 * Create an order containing one product.
	 *
	 * @param WC_Product           $product     Product object.
	 * @param int                  $quantity    Quantity.
	 * @param string               $created_via Created via value.
	 * @param array<string,string> $meta        Order meta.
	 */
	private function create_order_for_product( WC_Product $product, int $quantity, string $created_via, array $meta = array() ): WC_Order {
		$order = new WC_Order();
		$order->set_created_via( $created_via );

		foreach ( $meta as $key => $value ) {
			$order->update_meta_data( $key, $value );
		}

		$item = new WC_Order_Item_Product();
		$item->set_product( $product );
		$item->set_quantity( $quantity );
		$order->add_item( $item );
		$order->save();

		return $order;
	}

	/**
	 * Set the first line item quantity on an order.
	 *
	 * @param WC_Order $order    Order object.
	 * @param int      $quantity New quantity.
	 */
	private function set_first_order_item_quantity( WC_Order $order, int $quantity ): WC_Order_Item_Product {
		$order = wc_get_order( $order->get_id() );
		$items = $order->get_items();
		$item  = reset( $items );

		$item->set_quantity( $quantity );
		$item->save();

		return $item;
	}

	/**
	 * Set the first line item totals on an order.
	 *
	 * @param WC_Order $order    Order object.
	 * @param string   $subtotal Line subtotal.
	 * @param string   $total    Line total.
	 */
	private function set_first_order_item_totals( WC_Order $order, string $subtotal, string $total ): WC_Order_Item_Product {
		$order = wc_get_order( $order->get_id() );
		$items = $order->get_items();
		$item  = reset( $items );

		$item->set_subtotal( $subtotal );
		$item->set_total( $total );
		$item->save();

		return $item;
	}

	/**
	 * Save the first line item through the classic admin order item path.
	 *
	 * @param WC_Order $order    Order object.
	 * @param int      $quantity New quantity.
	 * @param string   $subtotal Line subtotal.
	 * @param string   $total    Line total.
	 */
	private function save_first_order_item_with_admin_values( WC_Order $order, int $quantity, string $subtotal, string $total ): WC_Order_Item_Product {
		require_once WC_ABSPATH . 'includes/admin/wc-admin-functions.php';

		$order   = wc_get_order( $order->get_id() );
		$items   = $order->get_items();
		$item    = reset( $items );
		$item_id = $item->get_id();

		wc_save_order_items(
			$order->get_id(),
			array(
				'order_item_id'        => array( $item_id ),
				'order_item_name'      => array( $item_id => $item->get_name() ),
				'order_item_qty'       => array( $item_id => (string) $quantity ),
				'order_item_tax_class' => array( $item_id => $item->get_tax_class() ),
				'line_total'           => array( $item_id => $total ),
				'line_subtotal'        => array( $item_id => $subtotal ),
				'line_tax'             => array( $item_id => array() ),
				'line_subtotal_tax'    => array( $item_id => array() ),
			)
		);

		$order = wc_get_order( $order->get_id() );
		$items = $order->get_items();

		return reset( $items );
	}
}
