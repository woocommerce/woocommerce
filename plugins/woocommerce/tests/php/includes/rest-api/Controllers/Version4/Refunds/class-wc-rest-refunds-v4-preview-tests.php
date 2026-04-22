<?php
declare( strict_types=1 );

use Automattic\WooCommerce\Enums\OrderStatus;

/**
 * Integration tests for the POST /wc/v4/refunds/preview endpoint.
 *
 * @group refund-preview-tests
 */
class WC_REST_Refunds_V4_Preview_Tests extends WC_REST_Unit_Test_Case {

	/**
	 * User ID.
	 *
	 * @var int
	 */
	private $user_id;

	/**
	 * Collection of created orders for cleanup.
	 *
	 * @var array
	 */
	private $created_orders = array();

	/**
	 * Enable the REST API v4 feature.
	 */
	public static function enable_rest_api_v4_feature() {
		add_filter(
			'woocommerce_admin_features',
			function ( $features ) {
				$features[] = 'rest-api-v4';
				return $features;
			},
		);
	}

	/**
	 * Disable the REST API v4 feature.
	 */
	public static function disable_rest_api_v4_feature() {
		add_filter(
			'woocommerce_admin_features',
			function ( $features ) {
				$features = array_diff( $features, array( 'rest-api-v4' ) );
				return $features;
			}
		);
	}

	/**
	 * Setup our test server, endpoints, and user info.
	 */
	public function setUp(): void {
		$this->enable_rest_api_v4_feature();
		parent::setUp();

		$this->user_id = wp_insert_user(
			array(
				'user_login' => 'test_admin',
				'user_email' => 'test@example.com',
				'user_pass'  => 'password',
				'role'       => 'administrator',
			)
		);
		wp_set_current_user( $this->user_id );
	}

	/**
	 * Runs after each test.
	 */
	public function tearDown(): void {
		foreach ( $this->created_orders as $order_id ) {
			$order = wc_get_order( $order_id );
			if ( $order ) {
				foreach ( $order->get_refunds() as $refund ) {
					$refund->delete( true );
				}
				$order->delete( true );
			}
		}
		$this->created_orders = array();

		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->prefix}woocommerce_tax_rate_locations" );
		$wpdb->query( "DELETE FROM {$wpdb->prefix}woocommerce_tax_rates" );

