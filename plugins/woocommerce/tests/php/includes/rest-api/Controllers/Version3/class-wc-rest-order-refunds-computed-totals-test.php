<?php
declare( strict_types=1 );

use Automattic\WooCommerce\Enums\OrderStatus;

/**
 * Integration tests for the opt-in compute_totals mode of POST /wc/v3/orders/<order_id>/refunds,
 * including backward-compatibility lock-in tests for the default (unflagged) path.
 *
 * @group order-refunds-computed-totals
 */
class WC_REST_Order_Refunds_Computed_Totals_Test extends WC_REST_Unit_Test_Case {

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
				'user_login' => 'v3_totals_admin_' . wp_generate_password( 6, false ),
				'user_email' => 'v3_totals_admin_' . wp_generate_password( 6, false ) . '@example.com',
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
	 * @testdox A quantity-only line item computes the refund amount server-side.
	 */
	public function test_quantity_only_computes_amount(): void {
		$order   = $this->create_order_with_product( 25.00, 2 );
		$item_id = $this->get_first_line_item_id( $order );

		$response = $this->do_create_request(
			$order->get_id(),
			array(
				'compute_totals' => true,
				'line_items'     => array(
					array(
						'id'       => $item_id,
						'quantity' => 1,
					),
				),
			)
		);

		$this->assertEquals( 201, $response->get_status() );
		$this->assertEquals( '25.00', $response->get_data()['amount'] );
	}

	/**
	 * @testdox A quantity-only line item on a taxed order computes a tax-inclusive amount with a correct tax split.
	 */
	public function test_quantity_only_computes_amount_with_tax(): void {
		$tax_rate_id = $this->create_tax_rate( 10.0 );
		$order       = $this->create_order_with_product_and_tax( 100.00, 1, $tax_rate_id, 10.00 );
		$item_id     = $this->get_first_line_item_id( $order );

		$response = $this->do_create_request(
			$order->get_id(),
			array(
				'compute_totals' => true,
				'line_items'     => array(
					array(
						'id'       => $item_id,
						'quantity' => 1,
					),
				),
			)
		);

		$this->assertEquals( 201, $response->get_status() );
		$this->assertEquals( '110.00', $response->get_data()['amount'], 'Computed amount should be tax-inclusive.' );

		$refunds = wc_get_order( $order->get_id() )->get_refunds();
		$this->assertCount( 1, $refunds );
		$this->assertEquals( '10.00', wc_format_decimal( abs( (float) $refunds[0]->get_total_tax() ), 2 ), 'The stored tax portion should be split out of the inclusive amount.' );
	}

	/**
	 * @testdox A partial quantity computes a proportional amount.
	 */
	public function test_multi_quantity_partial(): void {
		$order   = $this->create_order_with_product( 10.00, 5 );
		$item_id = $this->get_first_line_item_id( $order );

		$response = $this->do_create_request(
			$order->get_id(),
			array(
				'compute_totals' => true,
				'line_items'     => array(
					array(
						'id'       => $item_id,
						'quantity' => 2,
					),
				),
			)
		);

		$this->assertEquals( 201, $response->get_status() );
		$this->assertEquals( '20.00', $response->get_data()['amount'] );
	}

	/**
	 * @testdox Computed and explicit-amount line items can be mixed in one request.
	 */
	public function test_mixed_computed_and_explicit_lines(): void {
		$order = $this->create_order_with_product_and_fee( 30.00, 10.00 );
		$items = $order->get_items( array( 'line_item', 'fee' ) );

		$line_items = array();
		foreach ( $items as $item_id => $item ) {
			if ( $item instanceof WC_Order_Item_Fee ) {
				$line_items[] = array(
					'id'           => $item_id,
					'refund_total' => 10.00,
				);
			} else {
				$line_items[] = array(
					'id'       => $item_id,
					'quantity' => 1,
				);
			}
		}

		$response = $this->do_create_request(
			$order->get_id(),
			array(
				'compute_totals' => true,
				'line_items'     => $line_items,
			)
		);

		$this->assertEquals( 201, $response->get_status() );
		$this->assertEquals( '40.00', $response->get_data()['amount'], 'Computed product line (30) plus explicit fee (10) should total 40.' );
	}

	/**
	 * @testdox In compute_totals mode a refund_total without refund_tax is treated as tax-inclusive.
	 */
	public function test_refund_total_without_refund_tax_is_tax_inclusive(): void {
		$tax_rate_id = $this->create_tax_rate( 10.0 );
		$order       = $this->create_order_with_product_and_tax( 100.00, 1, $tax_rate_id, 10.00 );
		$item_id     = $this->get_first_line_item_id( $order );

		$response = $this->do_create_request(
			$order->get_id(),
			array(
				'compute_totals' => true,
				'line_items'     => array(
					array(
						'id'           => $item_id,
						'refund_total' => 55.00,
					),
				),
			)
		);

		$this->assertEquals( 201, $response->get_status() );
		$this->assertEquals( '55.00', $response->get_data()['amount'] );

		$refunds = wc_get_order( $order->get_id() )->get_refunds();
		$this->assertEquals( '5.00', wc_format_decimal( abs( (float) $refunds[0]->get_total_tax() ), 2 ), 'Half of the stored $10 tax should be split out of the inclusive 55.00.' );
	}

