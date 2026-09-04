<?php
declare( strict_types=1 );

use Automattic\WooCommerce\Enums\OrderStatus;

/**
 * Integration tests for the POST /wc/v3/orders/<order_id>/refunds/preview endpoint.
 *
 * @group order-refunds-preview
 */
class WC_REST_Order_Refunds_Preview_Test extends WC_REST_Unit_Test_Case {

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
				'user_login' => 'v3_preview_admin_' . wp_generate_password( 6, false ),
				'user_email' => 'v3_preview_admin_' . wp_generate_password( 6, false ) . '@example.com',
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
	 * Runs after each test.
	 */
	public function tearDown(): void {
		remove_all_filters( 'woocommerce_rest_prepare_order_refund_preview' );

		global $wp_rest_additional_fields;
		unset( $wp_rest_additional_fields['order_refund_preview'] );

		parent::tearDown();
	}

	/**
	 * @testdox The preview route is registered under wc/v3.
	 */
	public function test_preview_route_is_registered(): void {
		$routes = $this->server->get_routes();

		$this->assertArrayHasKey( '/wc/v3/orders/(?P<order_id>[\d]+)/refunds/preview', $routes );
	}

	/**
	 * @testdox Preview a single full line item with no tax returns correct totals.
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

		$this->assertEquals( '100.00', $data['subtotal'] );
		$this->assertEquals( '0.00', $data['tax'] );
		$this->assertEquals( '100.00', $data['total'] );
		$this->assertCount( 1, $data['breakdown']['products']['items'] );
		$this->assertEquals( 2, $data['breakdown']['products']['items'][0]['quantity'] );
	}

	/**
	 * @testdox Preview a single line item with 10% tax extracts tax correctly.
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

		$this->assertEquals( '100.00', $data['subtotal'] );
		$this->assertEquals( '10.00', $data['tax'] );
		$this->assertEquals( '110.00', $data['total'] );
	}

	/**
	 * @testdox Preview partial quantity returns proportional totals.
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
	 * @testdox Preview multiple line items returns aggregated totals.
	 */
	public function test_preview_multiple_line_items(): void {
		$order = $this->create_order_with_two_products( 20.00, 30.00 );
		$items = array_values( $order->get_items( 'line_item' ) );

		$response = $this->do_preview_request(
			$order->get_id(),
			array(
				array(
					'line_item_id' => $items[0]->get_id(),
					'quantity'     => 1,
				),
				array(
					'line_item_id' => $items[1]->get_id(),
					'quantity'     => 1,
				),
			)
		);

		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();

		$this->assertEquals( '50.00', $data['total'], '20 + 30 = 50' );
		$this->assertCount( 2, $data['breakdown']['products']['items'] );
	}

	/**
	 * @testdox Preview a shipping line returns the shipping breakdown section.
	 */
	public function test_preview_shipping_line(): void {
		$order = $this->create_order_with_shipping( 15.00 );
		$items = $order->get_items( 'shipping' );
		$item  = reset( $items );

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

		$this->assertEquals( '15.00', $data['total'] );
		$this->assertCount( 1, $data['breakdown']['shipping']['items'] );
		$this->assertCount( 0, $data['breakdown']['products']['items'] );
	}

	/**
	 * @testdox Preview a fee line returns the fees breakdown section.
	 */
	public function test_preview_fee_line(): void {
		$order = $this->create_order_with_fee( 7.50 );
		$items = $order->get_items( 'fee' );
		$item  = reset( $items );

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

		$this->assertEquals( '7.50', $data['total'] );
		$this->assertCount( 1, $data['breakdown']['fees']['items'] );
	}

	/**
	 * @testdox An explicit refund_total overrides the quantity for the refunded amount.
	 */
	public function test_preview_refund_total_overrides_quantity(): void {
		$order   = $this->create_order_with_product( 50.00, 2 );
		$item_id = $this->get_first_line_item_id( $order );

		$response = $this->do_preview_request(
			$order->get_id(),
			array(
				array(
					'line_item_id' => $item_id,
					'quantity'     => 2,
					'refund_total' => 30.00,
				),
			)
		);

		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();

		$this->assertEquals( '30.00', $data['total'], 'refund_total should override the quantity-derived amount' );
	}

