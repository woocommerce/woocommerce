<?php
declare( strict_types=1 );

use Automattic\WooCommerce\Enums\OrderStatus;

/**
 * Integration tests for the can_be_refunded field on wc/v3 order responses.
 *
 * @group orders-can-be-refunded
 */
class WC_REST_Orders_Can_Be_Refunded_Test extends WC_REST_Unit_Test_Case {

	/**
	 * Shared admin user ID. Created once per class to avoid the wp_insert_user cost
	 * on every test.
	 *
	 * @var int
	 */
	protected static $user_id;

	/**
	 * Create the shared admin user once per class.
	 */
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		self::$user_id = wp_insert_user(
			array(
				'user_login' => 'v3_cbr_admin_' . wp_generate_password( 6, false ),
				'user_email' => 'v3_cbr_admin_' . wp_generate_password( 6, false ) . '@example.com',
				'user_pass'  => 'password',
				'role'       => 'administrator',
			)
		);
		if ( is_wp_error( self::$user_id ) ) {
			self::fail( 'Could not create test admin user: ' . self::$user_id->get_error_message() );
		}
		self::$user_id = (int) self::$user_id;
	}

	/**
	 * Delete the shared admin user once per class.
	 */
	public static function tearDownAfterClass(): void {
		if ( self::$user_id ) {
			wp_delete_user( self::$user_id );
			self::$user_id = 0;
		}
		parent::tearDownAfterClass();
	}

	/**
	 * Setup our test server, endpoints, and user info.
	 */
	public function setUp(): void {
		parent::setUp();

		wp_set_current_user( self::$user_id );
	}

	/**
	 * @testdox A fresh unrefunded order has can_be_refunded true at order and line item level.
	 */
	public function test_fresh_order_can_be_refunded(): void {
		$order = $this->create_order_with_product( 25.00, 2 );

		$data = $this->get_order_response( $order->get_id() );

		$this->assertTrue( $data['can_be_refunded'] );
		$this->assertTrue( $data['line_items'][0]['can_be_refunded'] );
	}

	/**
	 * @testdox A fully refunded order has can_be_refunded false at order and line item level.
	 */
	public function test_fully_refunded_order(): void {
		$order   = $this->create_order_with_product( 25.00, 1 );
		$item_id = $this->get_first_line_item_id( $order );

		wc_create_refund(
			array(
				'order_id'   => $order->get_id(),
				'amount'     => 25.00,
				'line_items' => array(
					$item_id => array(
						'qty'          => 1,
						'refund_total' => 25.00,
					),
				),
			)
		);

		$data = $this->get_order_response( $order->get_id() );

		$this->assertFalse( $data['can_be_refunded'] );
		$this->assertFalse( $data['line_items'][0]['can_be_refunded'] );
	}

	/**
	 * @testdox A partially refunded order has mixed can_be_refunded values per line.
	 */
	public function test_partially_refunded_order_has_mixed_line_values(): void {
		$order = wc_create_order();

		$product_a = WC_Helper_Product::create_simple_product();
		$product_a->set_regular_price( 20.00 );
		$product_a->save();
		$product_b = WC_Helper_Product::create_simple_product();
		$product_b->set_regular_price( 30.00 );
		$product_b->save();

		$item_ids = array();
		foreach ( array( $product_a, $product_b ) as $product ) {
			$item = new WC_Order_Item_Product();
			$item->set_props(
				array(
					'product'  => $product,
					'quantity' => 1,
					'subtotal' => (float) $product->get_regular_price(),
					'total'    => (float) $product->get_regular_price(),
				)
			);
			$item->save();
			$order->add_item( $item );
			$item_ids[] = $item->get_id();
		}
		$order->set_total( 50.00 );
		$order->set_status( OrderStatus::COMPLETED );
		$order->save();

		wc_create_refund(
			array(
				'order_id'   => $order->get_id(),
				'amount'     => 20.00,
				'line_items' => array(
					$item_ids[0] => array(
						'qty'          => 1,
						'refund_total' => 20.00,
					),
				),
			)
		);

		$data = $this->get_order_response( $order->get_id() );

		$this->assertTrue( $data['can_be_refunded'], 'Order with remaining amount should be refundable.' );

		$lines_by_id = array_column( $data['line_items'], null, 'id' );
		$this->assertFalse( $lines_by_id[ $item_ids[0] ]['can_be_refunded'], 'Fully refunded line should not be refundable.' );
		$this->assertTrue( $lines_by_id[ $item_ids[1] ]['can_be_refunded'], 'Untouched line should stay refundable.' );

		$product_a->delete( true );
		$product_b->delete( true );
	}

	/**
	 * @testdox A product line whose value is exhausted by an amount-only refund is not refundable despite remaining quantity.
	 */
	public function test_value_exhausted_line_not_refundable_despite_remaining_quantity(): void {
		$order = $this->create_order_with_product( 50.00, 1 );
		// Extra unallocated amount keeps the order refundable after the line is exhausted,
		// so the order-level flag does not mask the line-level one.
		$order->set_total( 60.00 );
		$order->save();
		$item_id = $this->get_first_line_item_id( $order );

		// Amount-only refund (qty 0) consumes the line's full monetary value while
		// leaving its quantity untouched.
		wc_create_refund(
			array(
				'order_id'   => $order->get_id(),
				'amount'     => 50.00,
				'line_items' => array(
					$item_id => array(
						'qty'          => 0,
						'refund_total' => 50.00,
					),
				),
			)
		);

		$data = $this->get_order_response( $order->get_id() );

		$this->assertTrue( $data['can_be_refunded'], 'Order with a remaining amount should stay refundable.' );
		$this->assertFalse( $data['line_items'][0]['can_be_refunded'], 'A line with no remaining value must not be refundable even with remaining quantity.' );
	}

	/**
	 * @testdox Non-refundable statuses report can_be_refunded false and refundable statuses true.
	 * @dataProvider status_provider
	 *
	 * @param string $status   Order status.
	 * @param bool   $expected Expected can_be_refunded value.
	 */
	public function test_status_gating( string $status, bool $expected ): void {
		$order = $this->create_order_with_product( 25.00, 1 );
		$order->set_status( $status );
		$order->save();

		$data = $this->get_order_response( $order->get_id() );

		$this->assertSame( $expected, $data['can_be_refunded'], "Status {$status} should report can_be_refunded as " . ( $expected ? 'true' : 'false' ) . '.' );
	}

	/**
	 * Data provider for status gating.
	 *
	 * @return array
	 */
	public function status_provider(): array {
		return array(
			'completed'  => array( OrderStatus::COMPLETED, true ),
			'processing' => array( OrderStatus::PROCESSING, true ),
			'on-hold'    => array( OrderStatus::ON_HOLD, true ),
			'pending'    => array( OrderStatus::PENDING, false ),
			'cancelled'  => array( OrderStatus::CANCELLED, false ),
			'failed'     => array( OrderStatus::FAILED, false ),
		);
	}

	/**
	 * @testdox A custom line item without a product has can_be_refunded false.
	 */
	public function test_line_item_without_product_not_refundable(): void {
		$order = wc_create_order();
		$item  = new WC_Order_Item_Product();
		$item->set_props(
			array(
				'name'     => 'Custom item',
				'quantity' => 1,
				'subtotal' => 10.00,
				'total'    => 10.00,
			)
		);
		$item->save();
		$order->add_item( $item );
		$order->set_total( 10.00 );
		$order->set_status( OrderStatus::COMPLETED );
		$order->save();

		$data = $this->get_order_response( $order->get_id() );

		$this->assertFalse( $data['line_items'][0]['can_be_refunded'], 'A line without a product cannot be refunded by quantity.' );
	}

	/**
	 * @testdox A zero-priced product line item follows quantity logic.
	 */
	public function test_zero_priced_item_follows_quantity_logic(): void {
		$order = $this->create_order_with_product( 0.00, 1 );
		// Unrelated remaining amount so the order stays refundable.
		$order->set_total( 10.00 );
		$order->save();

		$data = $this->get_order_response( $order->get_id() );

		$this->assertTrue( $data['line_items'][0]['can_be_refunded'], 'A zero-priced line with unrefunded quantity should be refundable.' );
	}

	/**
	 * @testdox Shipping and fee lines with remaining amounts have can_be_refunded true.
	 */
	public function test_shipping_and_fee_lines_refundable(): void {
		$order = $this->create_order_with_shipping_and_fee( 15.00, 7.50 );

		$data = $this->get_order_response( $order->get_id() );

		$this->assertTrue( $data['shipping_lines'][0]['can_be_refunded'] );
		$this->assertTrue( $data['fee_lines'][0]['can_be_refunded'] );
	}

	/**
	 * @testdox Fully refunded shipping and fee lines have can_be_refunded false.
	 */
	public function test_fully_refunded_shipping_and_fee_lines(): void {
		$order = $this->create_order_with_shipping_and_fee( 15.00, 7.50 );

		$line_items = array();
		foreach ( $order->get_items( array( 'shipping', 'fee' ) ) as $item_id => $item ) {
			$line_items[ $item_id ] = array(
				'qty'          => 0,
				'refund_total' => (float) $item->get_total(),
			);
		}
		wc_create_refund(
			array(
				'order_id'   => $order->get_id(),
				'amount'     => 22.50,
				'line_items' => $line_items,
			)
		);

		$data = $this->get_order_response( $order->get_id() );

		$this->assertFalse( $data['shipping_lines'][0]['can_be_refunded'] );
		$this->assertFalse( $data['fee_lines'][0]['can_be_refunded'] );
	}

	/**
	 * @testdox A discount (negative-total) fee line with no prior refund reports can_be_refunded true.
	 */
	public function test_negative_fee_line_can_be_refunded(): void {
		$order = wc_create_order();
		$fee   = new WC_Order_Item_Fee();
		$fee->set_props(
			array(
				'name'  => 'Discount',
				'total' => -5.00,
			)
		);
		$fee->save();
		$order->add_item( $fee );
		$order->set_total( 10.00 );
		$order->set_status( OrderStatus::COMPLETED );
		$order->save();

		$data = $this->get_order_response( $order->get_id() );

		$this->assertTrue( $data['fee_lines'][0]['can_be_refunded'], 'Negative fee lines are compared on an absolute basis.' );
	}

	/**
	 * @testdox The list endpoint returns can_be_refunded for all orders.
	 */
	public function test_list_endpoint_returns_field(): void {
		$refundable = $this->create_order_with_product( 25.00, 1 );
		$pending    = $this->create_order_with_product( 25.00, 1 );
		$pending->set_status( OrderStatus::PENDING );
		$pending->save();

		$request  = new WP_REST_Request( 'GET', '/wc/v3/orders' );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );

		$orders_by_id = array_column( $response->get_data(), null, 'id' );
		$this->assertTrue( $orders_by_id[ $refundable->get_id() ]['can_be_refunded'] );
		$this->assertFalse( $orders_by_id[ $pending->get_id() ]['can_be_refunded'] );
	}

	/**
	 * @testdox The can_be_refunded field is read-only and ignored in write requests.
	 */
	public function test_field_is_read_only(): void {
		$order = $this->create_order_with_product( 25.00, 1 );
		$order->set_status( OrderStatus::PENDING );
		$order->save();

		$request = new WP_REST_Request( 'PUT', '/wc/v3/orders/' . $order->get_id() );
		$request->set_body_params( array( 'can_be_refunded' => true ) );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertFalse( $response->get_data()['can_be_refunded'], 'A client-supplied can_be_refunded value must be ignored.' );
	}

	/**
	 * @testdox The field respects _fields filtering in both directions.
	 */
	public function test_fields_filtering(): void {
		$order = $this->create_order_with_product( 25.00, 1 );

		$request = new WP_REST_Request( 'GET', '/wc/v3/orders/' . $order->get_id() );
		$request->set_param( '_fields', 'id,can_be_refunded' );
		$data = $this->server->dispatch( $request )->get_data();
		$this->assertArrayHasKey( 'can_be_refunded', $data );
		$this->assertTrue( $data['can_be_refunded'] );

		$request = new WP_REST_Request( 'GET', '/wc/v3/orders/' . $order->get_id() );
		$request->set_param( '_fields', 'id,status' );
		$data = $this->server->dispatch( $request )->get_data();
		$this->assertArrayNotHasKey( 'can_be_refunded', $data );
	}

	/**
	 * @testdox The wc/v2 orders endpoint does not contain the field.
	 */
	public function test_v2_endpoint_does_not_contain_field(): void {
		$order = $this->create_order_with_product( 25.00, 1 );

		$request  = new WP_REST_Request( 'GET', '/wc/v2/orders/' . $order->get_id() );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertArrayNotHasKey( 'can_be_refunded', $data );
		$this->assertArrayNotHasKey( 'can_be_refunded', $data['line_items'][0] );
	}

	/**
	 * @testdox The schema declares can_be_refunded as a read-only boolean at order and line level.
	 */
	public function test_schema_declares_field(): void {
		$request  = new WP_REST_Request( 'OPTIONS', '/wc/v3/orders' );
		$response = $this->server->dispatch( $request );
		$schema   = $response->get_data()['schema'];

		$this->assertSame( 'boolean', $schema['properties']['can_be_refunded']['type'] );
		$this->assertTrue( $schema['properties']['can_be_refunded']['readonly'] );
		foreach ( array( 'line_items', 'shipping_lines', 'fee_lines' ) as $section ) {
			$this->assertArrayHasKey( 'can_be_refunded', $schema['properties'][ $section ]['items']['properties'], "The {$section} item schema should declare can_be_refunded." );
		}
	}

	/**
	 * @testdox A fully refunded fee line with tax is not refundable at zero-decimal precision.
	 */
	public function test_zero_decimal_currency_fee_line(): void {
		$order = $this->create_order_with_shipping_and_fee( 0.00, 100.00 );
		$items = $order->get_items( 'fee' );
		$fee   = reset( $items );

		wc_create_refund(
			array(
				'order_id'   => $order->get_id(),
				'amount'     => 100.00,
				'line_items' => array(
					$fee->get_id() => array(
						'qty'          => 0,
						'refund_total' => 100.00,
					),
				),
			)
		);

		add_filter( 'wc_get_price_decimals', '__return_zero' );

		try {
			$data = $this->get_order_response( $order->get_id() );

			$this->assertFalse( $data['fee_lines'][0]['can_be_refunded'], 'A fully refunded fee line must not be refundable at 0dp.' );
		} finally {
			remove_filter( 'wc_get_price_decimals', '__return_zero' );
		}
	}

	/**
	 * Create a completed order with a single product line item.
	 *
	 * @param float $unit_price Unit price.
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

		return $order;
	}

	/**
	 * Create a completed order with one shipping line and one fee line.
	 *
	 * @param float $shipping_total Shipping total.
	 * @param float $fee_total      Fee total.
	 * @return WC_Order
	 */
	private function create_order_with_shipping_and_fee( float $shipping_total, float $fee_total ): WC_Order {
		$order = wc_create_order();

		$shipping = new WC_Order_Item_Shipping();
		$shipping->set_props(
			array(
				'method_title' => 'Flat Rate',
				'total'        => $shipping_total,
			)
		);
		$shipping->save();
		$order->add_item( $shipping );

		$fee = new WC_Order_Item_Fee();
		$fee->set_props(
			array(
				'name'  => 'Service fee',
				'total' => $fee_total,
			)
		);
		$fee->save();
		$order->add_item( $fee );

		$order->set_total( $shipping_total + $fee_total );
		$order->set_status( OrderStatus::COMPLETED );
		$order->save();

		return $order;
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
	 * Fetch a single order through the v3 REST API and return the response data.
	 *
	 * @param int $order_id Order ID.
	 * @return array
	 */
	private function get_order_response( int $order_id ): array {
		$request  = new WP_REST_Request( 'GET', '/wc/v3/orders/' . $order_id );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		return $response->get_data();
	}
}