	/**
	 * @testdox In compute_totals mode an explicit refund_tax keeps tax-exclusive refund_total semantics.
	 */
	public function test_explicit_refund_tax_keeps_net_semantics(): void {
		$tax_rate_id = $this->create_tax_rate( 10.0 );
		$order       = $this->create_order_with_product_and_tax( 100.00, 1, $tax_rate_id, 10.00 );
		$item_id     = $this->get_first_line_item_id( $order );

		$response = $this->do_create_request(
			$order->get_id(),
			array(
				'compute_totals' => true,
				'line_items'     => array(
					array(
						'id'           => $item_id,
						'refund_total' => 50.00,
						'refund_tax'   => array(
							array(
								'id'           => $tax_rate_id,
								'refund_total' => 5.00,
							),
						),
					),
				),
			)
		);

		$this->assertEquals( 201, $response->get_status() );
		$this->assertEquals( '55.00', $response->get_data()['amount'], 'Net 50.00 plus explicit tax 5.00 should total 55.00.' );
	}

	/**
	 * @testdox Duplicate tax IDs within a line return 400 duplicate_tax_id and create no refund.
	 */
	public function test_duplicate_refund_tax_ids_return_400(): void {
		$tax_rate_id = $this->create_tax_rate( 10.0 );
		$order       = $this->create_order_with_product_and_tax( 100.00, 1, $tax_rate_id, 10.00 );
		$item_id     = $this->get_first_line_item_id( $order );

		$response = $this->do_create_request(
			$order->get_id(),
			array(
				'compute_totals' => true,
				'line_items'     => array(
					array(
						'id'           => $item_id,
						'refund_total' => 50.00,
						'refund_tax'   => array(
							array(
								'id'           => $tax_rate_id,
								'refund_total' => 5.00,
							),
							array(
								'id'           => $tax_rate_id,
								'refund_total' => 5.00,
							),
						),
					),
				),
			)
		);

		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'woocommerce_rest_duplicate_tax_id', $response->get_data()['code'] );
		$this->assertCount( 0, wc_get_order( $order->get_id() )->get_refunds(), 'A refund with inconsistent tax accounting must never be created.' );
	}

	/**
	 * @testdox refund_tax combined with an auto-computed refund_total returns 400, not 500.
	 */
	public function test_refund_tax_with_auto_computed_total_returns_400(): void {
		$tax_rate_id = $this->create_tax_rate( 10.0 );
		$order       = $this->create_order_with_product_and_tax( 100.00, 1, $tax_rate_id, 10.00 );
		$item_id     = $this->get_first_line_item_id( $order );

		$response = $this->do_create_request(
			$order->get_id(),
			array(
				'compute_totals' => true,
				'line_items'     => array(
					array(
						'id'         => $item_id,
						'quantity'   => 1,
						'refund_tax' => array(
							array(
								'id'           => $tax_rate_id,
								'refund_total' => 5.00,
							),
						),
					),
				),
			)
		);

		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'woocommerce_rest_invalid_line_item', $response->get_data()['code'] );
		$this->assertCount( 0, wc_get_order( $order->get_id() )->get_refunds() );
	}

	/**
	 * @testdox A refund_tax entry referencing a tax id not on the line returns 400, not 500.
	 */
	public function test_refund_tax_unknown_tax_id_returns_400(): void {
		$tax_rate_id = $this->create_tax_rate( 10.0 );
		$order       = $this->create_order_with_product_and_tax( 100.00, 1, $tax_rate_id, 10.00 );
		$item_id     = $this->get_first_line_item_id( $order );

		$response = $this->do_create_request(
			$order->get_id(),
			array(
				'compute_totals' => true,
				'line_items'     => array(
					array(
						'id'           => $item_id,
						'refund_total' => 50.00,
						'refund_tax'   => array(
							array(
								'id'           => $tax_rate_id + 999,
								'refund_total' => 5.00,
							),
						),
					),
				),
			)
		);

		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'woocommerce_rest_invalid_line_item', $response->get_data()['code'] );
		$this->assertCount( 0, wc_get_order( $order->get_id() )->get_refunds() );
	}

	/**
	 * @testdox A refund_tax entry missing id or refund_total returns 400, not 500.
	 */
	public function test_refund_tax_missing_fields_returns_400(): void {
		$tax_rate_id = $this->create_tax_rate( 10.0 );
		$order       = $this->create_order_with_product_and_tax( 100.00, 1, $tax_rate_id, 10.00 );
		$item_id     = $this->get_first_line_item_id( $order );

		$response = $this->do_create_request(
			$order->get_id(),
			array(
				'compute_totals' => true,
				'line_items'     => array(
					array(
						'id'           => $item_id,
						'refund_total' => 50.00,
						'refund_tax'   => array(
							array( 'id' => $tax_rate_id ),
						),
					),
				),
			)
		);

		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'woocommerce_rest_invalid_line_item', $response->get_data()['code'] );
		$this->assertCount( 0, wc_get_order( $order->get_id() )->get_refunds() );
	}

	/**
	 * @testdox A refund_tax total exceeding the remaining refundable tax returns 400, not 500.
	 */
	public function test_refund_tax_exceeding_remaining_tax_returns_400(): void {
		$tax_rate_id = $this->create_tax_rate( 10.0 );
		$order       = $this->create_order_with_product_and_tax( 100.00, 1, $tax_rate_id, 10.00 );
		$item_id     = $this->get_first_line_item_id( $order );

		$response = $this->do_create_request(
			$order->get_id(),
			array(
				'compute_totals' => true,
				'line_items'     => array(
					array(
						'id'           => $item_id,
						'refund_total' => 50.00,
						'refund_tax'   => array(
							array(
								'id'           => $tax_rate_id,
								'refund_total' => 50.00,
							),
						),
					),
				),
			)
		);

		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'woocommerce_rest_invalid_refund_amount', $response->get_data()['code'] );
		$this->assertCount( 0, wc_get_order( $order->get_id() )->get_refunds() );
	}

	/**
	 * @testdox Auto-computing a refund against a line whose source quantity is zero returns 400, not 500.
	 */
	public function test_zero_source_quantity_auto_compute_returns_400(): void {
		// create_order_with_product() cannot build a zero-quantity line, so the order
		// is assembled here. The order total is non-zero so the order is not treated
		// as already fully refunded, which would fail earlier with a different error.
		$order = wc_create_order();
		$item  = new WC_Order_Item_Product();
		$item->set_props(
			array(
				'quantity' => 0,
				'subtotal' => 0,
				'total'    => 0,
			)
		);
		$item->save();
		$order->add_item( $item );
		$order->set_total( 10.00 );
		$order->set_status( OrderStatus::COMPLETED );
		$order->save();

		$response = $this->do_create_request(
			$order->get_id(),
			array(
				'compute_totals' => true,
				'line_items'     => array(
					array(
						'id'       => $item->get_id(),
						'quantity' => 1,
					),
				),
			)
		);

		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'woocommerce_rest_invalid_line_item', $response->get_data()['code'] );
		$this->assertStringContainsString( 'source quantity is zero', $response->get_data()['message'] );
		$this->assertCount( 0, wc_get_order( $order->get_id() )->get_refunds() );
	}

	/**
	 * @testdox A quantity-form refund after a partial amount refund is clamped to the line's remaining refundable amount.
	 */
	public function test_quantity_refund_clamped_to_remaining(): void {
		$order   = $this->create_order_with_product( 50.00, 2 );
		$item_id = $this->get_first_line_item_id( $order );

		wc_create_refund(
			array(
				'order_id'   => $order->get_id(),
				'amount'     => 30.00,
				'line_items' => array(
					$item_id => array(
						'qty'          => 0,
						'refund_total' => 30.00,
					),
				),
			)
		);

		$response = $this->do_create_request(
			$order->get_id(),
			array(
				'compute_totals' => true,
				'line_items'     => array(
					array(
						'id'       => $item_id,
						'quantity' => 2,
					),
				),
			)
		);

		$this->assertEquals( 201, $response->get_status() );
		$this->assertEquals( '70.00', $response->get_data()['amount'], 'The 100.00 quantity amount should be clamped to the 70.00 remaining on the line.' );
	}

	/**
	 * @testdox An amount-only request works in compute_totals mode.
	 */
	public function test_amount_only_refund(): void {
		$order = $this->create_order_with_product( 50.00, 1 );

		$response = $this->do_create_request(
			$order->get_id(),
			array(
				'compute_totals' => true,
				'amount'         => '10.00',
			)
		);

		$this->assertEquals( 201, $response->get_status() );
		$this->assertEquals( '10.00', $response->get_data()['amount'] );
	}

	/**
	 * @testdox A quantity exceeding the refundable units returns 422 quantity_exceeds_refundable.
	 */
	public function test_over_quantity_returns_422(): void {
		$order   = $this->create_order_with_product( 10.00, 2 );
		$item_id = $this->get_first_line_item_id( $order );

		$response = $this->do_create_request(
			$order->get_id(),
			array(
				'compute_totals' => true,
				'line_items'     => array(
					array(
						'id'       => $item_id,
						'quantity' => 3,
					),
				),
			)
		);

		$this->assertEquals( 422, $response->get_status() );
		$this->assertEquals( 'woocommerce_rest_quantity_exceeds_refundable', $response->get_data()['code'] );
	}

	/**
	 * @testdox A refund_total above the line total returns 422 refund_total_exceeds_line.
	 */
	public function test_refund_total_exceeds_line_returns_422(): void {
		$order   = $this->create_order_with_product( 50.00, 1 );
		$item_id = $this->get_first_line_item_id( $order );

		$response = $this->do_create_request(
			$order->get_id(),
			array(
				'compute_totals' => true,
				'line_items'     => array(
					array(
						'id'           => $item_id,
						'refund_total' => 60.00,
					),
				),
			)
		);

		$this->assertEquals( 422, $response->get_status() );
		$this->assertEquals( 'woocommerce_rest_refund_total_exceeds_line', $response->get_data()['code'] );
	}

	/**
	 * @testdox A non-refundable order status returns 422 order_not_refundable.
	 */
	public function test_non_refundable_status_returns_422(): void {
		$order   = $this->create_order_with_product( 10.00, 1 );
		$item_id = $this->get_first_line_item_id( $order );
		$order->set_status( OrderStatus::PENDING );
		$order->save();

		$response = $this->do_create_request(
			$order->get_id(),
			array(
				'compute_totals' => true,
				'line_items'     => array(
					array(
						'id'       => $item_id,
						'quantity' => 1,
					),
				),
			)
		);

		$this->assertEquals( 422, $response->get_status() );
		$this->assertEquals( 'woocommerce_rest_order_not_refundable', $response->get_data()['code'] );
	}

	/**
	 * @testdox An amount below the computed line total returns 400 invalid_refund_amount.
	 */
	public function test_amount_below_computed_total_returns_400(): void {
		$order   = $this->create_order_with_product( 50.00, 1 );
		$item_id = $this->get_first_line_item_id( $order );

		$response = $this->do_create_request(
			$order->get_id(),
			array(
				'compute_totals' => true,
				'amount'         => '30.00',
				'line_items'     => array(
					array(
						'id'       => $item_id,
						'quantity' => 1,
					),
				),
			)
		);

		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'woocommerce_rest_invalid_refund_amount', $response->get_data()['code'] );
	}

	/**
	 * @testdox An amount above the order's remaining refundable amount returns 422 refund_exceeds_remaining.
	 */
	public function test_amount_above_remaining_returns_422(): void {
		$order = $this->create_order_with_product( 50.00, 1 );

		$response = $this->do_create_request(
			$order->get_id(),
			array(
				'compute_totals' => true,
				'amount'         => '80.00',
			)
		);

		$this->assertEquals( 422, $response->get_status() );
		$this->assertEquals( 'woocommerce_rest_refund_exceeds_remaining', $response->get_data()['code'] );
	}

	/**
	 * @testdox An explicit zero amount returns 400 invalid_refund_amount instead of falling back to the computed total.
	 */
	public function test_explicit_zero_amount_returns_400(): void {
		$order   = $this->create_order_with_product( 50.00, 1 );
		$item_id = $this->get_first_line_item_id( $order );

		$response = $this->do_create_request(
			$order->get_id(),
			array(
				'compute_totals' => true,
				'amount'         => '0.00',
				'line_items'     => array(
					array(
						'id'       => $item_id,
						'quantity' => 1,
					),
				),
			)
		);

		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'woocommerce_rest_invalid_refund_amount', $response->get_data()['code'] );
		$this->assertCount( 0, wc_get_order( $order->get_id() )->get_refunds(), 'No refund may be created for an explicit zero amount.' );
	}

	/**
	 * @testdox A compute_totals refund matches the totals of the preview endpoint for the same input.
	 */
	public function test_matches_preview(): void {
		$tax_rate_id = $this->create_tax_rate( 10.0 );
		$order       = $this->create_order_with_product_and_tax( 100.00, 1, $tax_rate_id, 10.00 );
		$item_id     = $this->get_first_line_item_id( $order );

		$preview_request = new WP_REST_Request( 'POST', '/wc/v3/orders/' . $order->get_id() . '/refunds/preview' );
		$preview_request->set_body_params(
			array(
				'line_items' => array(
					array(
						'line_item_id' => $item_id,
						'quantity'     => 1,
					),
				),
			)
		);
		$preview_response = $this->server->dispatch( $preview_request );
		$this->assertEquals( 200, $preview_response->get_status() );

		$create_response = $this->do_create_request(
			$order->get_id(),
			array(
				'compute_totals' => true,
				'line_items'     => array(
					array(
						'id'       => $item_id,
						'quantity' => 1,
					),
				),
			)
		);
		$this->assertEquals( 201, $create_response->get_status() );

		$this->assertEquals(
			$preview_response->get_data()['total'],
			$create_response->get_data()['amount'],
			'Preview total and computed create amount must match for identical input.'
		);
	}

	/**
	 * @testdox Computed amounts round to a whole unit in a zero-decimal currency.
	 */
	public function test_zero_decimal_currency(): void {
		add_filter( 'wc_get_price_decimals', '__return_zero' );

		try {
			$order   = $this->create_order_with_product( 100.00, 1 );
			$item_id = $this->get_first_line_item_id( $order );

			$response = $this->do_create_request(
				$order->get_id(),
				array(
					'compute_totals' => true,
					'line_items'     => array(
						array(
							'id'       => $item_id,
							'quantity' => 1,
						),
					),
				)
			);

			$this->assertEquals( 201, $response->get_status() );
			$this->assertEquals( '100', $response->get_data()['amount'] );
		} finally {
			remove_filter( 'wc_get_price_decimals', '__return_zero' );
		}
	}

	/**
	 * @testdox BC: without compute_totals a quantity-only request still creates a 0.00 refund.
	 */
	public function test_unflagged_quantity_only_still_creates_zero_refund(): void {
		$order   = $this->create_order_with_product( 25.00, 2 );
		$item_id = $this->get_first_line_item_id( $order );

		// In production this request path emits a PHP warning (undefined refund_total
		// array key in wc_create_refund; an E_NOTICE on PHP 7.4) and continues.
		// PHPUnit converts it into an exception, which would make wc_create_refund
		// fail with a 500 that does not happen outside the test runner — mask both
		// error levels to test the real behavior.
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.prevent_path_disclosure_error_reporting, WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_error_reporting -- scoped mask so PHPUnit does not convert a production-only PHP warning into an exception.
		$error_reporting = error_reporting( E_ALL & ~E_WARNING & ~E_NOTICE );
		try {
			$response = $this->do_create_request(
				$order->get_id(),
				array(
					'line_items' => array(
						array(
							'id'       => $item_id,
							'quantity' => 1,
						),
					),
				)
			);
		} finally {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.prevent_path_disclosure_error_reporting, WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_error_reporting -- restore the previous error_reporting level.
			error_reporting( $error_reporting );
		}

		$this->assertEquals( 201, $response->get_status() );
		$this->assertEquals( '', $response->get_data()['amount'], 'The pre-existing ghost zero-amount refund behavior (amount serialized as an empty string) must be preserved when the flag is absent.' );

		$refunds = wc_get_order( $order->get_id() )->get_refunds();
		$this->assertCount( 1, $refunds );
		$this->assertEquals( 0.0, (float) $refunds[0]->get_amount() );
	}

	/**
	 * @testdox BC: compute_totals explicitly false behaves the same as an absent flag.
	 */
	public function test_compute_totals_false_same_as_absent(): void {
		$order   = $this->create_order_with_product( 25.00, 2 );
		$item_id = $this->get_first_line_item_id( $order );

		// See test_unflagged_quantity_only_still_creates_zero_refund for why the
		// PHP warning this legacy path emits must be masked under PHPUnit.
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.prevent_path_disclosure_error_reporting, WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_error_reporting -- scoped mask so PHPUnit does not convert a production-only PHP warning into an exception.
		$error_reporting = error_reporting( E_ALL & ~E_WARNING & ~E_NOTICE );
		try {
			$response = $this->do_create_request(
				$order->get_id(),
				array(
					'compute_totals' => false,
					'line_items'     => array(
						array(
							'id'       => $item_id,
							'quantity' => 1,
						),
					),
				)
			);
		} finally {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.prevent_path_disclosure_error_reporting, WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_error_reporting -- restore the previous error_reporting level.
			error_reporting( $error_reporting );
		}

		$this->assertEquals( 201, $response->get_status() );
		$this->assertEquals( '', $response->get_data()['amount'] );
	}

	/**
	 * @testdox BC: without compute_totals a refund_total without refund_tax keeps tax-exclusive semantics.
	 */
	public function test_unflagged_refund_total_stays_tax_exclusive(): void {
		$tax_rate_id = $this->create_tax_rate( 10.0 );
		$order       = $this->create_order_with_product_and_tax( 100.00, 1, $tax_rate_id, 10.00 );
		$item_id     = $this->get_first_line_item_id( $order );

		$response = $this->do_create_request(
			$order->get_id(),
			array(
				'line_items' => array(
					array(
						'id'           => $item_id,
						'refund_total' => 55.00,
					),
				),
			)
		);

		$this->assertEquals( 201, $response->get_status() );
		$this->assertEquals( '55.00', $response->get_data()['amount'] );

		$refunds = wc_get_order( $order->get_id() )->get_refunds();
		$this->assertEquals( 0.0, (float) $refunds[0]->get_total_tax(), 'Without the flag no tax may be split out of refund_total.' );
	}

	/**
	 * @testdox BC: without compute_totals an explicit refund_total plus refund_tax sums to the amount as before.
	 */
	public function test_unflagged_explicit_net_plus_tax_sums_amount(): void {
		$tax_rate_id = $this->create_tax_rate( 10.0 );
		$order       = $this->create_order_with_product_and_tax( 100.00, 1, $tax_rate_id, 10.00 );
		$item_id     = $this->get_first_line_item_id( $order );

		$response = $this->do_create_request(
			$order->get_id(),
			array(
				'line_items' => array(
					array(
						'id'           => $item_id,
						'quantity'     => 1,
						'refund_total' => 100.00,
						'refund_tax'   => array(
							array(
								'id'           => $tax_rate_id,
								'refund_total' => 10.00,
							),
						),
					),
				),
			)
		);

		$this->assertEquals( 201, $response->get_status() );
		$this->assertEquals( '110.00', $response->get_data()['amount'] );
	}

	/**
	 * @testdox The compute_totals parameter is declared in the create schema with a false default.
	 */
	public function test_compute_totals_declared_in_schema(): void {
		$order = $this->create_order_with_product( 10.00, 1 );

		$request  = new WP_REST_Request( 'OPTIONS', '/wc/v3/orders/' . $order->get_id() . '/refunds' );
		$response = $this->server->dispatch( $request );
		$schema   = $response->get_data()['schema'];

		$this->assertArrayHasKey( 'compute_totals', $schema['properties'] );
		$this->assertFalse( $schema['properties']['compute_totals']['default'] );
	}

	/**
	 * @testdox An array refund_total returns 400 invalid_refund_total instead of a TypeError.
	 */
	public function test_array_refund_total_returns_400(): void {
		$order   = $this->create_order_with_product( 25.00, 1 );
		$item_id = $this->get_first_line_item_id( $order );

		$response = $this->do_create_request(
			$order->get_id(),
			array(
				'compute_totals' => true,
				'line_items'     => array(
					array(
						'id'           => $item_id,
						'refund_total' => array( 10.00 ),
					),
				),
			)
		);

		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'woocommerce_rest_invalid_refund_total', $response->get_data()['code'] );
		$this->assertCount( 0, wc_get_order( $order->get_id() )->get_refunds() );
	}

	/**
	 * @testdox A non-numeric quantity returns 400 invalid_quantity.
	 */
	public function test_non_numeric_quantity_returns_400(): void {
		$order   = $this->create_order_with_product( 25.00, 1 );
		$item_id = $this->get_first_line_item_id( $order );

		$response = $this->do_create_request(
			$order->get_id(),
			array(
				'compute_totals' => true,
				'line_items'     => array(
					array(
						'id'       => $item_id,
						'quantity' => 'two',
					),
				),
			)
		);

		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'woocommerce_rest_invalid_quantity', $response->get_data()['code'] );
	}

	/**
	 * @testdox A fractional quantity returns 400 invalid_quantity.
	 */
	public function test_fractional_quantity_returns_400(): void {
		$order   = $this->create_order_with_product( 25.00, 2 );
		$item_id = $this->get_first_line_item_id( $order );

		$response = $this->do_create_request(
			$order->get_id(),
			array(
				'compute_totals' => true,
				'line_items'     => array(
					array(
						'id'       => $item_id,
						'quantity' => 1.5,
					),
				),
			)
		);

		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'woocommerce_rest_invalid_quantity', $response->get_data()['code'] );
	}

	/**
	 * @testdox Numeric-string quantity and refund_total values are accepted and cast.
	 */
	public function test_numeric_string_values_are_accepted(): void {
		$order   = $this->create_order_with_product( 25.00, 2 );
		$item_id = $this->get_first_line_item_id( $order );

		$response = $this->do_create_request(
			$order->get_id(),
			array(
				'compute_totals' => true,
				'line_items'     => array(
					array(
						'id'       => (string) $item_id,
						'quantity' => '2',
					),
				),
			)
		);

		$this->assertEquals( 201, $response->get_status() );
		$this->assertEquals( '50.00', $response->get_data()['amount'] );
	}

	/**
	 * @testdox A non-array refund_tax returns 400 invalid_line_item.
	 */
	public function test_non_array_refund_tax_returns_400(): void {
		$order   = $this->create_order_with_product( 25.00, 1 );
		$item_id = $this->get_first_line_item_id( $order );

		$response = $this->do_create_request(
			$order->get_id(),
			array(
				'compute_totals' => true,
				'line_items'     => array(
					array(
						'id'           => $item_id,
						'refund_total' => 10.00,
						'refund_tax'   => 'nope',
					),
				),
			)
		);

		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'woocommerce_rest_invalid_line_item', $response->get_data()['code'] );
	}

	/**
	 * @testdox A refund_tax entry with a non-numeric refund_total returns 400 invalid_line_item.
	 */
	public function test_malformed_refund_tax_entry_returns_400(): void {
		$order   = $this->create_order_with_product( 25.00, 1 );
		$item_id = $this->get_first_line_item_id( $order );

		$response = $this->do_create_request(
			$order->get_id(),
			array(
				'compute_totals' => true,
				'line_items'     => array(
					array(
						'id'           => $item_id,
						'refund_total' => 10.00,
						'refund_tax'   => array(
							array(
								'id'           => 1,
								'refund_total' => array( 5.00 ),
							),
						),
					),
				),
			)
		);

		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'woocommerce_rest_invalid_line_item', $response->get_data()['code'] );
	}

	/**
	 * @testdox An array line item id returns 400 invalid_line_item.
	 */
	public function test_array_line_item_id_returns_400(): void {
		$order = $this->create_order_with_product( 25.00, 1 );

		$response = $this->do_create_request(
			$order->get_id(),
			array(
				'compute_totals' => true,
				'line_items'     => array(
					array(
						'id'       => array( 1 ),
						'quantity' => 1,
					),
				),
			)
		);

		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'woocommerce_rest_invalid_line_item', $response->get_data()['code'] );
	}

	/**
	 * @testdox A fractional line item id returns 400 invalid_line_item instead of refunding a truncated id.
	 */
	public function test_fractional_line_item_id_returns_400(): void {
		$order   = $this->create_order_with_product( 25.00, 1 );
		$item_id = $this->get_first_line_item_id( $order );

		$response = $this->do_create_request(
			$order->get_id(),
			array(
				'compute_totals' => true,
				'line_items'     => array(
					array(
						'id'       => $item_id + 0.5,
						'quantity' => 1,
					),
				),
			)
		);

		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'woocommerce_rest_invalid_line_item', $response->get_data()['code'] );
		$this->assertCount( 0, wc_get_order( $order->get_id() )->get_refunds(), 'A fractional id must never be truncated into a refund of a different line.' );
	}

	/**
	 * @testdox A fractional refund_tax id returns 400 invalid_line_item instead of refunding a truncated tax bucket.
	 */
	public function test_fractional_refund_tax_id_returns_400(): void {
		$tax_rate_id = $this->create_tax_rate( 10.0 );
		$order       = $this->create_order_with_product_and_tax( 100.00, 1, $tax_rate_id, 10.00 );
		$item_id     = $this->get_first_line_item_id( $order );

		$response = $this->do_create_request(
			$order->get_id(),
			array(
				'compute_totals' => true,
				'line_items'     => array(
					array(
						'id'           => $item_id,
						'refund_total' => 50.00,
						'refund_tax'   => array(
							array(
								'id'           => $tax_rate_id + 0.5,
								'refund_total' => 5.00,
							),
						),
					),
				),
			)
		);

		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'woocommerce_rest_invalid_line_item', $response->get_data()['code'] );
		$this->assertCount( 0, wc_get_order( $order->get_id() )->get_refunds() );
	}

	/**
	 * @testdox A scalar line_items entry returns 400 invalid_line_item.
	 */
	public function test_scalar_line_items_entry_returns_400(): void {
		$order = $this->create_order_with_product( 25.00, 1 );

		$response = $this->do_create_request(
			$order->get_id(),
			array(
				'compute_totals' => true,
				'line_items'     => array( 'not-an-object' ),
			)
		);

		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'woocommerce_rest_invalid_line_item', $response->get_data()['code'] );
	}

	/**
	 * @testdox Repeating the same line item id in one compute_totals request is rejected.
	 */
	public function test_duplicate_line_item_ids_rejected(): void {
		$order   = $this->create_order_with_product( 10.00, 4 );
		$item_id = $this->get_first_line_item_id( $order );

		$response = $this->do_create_request(
			$order->get_id(),
			array(
				'compute_totals' => true,
				'line_items'     => array(
					array(
						'id'       => $item_id,
						'quantity' => 1,
					),
					array(
						'id'       => $item_id,
						'quantity' => 1,
					),
				),
			)
		);

		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'woocommerce_rest_duplicate_line_item', $response->get_data()['code'] );
	}

	/**
	 * @testdox A compute_totals request against a nonexistent order id, or a refund id used as the order id, returns a 404.
	 */
	public function test_invalid_order_ids_return_404(): void {
		$body = array(
			'compute_totals' => true,
			'line_items'     => array(),
		);

		$response = $this->do_create_request( 999999999, $body );
		$this->assertEquals( 404, $response->get_status() );
		$this->assertEquals( 'woocommerce_rest_invalid_order_id', $response->get_data()['code'] );

		// A refund id resolves through wc_get_order to a WC_Order_Refund, which
		// must be rejected the same way as a missing order.
		$order  = $this->create_order_with_product( 10.00, 1 );
		$refund = wc_create_refund(
			array(
				'order_id' => $order->get_id(),
				'amount'   => 5.00,
			)
		);
		$this->assertNotWPError( $refund );

		$response = $this->do_create_request( $refund->get_id(), $body );
		$this->assertEquals( 404, $response->get_status() );
		$this->assertEquals( 'woocommerce_rest_invalid_order_id', $response->get_data()['code'] );
	}

	/**
	 * @testdox The pre_insert filter receives internal-format line items and the resolved amount, matching the legacy path's request shape.
	 */
	public function test_pre_insert_filter_receives_internal_format(): void {
		$order   = $this->create_order_with_product( 25.00, 2 );
		$item_id = $this->get_first_line_item_id( $order );

		$captured = null;
		$capture  = function ( $refund, $request, $creating ) use ( &$captured ) {
			$captured = array(
				'line_items' => $request['line_items'],
				'amount'     => $request['amount'],
				'creating'   => $creating,
			);
			return $refund;
		};
		add_filter( 'woocommerce_rest_pre_insert_shop_order_refund_object', $capture, 10, 3 );

		try {
			$response = $this->do_create_request(
				$order->get_id(),
				array(
					'compute_totals' => true,
					'line_items'     => array(
						array(
							'id'       => $item_id,
							'quantity' => 1,
						),
					),
				)
			);
		} finally {
			remove_filter( 'woocommerce_rest_pre_insert_shop_order_refund_object', $capture );
		}

		$this->assertEquals( 201, $response->get_status() );
		$this->assertNotNull( $captured, 'The pre_insert filter should run for compute_totals requests.' );
		$this->assertTrue( $captured['creating'] );
		$this->assertSame( '25', $captured['amount'], 'The resolved amount should be mirrored onto the request.' );
		$this->assertSame( array( $item_id ), array_keys( $captured['line_items'] ), 'line_items should be keyed by item id (internal format).' );
		$this->assertSame( 1, $captured['line_items'][ $item_id ]['qty'] );
		$this->assertEquals( 25.00, $captured['line_items'][ $item_id ]['refund_total'] );
	}

	/**
	 * @testdox A wc_create_refund failure returns the same error code and status with and without compute_totals.
	 */
	public function test_create_failure_code_and_status_match_legacy(): void {
		$order   = $this->create_order_with_product( 10.00, 1 );
		$item_id = $this->get_first_line_item_id( $order );

		$fail = function () {
			throw new Exception( 'Simulated create failure.' );
		};
		add_action( 'woocommerce_create_refund', $fail );

		try {
			$computed = $this->do_create_request(
				$order->get_id(),
				array(
					'compute_totals' => true,
					'line_items'     => array(
						array(
							'id'       => $item_id,
							'quantity' => 1,
						),
					),
				)
			);

			$legacy = $this->do_create_request( $order->get_id(), array( 'amount' => '5.00' ) );
		} finally {
			remove_action( 'woocommerce_create_refund', $fail );
		}

		$this->assertEquals( 500, $computed->get_status() );
		$this->assertEquals( 'woocommerce_rest_cannot_create_order_refund', $computed->get_data()['code'] );
		$this->assertEquals( $legacy->get_status(), $computed->get_status(), 'Both paths should fail with the same HTTP status.' );
		$this->assertEquals( $legacy->get_data()['code'], $computed->get_data()['code'], 'Both paths should fail with the same error code.' );
	}

	/**
	 * @testdox A line item carrying both id and line_item_id is rejected as ambiguous.
	 */
	public function test_conflicting_line_identifiers_rejected(): void {
		$order   = $this->create_order_with_product( 10.00, 2 );
		$item_id = $this->get_first_line_item_id( $order );

		$response = $this->do_create_request(
			$order->get_id(),
			array(
				'compute_totals' => true,
				'line_items'     => array(
					array(
						'id'           => $item_id,
						'line_item_id' => $item_id + 1,
						'quantity'     => 1,
					),
				),
			)
		);

		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'woocommerce_rest_invalid_line_item', $response->get_data()['code'] );
		$this->assertCount( 0, wc_get_order( $order->get_id() )->get_refunds(), 'No refund should be created from an ambiguous line identifier.' );
	}

	/**
	 * @testdox A line item keyed by line_item_id alone is accepted, matching the preview payload shape.
	 */
	public function test_line_item_id_key_accepted(): void {
		$order   = $this->create_order_with_product( 25.00, 2 );
		$item_id = $this->get_first_line_item_id( $order );

		$response = $this->do_create_request(
			$order->get_id(),
			array(
				'compute_totals' => true,
				'line_items'     => array(
					array(
						'line_item_id' => $item_id,
						'quantity'     => 1,
					),
				),
			)
		);

		$this->assertEquals( 201, $response->get_status() );
		$this->assertEquals( '25.00', $response->get_data()['amount'] );
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
	 * Create a completed order with one product line item and one fee line.
	 *
	 * @param float $product_price Product price.
	 * @param float $fee_total     Fee total.
	 * @return WC_Order
	 */
	private function create_order_with_product_and_fee( float $product_price, float $fee_total ): WC_Order {
		$order = $this->create_order_with_product( $product_price, 1 );

		$fee = new WC_Order_Item_Fee();
		$fee->set_props(
			array(
				'name'  => 'Service fee',
				'total' => $fee_total,
			)
		);
		$fee->save();
		$order->add_item( $fee );
		$order->set_total( $product_price + $fee_total );
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
	 * Send a refund creation request and return the response.
	 *
	 * api_refund is forced to false unless the test supplies it: the test orders
	 * have no payment gateway, so the v3 default of true would make wc_refund_payment
	 * fail and delete the refund.
	 *
	 * @param int   $order_id Order ID.
	 * @param array $body     Request body parameters.
	 * @return WP_REST_Response
	 */
	private function do_create_request( int $order_id, array $body ): WP_REST_Response {
		$request = new WP_REST_Request( 'POST', '/wc/v3/orders/' . $order_id . '/refunds' );
		$request->set_body_params( array_merge( array( 'api_refund' => false ), $body ) );
		return $this->server->dispatch( $request );
	}
}