		parent::tearDown();
		$this->disable_rest_api_v4_feature();
	}

	/**
	 * @testdox P1: Preview a single full line item with no tax returns correct totals.
	 */
	public function test_preview_single_line_item_no_tax(): void {
		$order   = $this->create_order_with_product( 50.00, 2 );
		$item_id = $this->get_first_line_item_id( $order );

		$response = $this->do_preview_request(
			$order->get_id(),
			array(
				array(
					'line_item_id' => $item_id,
					'quantity'     => 2,
				),
			)
		);

		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();

		$this->assertEquals( '100.00', $data['total'] );
		$this->assertEquals( '100.00', $data['subtotal'] );
		$this->assertEquals( '0.00', $data['tax'] );
		$this->assertCount( 1, $data['breakdown']['products']['items'] );
		$this->assertEquals( 2, $data['breakdown']['products']['items'][0]['quantity'] );
	}

	/**
	 * @testdox P2: Preview a single line item with 10% tax extracts tax correctly.
	 */
	public function test_preview_single_line_item_with_tax(): void {
		$tax_rate_id = $this->create_tax_rate( 10.0 );
		$order       = $this->create_order_with_product_and_tax( 100.00, 1, $tax_rate_id, 10.00 );
		$item_id     = $this->get_first_line_item_id( $order );

		$response = $this->do_preview_request(
			$order->get_id(),
			array(
				array(
					'line_item_id' => $item_id,
					'quantity'     => 1,
				),
			)
		);

		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();

		$this->assertEquals( '110.00', $data['total'] );
		$this->assertEquals( '100.00', $data['subtotal'] );
		$this->assertEquals( '10.00', $data['tax'] );
	}

	/**
	 * @testdox P3: Preview partial quantity returns proportional totals.
	 */
	public function test_preview_partial_quantity(): void {
		$order   = $this->create_order_with_product( 10.00, 5 );
		$item_id = $this->get_first_line_item_id( $order );

		$response = $this->do_preview_request(
			$order->get_id(),
			array(
				array(
					'line_item_id' => $item_id,
					'quantity'     => 2,
				),
			)
		);

		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();

		$this->assertEquals( '20.00', $data['total'], 'Partial refund of 2 of 5 at $10 each should be $20' );
		$this->assertEquals( 2, $data['breakdown']['products']['items'][0]['quantity'] );
	}

	/**
	 * @testdox P4: Preview multiple line items returns aggregated totals.
	 */
	public function test_preview_multiple_line_items(): void {
		$product_a = WC_Helper_Product::create_simple_product();
		$product_a->set_regular_price( 20.00 );
		$product_a->save();

		$product_b = WC_Helper_Product::create_simple_product();
		$product_b->set_regular_price( 30.00 );
		$product_b->save();

		$order = wc_create_order();
		$item_a = new WC_Order_Item_Product();
		$item_a->set_props(
			array(
				'product'  => $product_a,
				'quantity' => 2,
				'subtotal' => 40.00,
				'total'    => 40.00,
			)
		);
		$item_a->save();
		$order->add_item( $item_a );

		$item_b = new WC_Order_Item_Product();
		$item_b->set_props(
			array(
				'product'  => $product_b,
				'quantity' => 1,
				'subtotal' => 30.00,
				'total'    => 30.00,
			)
		);
		$item_b->save();
		$order->add_item( $item_b );

		$order->set_total( 70.00 );
		$order->set_status( OrderStatus::COMPLETED );
		$order->save();
		$this->created_orders[] = $order->get_id();

		$response = $this->do_preview_request(
			$order->get_id(),
			array(
				array(
					'line_item_id' => $item_a->get_id(),
					'quantity'     => 1,
				),
				array(
					'line_item_id' => $item_b->get_id(),
					'quantity'     => 1,
				),
			)
		);

		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();

		$this->assertEquals( '50.00', $data['total'], '20 + 30 = 50' );
		$this->assertCount( 2, $data['breakdown']['products']['items'] );

		$product_a->delete( true );
		$product_b->delete( true );
	}

	/**
	 * @testdox P7: Preview with quantity exceeding refundable returns error.
	 */
	public function test_preview_quantity_exceeds_refundable(): void {
		// Create order with qty=2 so a partial refund leaves remaining amount.
		$order   = $this->create_order_with_product( 25.00, 2 );
		$item_id = $this->get_first_line_item_id( $order );

		// Refund 1 unit (leaves 1 remaining and $25 remaining amount).
		wc_create_refund(
			array(
				'order_id'   => $order->get_id(),
				'amount'     => 25.00,
				'line_items' => array(
					$item_id => array(
						'qty'          => 1,
						'refund_total' => 25.00,
						'refund_tax'   => array(),
					),
				),
			)
		);

		// Try to refund 2, but only 1 remains.
		$response = $this->do_preview_request(
			$order->get_id(),
			array(
				array(
					'line_item_id' => $item_id,
					'quantity'     => 2,
				),
			)
		);

		$this->assertEquals( 400, $response->get_status() );
		$data = $response->get_data();
		$this->assertEquals( 'quantity_exceeds_refundable', $data['code'] );
	}

	/**
	 * @testdox P8: Preview with invalid line item ID returns error.
	 */
	public function test_preview_invalid_line_item(): void {
		$order = $this->create_order_with_product( 50.00, 1 );

		$response = $this->do_preview_request(
			$order->get_id(),
			array(
				array(
					'line_item_id' => 999999,
					'quantity'     => 1,
				),
			)
		);

		$this->assertEquals( 400, $response->get_status() );
		$data = $response->get_data();
		$this->assertEquals( 'invalid_line_item', $data['code'] );
	}

	/**
	 * @testdox P9: Preview on fully refunded order returns error.
	 */
	public function test_preview_fully_refunded_order(): void {
		$order   = $this->create_order_with_product( 50.00, 1 );
		$item_id = $this->get_first_line_item_id( $order );

		wc_create_refund(
			array(
				'order_id' => $order->get_id(),
				'amount'   => 50.00,
			)
		);

		$response = $this->do_preview_request(
			$order->get_id(),
			array(
				array(
					'line_item_id' => $item_id,
					'quantity'     => 1,
				),
			)
		);

		$this->assertEquals( 400, $response->get_status() );
		$data = $response->get_data();
		$this->assertEquals( 'order_not_refundable', $data['code'] );
	}

	/**
	 * @testdox P11: Preview with empty line_items array returns error.
	 */
	public function test_preview_empty_line_items(): void {
		$order = $this->create_order_with_product( 50.00, 1 );

		$response = $this->do_preview_request( $order->get_id(), array() );

		$this->assertEquals( 400, $response->get_status() );
	}

	/**
	 * @testdox P15: Preview without authentication returns 401.
	 */
	public function test_preview_unauthenticated(): void {
		$order = $this->create_order_with_product( 50.00, 1 );
		wp_set_current_user( 0 );

		$item_id  = $this->get_first_line_item_id( $order );
		$response = $this->do_preview_request(
			$order->get_id(),
			array(
				array(
					'line_item_id' => $item_id,
					'quantity'     => 1,
				),
			)
		);

		$this->assertContains( $response->get_status(), array( 401, 403 ) );
	}

	/**
	 * @testdox P17: Preview does NOT create a refund record.
	 */
	public function test_preview_does_not_create_refund(): void {
		$order   = $this->create_order_with_product( 50.00, 1 );
		$item_id = $this->get_first_line_item_id( $order );

		$refunds_before = $order->get_refunds();

		$response = $this->do_preview_request(
			$order->get_id(),
			array(
				array(
					'line_item_id' => $item_id,
					'quantity'     => 1,
				),
			)
		);

		$this->assertEquals( 200, $response->get_status() );

		// Reload the order and check refunds.
		$order          = wc_get_order( $order->get_id() );
		$refunds_after  = $order->get_refunds();

		$this->assertCount( count( $refunds_before ), $refunds_after, 'Preview should not create any refund records' );
	}

	/**
	 * @testdox P19: Preview response total matches subsequent create response total for same inputs.
	 */
	public function test_preview_matches_create(): void {
		$tax_rate_id = $this->create_tax_rate( 10.0 );
		$order       = $this->create_order_with_product_and_tax( 100.00, 1, $tax_rate_id, 10.00 );
		$item_id     = $this->get_first_line_item_id( $order );

		// Get preview.
		$preview_response = $this->do_preview_request(
			$order->get_id(),
			array(
				array(
					'line_item_id' => $item_id,
					'quantity'     => 1,
				),
			)
		);
		$this->assertEquals( 200, $preview_response->get_status() );
		$preview_data = $preview_response->get_data();

		// Create the actual refund with the same line items using the create endpoint.
		$create_request = new WP_REST_Request( 'POST', '/wc/v4/refunds' );
		$create_request->set_body_params(
			array(
				'order_id'   => $order->get_id(),
				'line_items' => array(
					array(
						'line_item_id' => $item_id,
						'quantity'     => 1,
						'refund_total' => 110.00,
					),
				),
			)
		);
		$create_response = $this->server->dispatch( $create_request );
		$this->assertEquals( 201, $create_response->get_status() );
		$create_data = $create_response->get_data();

		$this->assertEquals(
			$preview_data['total'],
			$create_data['amount'],
			'Preview total must match create refund amount exactly'
		);
	}

	/**
	 * @testdox Preview response includes product metadata (name, product_id, variation_id).
	 */
	public function test_preview_includes_product_metadata(): void {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( 50.00 );
		$product->save();

		$order = wc_create_order();
		$item  = new WC_Order_Item_Product();
		$item->set_props(
			array(
				'product'  => $product,
				'quantity' => 1,
				'subtotal' => 50.00,
				'total'    => 50.00,
			)
		);
		$item->save();
		$order->add_item( $item );
		$order->set_total( 50.00 );
		$order->set_status( OrderStatus::COMPLETED );
		$order->save();
		$this->created_orders[] = $order->get_id();

		$response = $this->do_preview_request(
			$order->get_id(),
			array(
				array(
					'line_item_id' => $item->get_id(),
					'quantity'     => 1,
				),
			)
		);

		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();

		$product_item = $data['breakdown']['products']['items'][0];
		$this->assertArrayHasKey( 'name', $product_item );
		$this->assertArrayHasKey( 'product_id', $product_item );
		$this->assertArrayHasKey( 'variation_id', $product_item );
		$this->assertNotEmpty( $product_item['name'] );
		$this->assertGreaterThan( 0, $product_item['product_id'] );

		$product->delete( true );
	}

	/**
	 * @testdox Preview on cancelled order returns order_not_refundable error.
	 */
	public function test_preview_cancelled_order(): void {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( 50.00 );
		$product->save();

		$order = wc_create_order();
		$item  = new WC_Order_Item_Product();
		$item->set_props(
			array(
				'product'  => $product,
				'quantity' => 1,
				'subtotal' => 50.00,
				'total'    => 50.00,
			)
		);
		$item->save();
		$order->add_item( $item );
		$order->set_total( 50.00 );
		$order->set_status( OrderStatus::CANCELLED );
		$order->save();
		$this->created_orders[] = $order->get_id();

		$response = $this->do_preview_request(
			$order->get_id(),
			array(
				array(
					'line_item_id' => $item->get_id(),
					'quantity'     => 1,
				),
			)
		);

		$this->assertEquals( 400, $response->get_status() );
		$data = $response->get_data();
		$this->assertEquals( 'order_not_refundable', $data['code'] );

		$product->delete( true );
	}

	/**
	 * @testdox Preview includes max_refundable amount.
	 */
	public function test_preview_includes_max_refundable(): void {
		$order   = $this->create_order_with_product( 100.00, 2 );
		$item_id = $this->get_first_line_item_id( $order );

		// Partially refund $50.
		wc_create_refund(
			array(
				'order_id' => $order->get_id(),
				'amount'   => 50.00,
			)
		);

		$response = $this->do_preview_request(
			$order->get_id(),
			array(
				array(
					'line_item_id' => $item_id,
					'quantity'     => 1,
				),
			)
		);

		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();

		$this->assertEquals( '150.00', $data['max_refundable'], 'Max refundable should be original total minus already refunded' );
	}

	// -- Helper methods --

	/**
	 * Create an order with a product line item.
	 *
	 * @param float $unit_price Product price per unit.
	 * @param int   $quantity   Quantity.
	 * @return WC_Order
	 */
	private function create_order_with_product( float $unit_price, int $quantity ): WC_Order {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( $unit_price );
		$product->save();

		$order = wc_create_order();
		$item  = new WC_Order_Item_Product();
		$item->set_props(
			array(
				'product'  => $product,
				'quantity' => $quantity,
				'subtotal' => $unit_price * $quantity,
				'total'    => $unit_price * $quantity,
			)
		);
		$item->save();
		$order->add_item( $item );
		$order->set_total( $unit_price * $quantity );
		$order->set_status( OrderStatus::COMPLETED );
		$order->save();

		$this->created_orders[] = $order->get_id();
		$product->delete( true );

		return $order;
	}

	/**
	 * Create an order with a product and tax.
	 *
	 * @param float $product_price Product price.
	 * @param int   $quantity      Quantity.
	 * @param int   $tax_rate_id   Tax rate ID.
	 * @param float $tax_amount    Tax amount.
	 * @return WC_Order
	 */
	private function create_order_with_product_and_tax( float $product_price, int $quantity, int $tax_rate_id, float $tax_amount ): WC_Order {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( $product_price );
		$product->set_tax_status( 'taxable' );
		$product->save();

		$total = $product_price * $quantity;
		$order = wc_create_order();
		$item  = new WC_Order_Item_Product();
		$item->set_props(
			array(
				'product'  => $product,
				'quantity' => $quantity,
				'subtotal' => $total,
				'total'    => $total,
			)
		);
		$item->set_taxes(
			array(
				'total'    => array( $tax_rate_id => $tax_amount ),
				'subtotal' => array( $tax_rate_id => $tax_amount ),
			)
		);
		$item->save();
		$order->add_item( $item );

		$tax_item = new WC_Order_Item_Tax();
		$tax_item->set_rate( $tax_rate_id );
		$tax_item->set_tax_total( $tax_amount );
		$tax_item->save();
		$order->add_item( $tax_item );

		$order->set_billing_country( 'US' );
		$order->set_total( $total + $tax_amount );
		$order->set_status( OrderStatus::COMPLETED );
		$order->save();

		$this->created_orders[] = $order->get_id();
		$product->delete( true );

		return $order;
	}

	/**
	 * Create a tax rate.
	 *
	 * @param float $rate Tax rate percentage.
	 * @return int Tax rate ID.
	 */
	private function create_tax_rate( float $rate ): int {
		return WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => 'US',
				'tax_rate_state'    => '',
				'tax_rate'          => number_format( $rate, 4 ),
				'tax_rate_name'     => 'Tax',
				'tax_rate_priority' => '1',
				'tax_rate_compound' => '0',
				'tax_rate_shipping' => '1',
				'tax_rate_order'    => '1',
				'tax_rate_class'    => '',
			)
		);
	}

	/**
	 * Get the first line item ID from an order.
	 *
	 * @param WC_Order $order Order instance.
	 * @return int Line item ID.
	 */
	private function get_first_line_item_id( WC_Order $order ): int {
		$items = $order->get_items( 'line_item' );
		$item  = reset( $items );
		return $item->get_id();
	}

	/**
	 * Send a preview request and return the response.
	 *
	 * @param int   $order_id   Order ID.
	 * @param array $line_items Line items array.
	 * @return WP_REST_Response
	 */
	private function do_preview_request( int $order_id, array $line_items ): WP_REST_Response {
		$request = new WP_REST_Request( 'POST', '/wc/v4/refunds/preview' );
		$request->set_body_params(
			array(
				'order_id'   => $order_id,
				'line_items' => $line_items,
			)
		);
		return $this->server->dispatch( $request );
	}
}
