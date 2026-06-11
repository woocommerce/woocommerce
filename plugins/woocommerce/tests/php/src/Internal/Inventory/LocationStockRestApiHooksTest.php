<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Inventory;

use Automattic\WooCommerce\Enums\ProductStockStatus;
use Automattic\WooCommerce\Internal\Inventory\LocationStockService;
use WC_Product;

require_once __DIR__ . '/LocationStockTestCase.php';

/**
 * Tests for POS location stock.
 *
 * @covers \Automattic\WooCommerce\Internal\Inventory\LocationStockRestApiHooks
 */
class LocationStockRestApiHooksTest extends LocationStockTestCase {

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
	 * @testdox Should keep the edited quantity and add an error note when a REST order item increase exceeds available stock.
	 */
	public function test_pos_rest_order_item_increase_beyond_stock_keeps_edit_and_notes_error(): void {
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

		// Like Core, the edit is not rolled back; POS stock stays at zero (never negative) and Core stock is untouched.
		$this->assertEquals( 5, $item->get_quantity() );
		$this->assertEquals( 0, $this->service->get_location_stock( $product, LocationStockService::LOCATION_POS ) );
		$this->assertEquals( 15, wc_get_product( $product->get_id() )->get_stock_quantity() );
		$this->assert_order_has_error_note( $order, 'Not enough stock at POS' );
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
}