	/**
	 * @testdox A partial-amount preview splits tax so subtotal + tax reconstitutes the total.
	 */
	public function test_preview_partial_amount_tax_split_reconstitutes_total(): void {
		$tax_rate_id = $this->create_tax_rate( 10.0 );
		// $100 net + $10 tax = $110 incl. Refund $55 (half): expect 50.00 net + 5.00 tax.
		$order   = $this->create_order_with_product_and_tax( 100.00, 1, $tax_rate_id, 10.00 );
		$item_id = $this->get_first_line_item_id( $order );

		$response = $this->do_preview_request(
			$order->get_id(),
			array(
				array(
					'line_item_id' => $item_id,
					'refund_total' => 55.00,
				),
			)
		);

		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();
		$item = $data['breakdown']['products']['items'][0];

		$this->assertEquals( '50.00', $item['subtotal'], 'Net subtotal should be half of the $100 net.' );
		$this->assertEquals( '5.00', $item['tax'], 'Tax should be half of the $10 stored tax.' );
		$this->assertEquals( '55.00', $item['total'], 'Total should equal the requested refund_total.' );
		$this->assertEquals(
			$item['total'],
			wc_format_decimal( (float) $item['subtotal'] + (float) $item['tax'], wc_get_price_decimals() ),
			'subtotal + tax must reconstitute the total to the cent.'
		);
	}

	/**
	 * @testdox Preview with quantity exceeding refundable units returns quantity_exceeds_refundable.
	 */
	public function test_preview_quantity_exceeds_refundable(): void {
		$order   = $this->create_order_with_product( 25.00, 2 );
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

		$response = $this->do_preview_request(
			$order->get_id(),
			array(
				array(
					'line_item_id' => $item_id,
					'quantity'     => 2,
				),
			)
		);

		$this->assertEquals( 422, $response->get_status() );
		$this->assertEquals( 'woocommerce_rest_quantity_exceeds_refundable', $response->get_data()['code'] );
	}

	/**
	 * @testdox Preview returns 422 when the total exceeds the remaining refundable amount.
	 */
	public function test_preview_returns_422_when_total_exceeds_max_refundable(): void {
		$order   = $this->create_order_with_product( 50.00, 2 );
		$item_id = $this->get_first_line_item_id( $order );

		// Amount-only partial refund leaves the line untouched but shrinks the
		// order's remaining refundable amount to $40.
		wc_create_refund(
			array(
				'order_id' => $order->get_id(),
				'amount'   => 60.00,
			)
		);

		$response = $this->do_preview_request(
			$order->get_id(),
			array(
				array(
					'line_item_id' => $item_id,
					'quantity'     => 2,
				),
			)
		);

		$this->assertEquals( 422, $response->get_status() );
		$this->assertEquals( 'woocommerce_rest_preview_exceeds_max_refundable', $response->get_data()['code'] );
	}

	/**
	 * @testdox Preview on a fully refunded order returns order_not_refundable.
	 */
	public function test_preview_fully_refunded_order(): void {
		$order   = $this->create_order_with_product( 30.00, 1 );
		$item_id = $this->get_first_line_item_id( $order );

		wc_create_refund(
			array(
				'order_id'   => $order->get_id(),
				'amount'     => 30.00,
				'line_items' => array(
					$item_id => array(
						'qty'          => 1,
						'refund_total' => 30.00,
					),
				),
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

		$this->assertEquals( 422, $response->get_status() );
		$this->assertEquals( 'woocommerce_rest_order_not_refundable', $response->get_data()['code'] );
	}

	/**
	 * @testdox An amount-form preview of an already fully refunded line returns line_item_already_refunded.
	 */
	public function test_preview_fully_refunded_line(): void {
		$order = $this->create_order_with_two_products( 20.00, 30.00 );
		$items = array_values( $order->get_items( 'line_item' ) );

		wc_create_refund(
			array(
				'order_id'   => $order->get_id(),
				'amount'     => 20.00,
				'line_items' => array(
					$items[0]->get_id() => array(
						'qty'          => 1,
						'refund_total' => 20.00,
					),
				),
			)
		);

		$response = $this->do_preview_request(
			$order->get_id(),
			array(
				array(
					'line_item_id' => $items[0]->get_id(),
					'refund_total' => 5.00,
				),
			)
		);

		$this->assertEquals( 422, $response->get_status() );
		$this->assertEquals( 'woocommerce_rest_line_item_already_refunded', $response->get_data()['code'] );
	}

	/**
	 * @testdox Preview with an empty line_items array is rejected by the schema.
	 */
	public function test_preview_empty_line_items(): void {
		$order = $this->create_order_with_product( 10.00, 1 );

		$response = $this->do_preview_request( $order->get_id(), array() );

		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'rest_invalid_param', $response->get_data()['code'] );
	}

	/**
	 * @testdox Preview with the same line item twice returns duplicate_line_item.
	 */
	public function test_preview_duplicate_line_item(): void {
		$order   = $this->create_order_with_product( 10.00, 2 );
		$item_id = $this->get_first_line_item_id( $order );

		$response = $this->do_preview_request(
			$order->get_id(),
			array(
				array(
					'line_item_id' => $item_id,
					'quantity'     => 1,
				),
				array(
					'line_item_id' => $item_id,
					'quantity'     => 1,
				),
			)
		);

		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'woocommerce_rest_duplicate_line_item', $response->get_data()['code'] );
	}

