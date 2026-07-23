<?php
declare( strict_types = 1 );

use Automattic\WooCommerce\Enums\OrderStatus;

/**
 * Tests for the `can_be_refunded` field on v4 order and line item responses.
 */
class WC_REST_Orders_V4_Can_Be_Refunded_Test extends WC_REST_Unit_Test_Case {

	/**
	 * Shared admin user ID, used for REST authentication across the class.
	 *
	 * @var int
	 */
	protected static $user_id;

	/**
	 * Enable the REST API v4 feature.
	 */
	private static function enable_rest_api_v4_feature(): void {
		add_filter(
			'woocommerce_admin_features',
			array( __CLASS__, 'add_v4_feature' ),
		);
	}

	/**
	 * Disable the REST API v4 feature.
	 */
	private static function disable_rest_api_v4_feature(): void {
		remove_filter(
			'woocommerce_admin_features',
			array( __CLASS__, 'add_v4_feature' ),
		);
	}

	/**
	 * Filter callback to add the rest-api-v4 feature.
	 *
	 * @param array $features Features array.
	 * @return array
	 */
	public static function add_v4_feature( array $features ): array {
		$features[] = 'rest-api-v4';
		return $features;
	}

	/**
	 * Create the shared admin user once for the whole class.
	 *
	 * @param object $factory Factory object.
	 */
	public static function wpSetUpBeforeClass( $factory ) {
		self::$user_id = $factory->user->create( array( 'role' => 'administrator' ) );
	}

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		self::enable_rest_api_v4_feature();
		parent::setUp();

