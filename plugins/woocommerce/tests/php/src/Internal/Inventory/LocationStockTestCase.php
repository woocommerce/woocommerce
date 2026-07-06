<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Inventory;

use Automattic\WooCommerce\Enums\ProductStockStatus;
use Automattic\WooCommerce\Internal\Inventory\InventoryController;
use Automattic\WooCommerce\Internal\Inventory\LocationStockAdminController;
use Automattic\WooCommerce\Internal\Inventory\LocationStockInstaller;
use Automattic\WooCommerce\Internal\Inventory\LocationStockOrderController;
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
 * Shared fixtures for POS location stock tests.
 */
abstract class LocationStockTestCase extends WC_REST_Unit_Test_Case {
	/**
	 * Service under test.
	 *
	 * @var LocationStockService
	 */
	protected LocationStockService $service;

	/**
	 * Controller under test.
	 *
	 * @var InventoryController
	 */
	protected InventoryController $controller;

	/**
	 * Admin controller under test.
	 *
	 * @var LocationStockAdminController
	 */
	protected LocationStockAdminController $admin_controller;

	/**
	 * Order controller under test.
	 *
	 * @var LocationStockOrderController
	 */
	protected LocationStockOrderController $order_controller;

	/**
	 * Installer under test.
	 *
	 * @var LocationStockInstaller
	 */
	protected LocationStockInstaller $installer;

	/**
	 * Database utility.
	 *
	 * @var DatabaseUtil
	 */
	protected DatabaseUtil $database_util;

	/**
	 * Original feature option value.
	 *
	 * @var mixed
	 */
	protected $previous_feature_option;

	/**
	 * Original manage stock option value.
	 *
	 * @var mixed
	 */
	protected $previous_manage_stock_option;

	/**
	 * Original table latch option value.
	 *
	 * @var mixed
	 */
	protected $previous_tables_created_option;

	/**
	 * Original POS location latch option value.
	 *
	 * @var mixed
	 */
	protected $previous_pos_location_created_option;

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

		$this->service          = wc_get_container()->get( LocationStockService::class );
		$this->controller       = wc_get_container()->get( InventoryController::class );
		$this->admin_controller = wc_get_container()->get( LocationStockAdminController::class );
		$this->order_controller = wc_get_container()->get( LocationStockOrderController::class );
		$this->installer        = wc_get_container()->get( LocationStockInstaller::class );
		$this->database_util    = wc_get_container()->get( DatabaseUtil::class );

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
	 * Restore an option value captured before the test.
	 *
	 * @param string $option_name Option name.
	 * @param mixed  $value       Previous option value.
	 */
	protected function restore_option( string $option_name, $value ): void {
		if ( null === $value ) {
			delete_option( $option_name );
			return;
		}

		update_option( $option_name, $value );
	}

	/**
	 * Create the inventory tables.
	 */
	protected function create_inventory_tables(): void {
		$this->database_util->dbdelta( $this->service->get_database_schema() );
	}

	/**
	 * Empty the inventory tables.
	 */
	protected function empty_inventory_tables(): void {
		global $wpdb;

		$wpdb->query( "DELETE FROM {$wpdb->prefix}wc_product_inventory" );
		$wpdb->query( "DELETE FROM {$wpdb->prefix}wc_locations" );
	}

	/**
	 * Remove the configured POS location.
	 */
	protected function remove_pos_location(): void {
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
	protected function drop_inventory_tables(): void {
		global $wpdb;

		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}wc_product_inventory" );
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}wc_locations" );
	}

	/**
	 * Create a REST order.
	 *
	 * @param array<string,mixed> $params Request parameters.
	 */
	protected function create_rest_order( array $params ): \WP_REST_Response {
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
	protected function update_rest_order_item_quantity( int $order_id, int $item_id, int $quantity ): \WP_REST_Response {
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
	protected function assert_order_used_pos_stock( WC_Order $order, int $reduced_qty ): void {
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
	protected function assert_order_has_error_note( WC_Order $order, string $message_fragment ): void {
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
	protected function set_product_modified_date_to_past( WC_Product $product ): int {
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
	protected function assert_product_modified_after( int $product_id, int $timestamp ): void {
		$this->assertGreaterThan( $timestamp, $this->get_product_modified_timestamp( $product_id ) );
	}

	/**
	 * Get a product modified timestamp.
	 *
	 * @param int $product_id Product ID.
	 */
	protected function get_product_modified_timestamp( int $product_id ): int {
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
	protected function map_pos_catalog_variation( WC_Product $variation, string $fields ): array {
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
	protected function map_pos_catalog_product( WC_Product $product, string $fields ): array {
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
	protected function clear_product_cache( WC_Product $product ): void {
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
	protected function get_rendered_variation_location_fields( WC_Product $variation, int $loop = 0 ): string {
		require_once WC_ABSPATH . 'includes/admin/wc-meta-box-functions.php';

		$variation_post = get_post( $variation->get_id() );
		$this->assertInstanceOf( \WP_Post::class, $variation_post );

		ob_start();
		$this->admin_controller->render_variation_location_fields( $loop, array(), $variation_post );

		return (string) ob_get_clean();
	}

	/**
	 * Create a managed-stock product.
	 */
	protected function create_managed_stock_product(): WC_Product {
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
	protected function create_unmanaged_stock_product(): WC_Product {
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
	protected function create_variation_with_own_stock(): WC_Product {
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
	protected function create_parent_managed_variation_product(): WC_Product {
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
	protected function create_pos_order_for_product( WC_Product $product, int $quantity ): WC_Order {
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
	protected function create_order_for_product( WC_Product $product, int $quantity, string $created_via, array $meta = array() ): WC_Order {
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
	protected function set_first_order_item_quantity( WC_Order $order, int $quantity ): WC_Order_Item_Product {
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
	protected function set_first_order_item_totals( WC_Order $order, string $subtotal, string $total ): WC_Order_Item_Product {
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
	protected function save_first_order_item_with_admin_values( WC_Order $order, int $quantity, string $subtotal, string $total ): WC_Order_Item_Product {
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