	/**
	 * @testdox Preview with a line item from a different order returns line_item_not_found.
	 */
	public function test_preview_cross_order_line_item(): void {
		$order       = $this->create_order_with_product( 10.00, 1 );
		$other_order = $this->create_order_with_product( 10.00, 1 );

		$response = $this->do_preview_request(
			$order->get_id(),
			array(
				array(
					'line_item_id' => $this->get_first_line_item_id( $other_order ),
					'quantity'     => 1,
				),
			)
		);

		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'woocommerce_rest_line_item_not_found', $response->get_data()['code'] );
	}

	/**
	 * @testdox Preview with a zero refund_total returns invalid_refund_total.
	 */
	public function test_preview_zero_refund_total(): void {
		$order   = $this->create_order_with_product( 10.00, 1 );
		$item_id = $this->get_first_line_item_id( $order );

		$response = $this->do_preview_request(
			$order->get_id(),
			array(
				array(
					'line_item_id' => $item_id,
					'refund_total' => 0,
				),
			)
		);

		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'woocommerce_rest_invalid_refund_total', $response->get_data()['code'] );
	}

	/**
	 * @testdox Preview with a null refund_total and a quantity falls back to the quantity amount.
	 */
	public function test_preview_null_refund_total_with_quantity_uses_quantity(): void {
		$order   = $this->create_order_with_product( 25.00, 2 );
		$item_id = $this->get_first_line_item_id( $order );

		$response = $this->do_preview_request(
			$order->get_id(),
			array(
				array(
					'line_item_id' => $item_id,
					'quantity'     => 1,
					'refund_total' => null,
				),
			)
		);

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( '25.00', $response->get_data()['total'] );
	}

	/**
	 * @testdox Preview with neither quantity nor refund_total returns missing_quantity_or_refund_total.
	 */
	public function test_preview_missing_quantity_and_refund_total(): void {
		$order   = $this->create_order_with_product( 10.00, 1 );
		$item_id = $this->get_first_line_item_id( $order );

		$response = $this->do_preview_request(
			$order->get_id(),
			array(
				array(
					'line_item_id' => $item_id,
				),
			)
		);

		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'woocommerce_rest_missing_quantity_or_refund_total', $response->get_data()['code'] );
	}

	/**
	 * @testdox Preview with an unknown order ID returns woocommerce_rest_invalid_order_id.
	 */
	public function test_preview_unknown_order_id(): void {
		$response = $this->do_preview_request(
			999999999,
			array(
				array(
					'line_item_id' => 1,
					'quantity'     => 1,
				),
			)
		);

		$this->assertEquals( 404, $response->get_status() );
		$this->assertEquals( 'woocommerce_rest_invalid_order_id', $response->get_data()['code'] );
	}

	/**
	 * @testdox Preview with a refund ID as the order ID is rejected with a 404.
	 */
	public function test_preview_refund_id_rejected(): void {
		$order   = $this->create_order_with_product( 30.00, 1 );
		$item_id = $this->get_first_line_item_id( $order );

		$refund = wc_create_refund(
			array(
				'order_id' => $order->get_id(),
				'amount'   => 10.00,
			)
		);

		$response = $this->do_preview_request(
			$refund->get_id(),
			array(
				array(
					'line_item_id' => $item_id,
					'quantity'     => 1,
				),
			)
		);

		$this->assertEquals( 404, $response->get_status() );
		$this->assertEquals( 'woocommerce_rest_invalid_order_id', $response->get_data()['code'] );
	}

	/**
	 * @testdox Preview requires authentication.
	 */
	public function test_preview_unauthenticated(): void {
		$order   = $this->create_order_with_product( 10.00, 1 );
		$item_id = $this->get_first_line_item_id( $order );

		wp_set_current_user( 0 );

		$response = $this->do_preview_request(
			$order->get_id(),
			array(
				array(
					'line_item_id' => $item_id,
					'quantity'     => 1,
				),
			)
		);

		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * @testdox Preview is forbidden for users without the create-refund capability.
	 */
	public function test_preview_forbidden_for_customer(): void {
		$order   = $this->create_order_with_product( 10.00, 1 );
		$item_id = $this->get_first_line_item_id( $order );

		$customer_id = wp_insert_user(
			array(
				'user_login' => 'v3_preview_customer_' . wp_generate_password( 6, false ),
				'user_email' => 'v3_preview_customer_' . wp_generate_password( 6, false ) . '@example.com',
				'user_pass'  => 'password',
				'role'       => 'customer',
			)
		);
		wp_set_current_user( $customer_id );

		$response = $this->do_preview_request(
			$order->get_id(),
			array(
				array(
					'line_item_id' => $item_id,
					'quantity'     => 1,
				),
			)
		);

		$this->assertEquals( 403, $response->get_status() );

		wp_delete_user( $customer_id );
	}

	/**
	 * @testdox Preview does not create a refund.
	 */
	public function test_preview_does_not_create_refund(): void {
		$order   = $this->create_order_with_product( 10.00, 1 );
		$item_id = $this->get_first_line_item_id( $order );

		$this->do_preview_request(
			$order->get_id(),
			array(
				array(
					'line_item_id' => $item_id,
					'quantity'     => 1,
				),
			)
		);

		$order = wc_get_order( $order->get_id() );
		$this->assertCount( 0, $order->get_refunds(), 'Preview must not create a refund' );
	}

	/**
	 * @testdox Preview totals match a refund subsequently created through the v3 create endpoint.
	 */
	public function test_preview_matches_create(): void {
		$tax_rate_id = $this->create_tax_rate( 10.0 );
		$order       = $this->create_order_with_product_and_tax( 100.00, 1, $tax_rate_id, 10.00 );
		$item_id     = $this->get_first_line_item_id( $order );

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
		$preview      = $preview_response->get_data();
		$preview_item = $preview['breakdown']['products']['items'][0];

		// Drive the create request from the preview breakdown so a divergence between
		// preview and create produces an actual mismatch rather than passing by
		// coincidence. v3 create semantics: refund_total is net, refund_tax explicit.
		$create_request = new WP_REST_Request( 'POST', '/wc/v3/orders/' . $order->get_id() . '/refunds' );
		$create_request->set_body_params(
			array(
				'line_items' => array(
					array(
						'id'           => $item_id,
						'quantity'     => 1,
						'refund_total' => (float) $preview_item['subtotal'],
						'refund_tax'   => array(
							array(
								'id'           => $tax_rate_id,
								'refund_total' => (float) $preview_item['tax'],
							),
						),
					),
				),
				'api_refund' => false,
			)
		);
		$create_response = $this->server->dispatch( $create_request );
		$this->assertEquals( 201, $create_response->get_status() );

		$this->assertEquals(
			$preview['total'],
			$create_response->get_data()['amount'],
			'Preview total must match the created refund amount exactly'
		);
	}

	/**
	 * @testdox Preview rounds to a whole unit in a zero-decimal currency.
	 */
	public function test_preview_zero_decimal_currency(): void {
		$tax_rate_id = $this->create_tax_rate( 10.0 );
		// $100 net + $10 tax = $110 incl, stored at 2dp before the currency switch.
		$order   = $this->create_order_with_product_and_tax( 100.00, 1, $tax_rate_id, 10.00 );
		$item_id = $this->get_first_line_item_id( $order );

		add_filter( 'wc_get_price_decimals', '__return_zero' );

		try {
			$response = $this->do_preview_request(
				$order->get_id(),
				array(
					array(
						'line_item_id' => $item_id,
						'refund_total' => 55.4,
					),
				)
			);

			$this->assertEquals( 200, $response->get_status() );
			$item = $response->get_data()['breakdown']['products']['items'][0];

			// 55.4 rounds to 55 at 0dp; the 10% split gives whole-unit 50 net / 5 tax.
			$this->assertEquals( '55', $item['total'], 'Total should round to a whole unit.' );
			$this->assertEquals( '5', $item['tax'] );
			$this->assertEquals( '50', $item['subtotal'] );
		} finally {
			remove_filter( 'wc_get_price_decimals', '__return_zero' );
		}
	}

	/**
	 * @testdox Preview keeps three decimal places in a three-decimal currency.
	 */
	public function test_preview_three_decimal_currency(): void {
		$three_decimals = function () {
			return 3;
		};
		add_filter( 'wc_get_price_decimals', $three_decimals );

		try {
			$order   = $this->create_order_with_product( 10.555, 1 );
			$item_id = $this->get_first_line_item_id( $order );

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
			$this->assertEquals( '10.555', $response->get_data()['total'], 'Three-decimal precision should be preserved.' );
		} finally {
			remove_filter( 'wc_get_price_decimals', $three_decimals );
		}
	}

	/**
	 * @testdox A field registered for order_refund_preview is populated in the response and advertised in the schema.
	 */
	public function test_preview_registered_rest_field_in_response_and_schema(): void {
		register_rest_field(
			'order_refund_preview',
			'registered_field',
			array(
				'get_callback' => function () {
					return 'registered_value';
				},
				'schema'       => array(
					'description' => 'Test field.',
					'type'        => 'string',
				),
			)
		);

		$order   = $this->create_order_with_product( 10.00, 1 );
		$item_id = $this->get_first_line_item_id( $order );

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
		$this->assertEquals( 'registered_value', $response->get_data()['registered_field'] );

		$options = new WP_REST_Request( 'OPTIONS', '/wc/v3/orders/' . $order->get_id() . '/refunds/preview' );
		$schema  = $this->server->dispatch( $options )->get_data()['schema'];
		$this->assertArrayHasKey( 'registered_field', $schema['properties'] );
	}

	/**
	 * @testdox A registered field without a schema is still populated, matching core back-compat.
	 */
	public function test_preview_schema_less_registered_field_is_populated(): void {
		register_rest_field(
			'order_refund_preview',
			'schema_less_field',
			array(
				'get_callback' => function () {
					return 'schema_less_value';
				},
			)
		);

		$order   = $this->create_order_with_product( 10.00, 1 );
		$item_id = $this->get_first_line_item_id( $order );

		$response = $this->do_preview_request(
			$order->get_id(),
			array(
				array(
					'line_item_id' => $item_id,
					'quantity'     => 1,
				),
			)
		);

		// Core deliberately includes fields registered without a schema; a
		// schema-derived allowlist alone would silently drop them.
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 'schema_less_value', $response->get_data()['schema_less_field'] );
	}

	/**
	 * @testdox A registered field's callback does not run when _fields excludes it.
	 */
	public function test_preview_registered_field_callback_skipped_when_not_requested(): void {
		$executed = false;
		register_rest_field(
			'order_refund_preview',
			'expensive_field',
			array(
				'get_callback' => function () use ( &$executed ) {
					$executed = true;
					return 'expensive_value';
				},
				'schema'       => array(
					'description' => 'Test field.',
					'type'        => 'string',
				),
			)
		);

		$order   = $this->create_order_with_product( 10.00, 1 );
		$item_id = $this->get_first_line_item_id( $order );

		$request = new WP_REST_Request( 'POST', '/wc/v3/orders/' . $order->get_id() . '/refunds/preview' );
		$request->set_body_params(
			array(
				'line_items' => array(
					array(
						'line_item_id' => $item_id,
						'quantity'     => 1,
					),
				),
			)
		);
		$request->set_param( '_fields', 'total' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertFalse( $executed, 'The excluded field callback must not execute.' );
		$data = rest_filter_response_fields( $response, $this->server, $request )->get_data();
		$this->assertArrayNotHasKey( 'expensive_field', $data );
		$this->assertArrayHasKey( 'total', $data );
	}

	/**
	 * @testdox The woocommerce_rest_prepare_order_refund_preview filter receives the response object and can mutate it.
	 */
	public function test_preview_filter_can_mutate_response(): void {
		$order   = $this->create_order_with_product( 10.00, 1 );
		$item_id = $this->get_first_line_item_id( $order );

		$received_order = null;
		add_filter(
			'woocommerce_rest_prepare_order_refund_preview',
			function ( $response, $filter_order ) use ( &$received_order ) {
				$this->assertInstanceOf( WP_REST_Response::class, $response, 'The filter should receive the response object, per the woocommerce_rest_prepare_* family contract.' );
				$received_order       = $filter_order;
				$data                 = $response->get_data();
				$data['custom_field'] = 'custom_value';
				$response->set_data( $data );
				return $response;
			},
			10,
			2
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
		$this->assertEquals( 'custom_value', $response->get_data()['custom_field'] );
		$this->assertInstanceOf( WC_Order::class, $received_order );
		$this->assertEquals( $order->get_id(), $received_order->get_id() );
	}

	/**
	 * @testdox Numeric strings in the preview payload are sanitized to their schema types before computation.
	 */
	public function test_preview_numeric_strings_are_sanitized(): void {
		$order   = $this->create_order_with_product( 25.00, 2 );
		$item_id = $this->get_first_line_item_id( $order );

		$response = $this->do_preview_request(
			$order->get_id(),
			array(
				array(
					'line_item_id' => (string) $item_id,
					'quantity'     => '2',
				),
			)
		);

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( '50.00', $response->get_data()['total'] );
	}

	/**
	 * @testdox The preview schema is exposed with the order_refund_preview title and expected properties.
	 */
	public function test_preview_schema_shape(): void {
		$order = $this->create_order_with_product( 10.00, 1 );

		$request  = new WP_REST_Request( 'OPTIONS', '/wc/v3/orders/' . $order->get_id() . '/refunds/preview' );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 'order_refund_preview', $data['schema']['title'] );
		foreach ( array( 'breakdown', 'subtotal', 'tax', 'total', 'max_refundable' ) as $property ) {
			$this->assertArrayHasKey( $property, $data['schema']['properties'], "Schema should declare the {$property} property." );
		}
	}

	/**
	 * @testdox A negative refund_total is accepted for a discount line when the aggregate stays positive.
	 */
	public function test_preview_negative_refund_total_on_discount_line(): void {
		$order = $this->create_order_with_product_and_discount( 50.00, 1, -10.00 );

		$product_item_id  = $this->get_first_line_item_id( $order );
		$discount_item_id = 0;
		foreach ( $order->get_items( 'fee' ) as $item ) {
			$discount_item_id = $item->get_id();
		}
		$this->assertGreaterThan( 0, $discount_item_id, 'Order should carry a discount fee line.' );

		$response = $this->do_preview_request(
			$order->get_id(),
			array(
				array(
					'line_item_id' => $product_item_id,
					'quantity'     => 1,
				),
				array(
					'line_item_id' => $discount_item_id,
					'refund_total' => -10.00,
				),
			)
		);

		// The discount line's negative amount nets against the product: 50 - 10 = 40.
		// A discount-only preview would be rejected by the aggregate guard by design;
		// this pairing is where the per-line sign rule and that guard interact.
		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertEquals( '40.00', $data['total'] );
	}

	/**
	 * @testdox An unknown key inside a line item is rejected by the argument schema.
	 */
	public function test_preview_unknown_line_item_key_rejected(): void {
		$order   = $this->create_order_with_product( 10.00, 1 );
		$item_id = $this->get_first_line_item_id( $order );

		$response = $this->do_preview_request(
			$order->get_id(),
			array(
				array(
					'line_item_id' => $item_id,
					'quantity'     => 1,
					'unexpected'   => 'value',
				),
			)
		);

		// additionalProperties => false: clients relying on strict payloads (the
		// mobile apps send exactly the declared keys) get an explicit rejection
		// rather than silently ignored input.
		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'rest_invalid_param', $response->get_data()['code'] );
	}

	/**
	 * @testdox GET on the preview route is rejected; only POST is registered.
	 */
	public function test_preview_get_method_rejected(): void {
		$order = $this->create_order_with_product( 10.00, 1 );

		$request  = new WP_REST_Request( 'GET', '/wc/v3/orders/' . $order->get_id() . '/refunds/preview' );
		$response = $this->server->dispatch( $request );

		// WP answers a wrong method on this route with `rest_no_route` — the same
		// code clients use as the "endpoint missing" signal, so a client mistakenly
		// issuing GET would look like an ineligible store rather than a client bug.
		$this->assertEquals( 404, $response->get_status() );
		$this->assertEquals( 'rest_no_route', $response->get_data()['code'] );
	}

	/**
	 * @testdox max_refundable reflects the remaining refundable amount after a prior partial refund.
	 */
	public function test_preview_max_refundable_value_after_partial_refund(): void {
		$order   = $this->create_order_with_product( 50.00, 2 );
		$item_id = $this->get_first_line_item_id( $order );

		$refund = wc_create_refund(
			array(
				'order_id' => $order->get_id(),
				'amount'   => 30.00,
				'reason'   => 'Partial refund',
			)
		);
		$this->assertNotWPError( $refund );

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
		$this->assertEquals( '50.00', $data['total'] );
		$this->assertEquals( '70.00', $data['max_refundable'] );
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

		$product->delete( true );

		return $order;
	}

	/**
	 * Create a completed order with two single-quantity product line items.
	 *
	 * @param float $price_a Price of the first product.
	 * @param float $price_b Price of the second product.
	 * @return WC_Order
	 */
	private function create_order_with_two_products( float $price_a, float $price_b ): WC_Order {
		$order = wc_create_order();

		foreach ( array( $price_a, $price_b ) as $price ) {
			$product = WC_Helper_Product::create_simple_product();
			$product->set_regular_price( $price );
			$product->save();

			$item = new WC_Order_Item_Product();
			$item->set_props(
				array(
					'product'  => $product,
					'quantity' => 1,
					'subtotal' => $price,
					'total'    => $price,
				)
			);
			$item->save();
			$order->add_item( $item );

			$product->delete( true );
		}

		$order->set_total( $price_a + $price_b );
		$order->set_status( OrderStatus::COMPLETED );
		$order->save();

		return $order;
	}

	/**
	 * Create a completed order with a single shipping line.
	 *
	 * @param float $total Shipping total.
	 * @return WC_Order
	 */
	private function create_order_with_shipping( float $total ): WC_Order {
		$order    = wc_create_order();
		$shipping = new WC_Order_Item_Shipping();
		$shipping->set_props(
			array(
				'method_title' => 'Flat Rate',
				'total'        => $total,
			)
		);
		$shipping->save();
		$order->add_item( $shipping );
		$order->set_total( $total );
		$order->set_status( OrderStatus::COMPLETED );
		$order->save();

		return $order;
	}

	/**
	 * Create a completed order with a single fee line.
	 *
	 * @param float $total Fee total.
	 * @return WC_Order
	 */
	private function create_order_with_fee( float $total ): WC_Order {
		$order = wc_create_order();
		$fee   = new WC_Order_Item_Fee();
		$fee->set_props(
			array(
				'name'  => 'Service fee',
				'total' => $total,
			)
		);
		$fee->save();
		$order->add_item( $fee );
		$order->set_total( $total );
		$order->set_status( OrderStatus::COMPLETED );
		$order->save();

		return $order;
	}

	/**
	 * Create a completed order with a product line and a negative (discount) fee line.
	 *
	 * @param float $unit_price Product unit price.
	 * @param int   $quantity   Product quantity.
	 * @param float $discount   Negative fee total representing the discount.
	 * @return WC_Order
	 */
	private function create_order_with_product_and_discount( float $unit_price, int $quantity, float $discount ): WC_Order {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( $unit_price );
		$product->save();

		$order = wc_create_order();
		$order->add_product( $product, $quantity );

		$fee = new WC_Order_Item_Fee();
		$fee->set_props(
			array(
				'name'  => 'Discount',
				'total' => $discount,
			)
		);
		$fee->save();
		$order->add_item( $fee );

		$order->calculate_totals();
		$order->set_status( OrderStatus::COMPLETED );
		$order->save();

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
		$request = new WP_REST_Request( 'POST', '/wc/v3/orders/' . $order_id . '/refunds/preview' );
		$request->set_body_params(
			array(
				'line_items' => $line_items,
			)
		);
		return $this->server->dispatch( $request );
	}
}