		wp_set_current_user( self::$user_id );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		parent::tearDown();
		self::disable_rest_api_v4_feature();
	}

	/**
	 * Helper to get a single order via the v4 API.
	 *
	 * @param int $order_id Order ID.
	 * @return array Response data.
	 */
	private function get_order_response( int $order_id ): array {
		$request  = new WP_REST_Request( 'GET', '/wc/v4/orders/' . $order_id );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		return $response->get_data();
	}

	/**
	 * Helper to create an order with a product line item.
	 *
	 * @param string $status Order status.
	 * @return WC_Order
	 */
	private function create_order_with_product( string $status = 'completed' ): WC_Order {
		$product = WC_Helper_Product::create_simple_product( true, array( 'regular_price' => '10.00' ) );
		$order   = WC_Helper_Order::create_order( self::$user_id, $product );
		$order->set_status( $status );
		$order->save();
		$order->calculate_totals( true );

		return $order;
	}

	/**
	 * @testdox Fresh unrefunded order has can_be_refunded true at order and line item level.
	 */
	public function test_fresh_order_can_be_refunded(): void {
		$order = $this->create_order_with_product();
		$data  = $this->get_order_response( $order->get_id() );

		$this->assertTrue( $data['can_be_refunded'], 'Fresh order should be refundable' );
		$this->assertNotEmpty( $data['line_items'], 'Order should have line items' );
		$this->assertTrue( $data['line_items'][0]['can_be_refunded'], 'Fresh line item should be refundable' );
	}

	/**
	 * @testdox Fully refunded order has can_be_refunded false at order and line item level.
	 */
	public function test_fully_refunded_order(): void {
		$order     = $this->create_order_with_product();
		$line_item = current( $order->get_items() );

		wc_create_refund(
			array(
				'order_id'   => $order->get_id(),
				'amount'     => $order->get_total(),
				'line_items' => array(
					$line_item->get_id() => array(
						'qty'          => $line_item->get_quantity(),
						'refund_total' => $line_item->get_total(),
						'refund_tax'   => array(),
					),
				),
			)
		);

		$data = $this->get_order_response( $order->get_id() );

		$this->assertFalse( $data['can_be_refunded'], 'Fully refunded order should not be refundable' );
		$this->assertFalse( $data['line_items'][0]['can_be_refunded'], 'Fully refunded line item should not be refundable' );
	}

	/**
	 * @testdox Partially refunded order has mixed can_be_refunded values.
	 */
	public function test_partially_refunded_order(): void {
		$product_a = WC_Helper_Product::create_simple_product( true, array( 'regular_price' => '10.00' ) );
		$product_b = WC_Helper_Product::create_simple_product( true, array( 'regular_price' => '20.00' ) );

		$order = wc_create_order( array( 'customer_id' => self::$user_id ) );

		$item_a = new WC_Order_Item_Product();
		$item_a->set_props(
			array(
				'product'  => $product_a,
				'quantity' => 2,
				'subtotal' => 20,
				'total'    => 20,
			)
		);
		$order->add_item( $item_a );

		$item_b = new WC_Order_Item_Product();
		$item_b->set_props(
			array(
				'product'  => $product_b,
				'quantity' => 1,
				'subtotal' => 20,
				'total'    => 20,
			)
		);
		$order->add_item( $item_b );

		$order->set_status( 'completed' );
		$order->save();
		$order->calculate_totals( true );

		// Fully refund item A.
		wc_create_refund(
			array(
				'order_id'   => $order->get_id(),
				'amount'     => 20,
				'line_items' => array(
					$item_a->get_id() => array(
						'qty'          => 2,
						'refund_total' => 20,
						'refund_tax'   => array(),
					),
				),
			)
		);

		$data = $this->get_order_response( $order->get_id() );

		$this->assertTrue( $data['can_be_refunded'], 'Partially refunded order should still be refundable' );

		$items_by_product = array();
		foreach ( $data['line_items'] as $item ) {
			$items_by_product[ $item['product_id'] ] = $item;
		}

		$this->assertFalse(
			$items_by_product[ $product_a->get_id() ]['can_be_refunded'],
			'Fully refunded line item should not be refundable'
		);
		$this->assertTrue(
			$items_by_product[ $product_b->get_id() ]['can_be_refunded'],
			'Unrefunded line item should be refundable'
		);
	}

	/**
	 * @testdox Order with cancelled status cannot be refunded even if it has remaining amount.
	 */
	public function test_cancelled_order_with_remaining_amount_is_not_refundable(): void {
		$order = $this->create_order_with_product( 'cancelled' );
		$data  = $this->get_order_response( $order->get_id() );

		$this->assertFalse( $data['can_be_refunded'], 'Cancelled order should not be refundable regardless of remaining amount' );
	}

	/**
	 * @testdox Order with failed status cannot be refunded even if it has remaining amount.
	 */
	public function test_failed_order_with_remaining_amount_is_not_refundable(): void {
		$order = $this->create_order_with_product( 'failed' );
		$data  = $this->get_order_response( $order->get_id() );

		$this->assertFalse( $data['can_be_refunded'], 'Failed order should not be refundable regardless of remaining amount' );
	}

	/**
	 * @testdox Order with on-hold status can be refunded if it has remaining amount.
	 */
	public function test_on_hold_order_with_remaining_amount_is_refundable(): void {
		$order = $this->create_order_with_product( 'on-hold' );
		$data  = $this->get_order_response( $order->get_id() );

		$this->assertTrue( $data['can_be_refunded'], 'On-hold order with remaining amount should be refundable' );
	}

	/**
	 * @testdox Order with pending status cannot be refunded even if it has remaining amount.
	 */
	public function test_pending_order_is_not_refundable(): void {
		$order = $this->create_order_with_product( 'pending' );
		$data  = $this->get_order_response( $order->get_id() );

		$this->assertFalse( $data['can_be_refunded'], 'Pending order should not be refundable regardless of remaining amount' );
	}

	/**
	 * @testdox Order with refunded status and no remaining amount has can_be_refunded false.
	 */
	public function test_refunded_status_order_no_remaining_amount(): void {
		$order     = $this->create_order_with_product();
		$line_item = current( $order->get_items() );

		wc_create_refund(
			array(
				'order_id'   => $order->get_id(),
				'amount'     => $order->get_total(),
				'line_items' => array(
					$line_item->get_id() => array(
						'qty'          => $line_item->get_quantity(),
						'refund_total' => $line_item->get_total(),
						'refund_tax'   => array(),
					),
				),
			)
		);

		$data = $this->get_order_response( $order->get_id() );

		$this->assertFalse( $data['can_be_refunded'], 'Fully refunded order should not be refundable' );
	}

	/**
	 * @testdox Line item with product_id 0 has can_be_refunded false.
	 */
	public function test_line_item_without_product_not_refundable(): void {
		$order = wc_create_order( array( 'customer_id' => self::$user_id ) );

		$item = new WC_Order_Item_Product();
		$item->set_props(
			array(
				'name'     => 'Custom item',
				'quantity' => 1,
				'subtotal' => 10,
				'total'    => 10,
			)
		);
		$order->add_item( $item );
		$order->set_status( 'completed' );
		$order->save();
		$order->calculate_totals( true );

		$data = $this->get_order_response( $order->get_id() );

		$this->assertFalse( $data['line_items'][0]['can_be_refunded'], 'Line item with product_id=0 should not be refundable' );
	}

	/**
	 * @testdox List endpoint returns can_be_refunded for all orders with correct values.
	 */
	public function test_list_endpoint_returns_field(): void {
		$completed_order = $this->create_order_with_product();
		$cancelled_order = $this->create_order_with_product( 'cancelled' );

		$request  = new WP_REST_Request( 'GET', '/wc/v4/orders' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$orders_by_id = array();
		foreach ( $response->get_data() as $order_data ) {
			$this->assertArrayHasKey( 'can_be_refunded', $order_data, 'List endpoint should include can_be_refunded' );
			$orders_by_id[ $order_data['id'] ] = $order_data;
		}

		$this->assertTrue( $orders_by_id[ $completed_order->get_id() ]['can_be_refunded'], 'Completed order should be refundable' );
		$this->assertFalse( $orders_by_id[ $cancelled_order->get_id() ]['can_be_refunded'], 'Cancelled order should not be refundable' );
	}

	/**
	 * @testdox The can_be_refunded field is read-only and ignored in POST requests.
	 */
	public function test_field_is_read_only(): void {
		$product = WC_Helper_Product::create_simple_product( true, array( 'regular_price' => '10.00' ) );

		$request = new WP_REST_Request( 'POST', '/wc/v4/orders' );
		$request->set_body_params(
			array(
				'status'          => OrderStatus::COMPLETED,
				'can_be_refunded' => false,
				'line_items'      => array(
					array(
						'product_id' => $product->get_id(),
						'quantity'   => 1,
					),
				),
			)
		);
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 201, $response->get_status() );
		$data = $response->get_data();

		$this->assertTrue( $data['can_be_refunded'], 'can_be_refunded should be server-computed regardless of POST value' );
	}

	/**
	 * @testdox Shipping line with remaining amount has can_be_refunded true.
	 */
	public function test_shipping_line_can_be_refunded(): void {
		$order = WC_Helper_Order::create_order_with_fees_and_shipping( self::$user_id );
		$order->set_status( 'completed' );
		$order->save();
		$order->calculate_totals( true );

		$data = $this->get_order_response( $order->get_id() );

		$this->assertNotEmpty( $data['shipping_lines'], 'Order should have shipping lines' );
		$this->assertTrue( $data['shipping_lines'][0]['can_be_refunded'], 'Unrefunded shipping line should be refundable' );
	}

	/**
	 * @testdox Fee line with remaining amount has can_be_refunded true.
	 */
	public function test_fee_line_can_be_refunded(): void {
		$order = WC_Helper_Order::create_order_with_fees_and_shipping( self::$user_id );
		$order->set_status( 'completed' );
		$order->save();
		$order->calculate_totals( true );

		$data = $this->get_order_response( $order->get_id() );

		$this->assertNotEmpty( $data['fee_lines'], 'Order should have fee lines' );
		$this->assertTrue( $data['fee_lines'][0]['can_be_refunded'], 'Unrefunded fee line should be refundable' );
	}

	/**
	 * @testdox Fully refunded shipping line has can_be_refunded false.
	 */
	public function test_fully_refunded_shipping_line(): void {
		$order = WC_Helper_Order::create_order_with_fees_and_shipping( self::$user_id );
		$order->set_status( 'completed' );
		$order->save();
		$order->calculate_totals( true );

		$shipping_item = current( $order->get_items( 'shipping' ) );

		wc_create_refund(
			array(
				'order_id'   => $order->get_id(),
				'amount'     => $shipping_item->get_total(),
				'line_items' => array(
					$shipping_item->get_id() => array(
						'qty'          => 0,
						'refund_total' => $shipping_item->get_total(),
						'refund_tax'   => array(),
					),
				),
			)
		);

		$data = $this->get_order_response( $order->get_id() );

		$this->assertFalse( $data['shipping_lines'][0]['can_be_refunded'], 'Fully refunded shipping line should not be refundable' );
	}

	/**
	 * @testdox Fully refunded fee line has can_be_refunded false.
	 */
	public function test_fully_refunded_fee_line(): void {
		$order = WC_Helper_Order::create_order_with_fees_and_shipping( self::$user_id );
		$order->set_status( 'completed' );
		$order->save();
		$order->calculate_totals( true );

		$fee_item = current( $order->get_items( 'fee' ) );

		wc_create_refund(
			array(
				'order_id'   => $order->get_id(),
				'amount'     => $fee_item->get_total(),
				'line_items' => array(
					$fee_item->get_id() => array(
						'qty'          => 0,
						'refund_total' => $fee_item->get_total(),
						'refund_tax'   => array(),
					),
				),
			)
		);

		$data = $this->get_order_response( $order->get_id() );

		$this->assertFalse( $data['fee_lines'][0]['can_be_refunded'], 'Fully refunded fee line should not be refundable' );
	}

	/**
	 * @testdox Fully refunded shipping line with tax has can_be_refunded false.
	 */
	public function test_fully_refunded_shipping_line_with_tax(): void {
		$order = wc_create_order( array( 'customer_id' => self::$user_id ) );

		$shipping_item = new WC_Order_Item_Shipping();
		$shipping_item->set_props(
			array(
				'method_title' => 'Flat Rate',
				'total'        => '10.00',
			)
		);
		$shipping_item->set_taxes(
			array(
				'total' => array( 1 => '1.50' ),
			)
		);
		$order->add_item( $shipping_item );
		$order->set_status( 'completed' );
		$order->save();
		$order->update_taxes();
		$order->calculate_totals( false );

		wc_create_refund(
			array(
				'order_id'   => $order->get_id(),
				'amount'     => 11.50,
				'line_items' => array(
					$shipping_item->get_id() => array(
						'qty'          => 0,
						'refund_total' => 10.00,
						'refund_tax'   => array( 1 => 1.50 ),
					),
				),
			)
		);

		$data = $this->get_order_response( $order->get_id() );

		$this->assertFalse(
			$data['shipping_lines'][0]['can_be_refunded'],
			'Fully refunded shipping line with tax should not be refundable'
		);
	}

	/**
	 * @testdox Fully refunded fee line with tax has can_be_refunded false.
	 */
	public function test_fully_refunded_fee_line_with_tax(): void {
		$order = wc_create_order( array( 'customer_id' => self::$user_id ) );

		$fee_item = new WC_Order_Item_Fee();
		$fee_item->set_props(
			array(
				'name'  => 'Test Fee',
				'total' => '20.00',
			)
		);
		$fee_item->set_taxes(
			array(
				'total' => array( 1 => '3.00' ),
			)
		);
		$order->add_item( $fee_item );
		$order->set_status( 'completed' );
		$order->save();
		$order->update_taxes();
		$order->calculate_totals( false );

		wc_create_refund(
			array(
				'order_id'   => $order->get_id(),
				'amount'     => 23.00,
				'line_items' => array(
					$fee_item->get_id() => array(
						'qty'          => 0,
						'refund_total' => 20.00,
						'refund_tax'   => array( 1 => 3.00 ),
					),
				),
			)
		);

		$data = $this->get_order_response( $order->get_id() );

		$this->assertFalse(
			$data['fee_lines'][0]['can_be_refunded'],
			'Fully refunded fee line with tax should not be refundable'
		);
	}

	/**
	 * @testdox Partially refunded shipping line with tax keeps can_be_refunded true.
	 *
	 * Regression test for WOOPLUG-6819: refunded fee/shipping totals from
	 * DataUtils::compute_refunded_quantities_and_totals() are tax-inclusive,
	 * so OrderSchema::can_be_refunded must compare against tax-inclusive line totals.
	 */
	public function test_partially_refunded_shipping_line_with_tax(): void {
		$order = wc_create_order( array( 'customer_id' => self::$user_id ) );

		$shipping_item = new WC_Order_Item_Shipping();
		$shipping_item->set_props(
			array(
				'method_title' => 'Flat Rate',
				'total'        => '10.00',
			)
		);
		$shipping_item->set_taxes(
			array(
				'total' => array( 1 => '1.50' ),
			)
		);
		$order->add_item( $shipping_item );
		$order->set_status( 'completed' );
		$order->save();
		$order->update_taxes();
		$order->calculate_totals( false );

		wc_create_refund(
			array(
				'order_id'   => $order->get_id(),
				'amount'     => 10.35,
				'line_items' => array(
					$shipping_item->get_id() => array(
						'qty'          => 0,
						'refund_total' => 9.00,
						'refund_tax'   => array( 1 => 1.35 ),
					),
				),
			)
		);

		$data = $this->get_order_response( $order->get_id() );

		$this->assertTrue(
			$data['shipping_lines'][0]['can_be_refunded'],
			'Partially refunded shipping line with tax should remain refundable'
		);
	}

	/**
	 * @testdox Partially refunded fee line with tax keeps can_be_refunded true.
	 *
	 * Regression test for WOOPLUG-6819.
	 */
	public function test_partially_refunded_fee_line_with_tax(): void {
		$order = wc_create_order( array( 'customer_id' => self::$user_id ) );

		$fee_item = new WC_Order_Item_Fee();
		$fee_item->set_props(
			array(
				'name'  => 'Test Fee',
				'total' => '20.00',
			)
		);
		$fee_item->set_taxes(
			array(
				'total' => array( 1 => '3.00' ),
			)
		);
		$order->add_item( $fee_item );
		$order->set_status( 'completed' );
		$order->save();
		$order->update_taxes();
		$order->calculate_totals( false );

		// Refunded tax-inclusive total (20.50) exceeds the tax-exclusive line total (20.00) but
		// not the tax-inclusive total (23.00). Under the old buggy comparison this flipped
		// can_be_refunded to false even though $2.50 of fee + tax remains refundable.
		wc_create_refund(
			array(
				'order_id'   => $order->get_id(),
				'amount'     => 20.50,
				'line_items' => array(
					$fee_item->get_id() => array(
						'qty'          => 0,
						'refund_total' => 18.00,
						'refund_tax'   => array( 1 => 2.50 ),
					),
				),
			)
		);

		$data = $this->get_order_response( $order->get_id() );

		$this->assertTrue(
			$data['fee_lines'][0]['can_be_refunded'],
			'Partially refunded fee line with tax should remain refundable'
		);
	}

	/**
	 * @testdox Zero-priced product line item with quantity follows quantity logic.
	 */
	public function test_zero_priced_item_follows_quantity_logic(): void {
		$product = WC_Helper_Product::create_simple_product( true, array( 'regular_price' => '0.00' ) );
		$order   = wc_create_order( array( 'customer_id' => self::$user_id ) );

		$item = new WC_Order_Item_Product();
		$item->set_props(
			array(
				'product'  => $product,
				'quantity' => 2,
				'subtotal' => 0,
				'total'    => 0,
			)
		);
		$order->add_item( $item );
		$order->set_status( 'completed' );
		$order->save();
		$order->calculate_totals( true );

		$data = $this->get_order_response( $order->get_id() );

		$this->assertTrue(
			$data['line_items'][0]['can_be_refunded'],
			'Zero-priced item with remaining quantity should be refundable'
		);
	}

	/**
	 * @testdox Discount (negative-total) fee line with no prior refund reports can_be_refunded true.
	 *
	 * Regression for WOOPLUG-6829: the comparison omitted abs(), so a negative
	 * discount fee (e.g. a loyalty credit) reported can_be_refunded=false even
	 * though the refund validator accepts it as part of a mixed refund.
	 */
	public function test_negative_fee_line_can_be_refunded(): void {
		$product = WC_Helper_Product::create_simple_product( true, array( 'regular_price' => '50.00' ) );
		$order   = wc_create_order( array( 'customer_id' => self::$user_id ) );

		$item = new WC_Order_Item_Product();
		$item->set_props(
			array(
				'product'  => $product,
				'quantity' => 1,
				'subtotal' => 50,
				'total'    => 50,
			)
		);
		$order->add_item( $item );

		$fee = new WC_Order_Item_Fee();
		$fee->set_props(
			array(
				'name'  => 'Loyalty discount',
				'total' => '-10.00',
			)
		);
		$order->add_item( $fee );

		$order->set_status( 'completed' );
		$order->save();
		$order->calculate_totals( false );

		$data = $this->get_order_response( $order->get_id() );

		$this->assertNotEmpty( $data['fee_lines'], 'Order should have fee lines' );
		$this->assertTrue(
			$data['fee_lines'][0]['can_be_refunded'],
			'A discount (negative) fee with no prior refund should be refundable.'
		);
	}
}
