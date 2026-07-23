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
	 * Shared admin user ID. Created once per class to avoid the wp_insert_user cost
	 * on every test (this suite has 25+ cases).
	 *
	 * @var int
	 */
	protected static $user_id;

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
	 * Create the shared admin user once per class.
	 */
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		self::$user_id = wp_insert_user(
			array(
				'user_login' => 'preview_admin_' . wp_generate_password( 6, false ),
				'user_email' => 'preview_admin_' . wp_generate_password( 6, false ) . '@example.com',
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
		$this->enable_rest_api_v4_feature();
		parent::setUp();

		wp_set_current_user( self::$user_id );
	}

	/**
	 * Runs after each test.
	 */
	public function tearDown(): void {
		$this->created_orders = array();

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

		$this->assertEquals( '100.00', $data['subtotal'] );
		$this->assertEquals( '0.00', $data['tax'] );
		$this->assertEquals( '100.00', $data['total'] );
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

		$this->assertEquals( '100.00', $data['subtotal'] );
		$this->assertEquals( '10.00', $data['tax'] );
		$this->assertEquals( '110.00', $data['total'] );
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

		$order  = wc_create_order();
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

		$this->assertEquals( 422, $response->get_status() );
		$data = $response->get_data();
		$this->assertEquals( 'quantity_exceeds_refundable', $data['code'] );
	}

	/**
	 * @testdox P8: Preview with invalid line item ID returns line_item_not_found.
	 */
	public function test_preview_invalid_line_item(): void {
		$order            = $this->create_order_with_product( 50.00, 1 );
		$existing_item_id = $this->get_first_line_item_id( $order );
		$nonexistent_id   = $existing_item_id + 999;

		$response = $this->do_preview_request(
			$order->get_id(),
			array(
				array(
					'line_item_id' => $nonexistent_id,
					'quantity'     => 1,
				),
			)
		);

		// A bad line_item_id reference is a 400 (malformed request), matching the create endpoint.
		$this->assertEquals( 400, $response->get_status() );
		$data = $response->get_data();
		$this->assertEquals( 'line_item_not_found', $data['code'] );
	}

	/**
	 * @testdox Preview returns 422 preview_exceeds_max_refundable when the computed total exceeds the order's remaining refundable amount.
	 *
	 * An amount-only partial refund (no line items attached) drops
	 * `get_remaining_refund_amount()` but leaves per-line quantities intact,
	 * so the per-line validation can still let a preview through that would
	 * over-refund in aggregate. The endpoint's grand-total guard catches it.
	 *
	 * Setup: 2 × $100 order ($200 refundable) → $50 amount-only refund applied
	 * → remaining = $150. Previewing qty 2 would compute total $200, exceeding
	 * the $150 remaining → 422 `preview_exceeds_max_refundable`.
	 */
	public function test_preview_returns_422_when_total_exceeds_max_refundable(): void {
		$order   = $this->create_order_with_product( 100.00, 2 );
		$item_id = $this->get_first_line_item_id( $order );

		// Amount-only partial refund — drops remaining refundable to $150
		// without consuming any specific units of the line item.
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
					'quantity'     => 2,
				),
			)
		);

		$this->assertEquals( 422, $response->get_status() );
		$data = $response->get_data();
		$this->assertEquals( 'preview_exceeds_max_refundable', $data['code'] );
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

		$this->assertEquals( 422, $response->get_status() );
		$data = $response->get_data();
		$this->assertEquals( 'order_not_refundable', $data['code'] );
	}

	/**
	 * @testdox P11: Preview with empty line_items array is rejected by schema validation.
	 *
	 * REST schema validation (minItems: 1) rejects the request before it reaches
	 * the controller, so the framework's generic 'rest_invalid_param' code wins
	 * over DataUtils's curated 'missing_line_items'. The HTTP contract still
	 * delivers a 400 with an actionable message.
	 */
	public function test_preview_empty_line_items(): void {
		$order = $this->create_order_with_product( 50.00, 1 );

		$response = $this->do_preview_request( $order->get_id(), array() );

		$this->assertEquals( 400, $response->get_status() );
		$data = $response->get_data();
		$this->assertEquals( 'rest_invalid_param', $data['code'] );
	}

	/**
	 * @testdox Preview rejects invalid quantity values (zero, negative, missing, non-integer).
	 *
	 * @dataProvider invalid_quantity_provider
	 *
	 * @param array $line_item_overrides Overrides merged into the line item entry (after line_item_id).
	 * @param array $expected_codes      Acceptable response error codes (REST framework or DataUtils).
	 */
	public function test_preview_invalid_quantity( array $line_item_overrides, array $expected_codes ): void {
		$order   = $this->create_order_with_product( 50.00, 1 );
		$item_id = $this->get_first_line_item_id( $order );

		$line_item = array_merge( array( 'line_item_id' => $item_id ), $line_item_overrides );
		$response  = $this->do_preview_request( $order->get_id(), array( $line_item ) );

		$this->assertEquals( 400, $response->get_status() );
		$data = $response->get_data();
		$this->assertContains( $data['code'], $expected_codes, 'Got code ' . $data['code'] );
	}

	/**
	 * Quantity scenarios that should all be rejected at the HTTP boundary.
	 *
	 * Some inputs are rejected by the REST framework (`rest_invalid_param`) and
	 * others by DataUtils::validate_preview_line_items (`invalid_quantity` or
	 * `missing_quantity_or_refund_total`). The test accepts any from the set so it
	 * documents the actual observable behaviour without coupling to which layer rejects.
	 *
	 * @return array<string, array<int, mixed>>
	 */
	public function invalid_quantity_provider(): array {
		return array(
			'zero'        => array( array( 'quantity' => 0 ), array( 'rest_invalid_param', 'invalid_quantity' ) ),
			'negative'    => array( array( 'quantity' => -1 ), array( 'rest_invalid_param', 'invalid_quantity' ) ),
			'missing key' => array( array(), array( 'rest_invalid_param', 'missing_line_item_id', 'missing_quantity_or_refund_total' ) ),
			'string'      => array( array( 'quantity' => 'abc' ), array( 'rest_invalid_param', 'invalid_quantity' ) ),
			'float'       => array( array( 'quantity' => 1.5 ), array( 'rest_invalid_param', 'invalid_quantity' ) ),
		);
	}

	/**
	 * @testdox Preview rejects malformed line_items payload at REST validation boundary.
	 */
	public function test_preview_invalid_payload_shape(): void {
		$order = $this->create_order_with_product( 50.00, 1 );

		$response = $this->do_preview_request(
			$order->get_id(),
			array(
				array(
					'line_item_id' => 'not-an-int',
					'quantity'     => 'also-not-an-int',
				),
			)
		);

		$this->assertEquals( 400, $response->get_status() );
		$data = $response->get_data();
		$this->assertEquals( 'rest_invalid_param', $data['code'] );
	}

	/**
	 * @testdox Preview returns INVALID_ID for an order ID belonging to a non-shop_order post type.
	 */
	public function test_preview_non_shop_order_returns_invalid_id(): void {
		// Create a refund directly — wc_get_order() will return it but get_type() is shop_order_refund.
		$order  = $this->create_order_with_product( 50.00, 1 );
		$refund = wc_create_refund(
			array(
				'order_id' => $order->get_id(),
				'amount'   => 10.00,
			)
		);
		$this->assertNotInstanceOf( \WP_Error::class, $refund );

		$response = $this->do_preview_request(
			$refund->get_id(),
			array(
				array(
					'line_item_id' => $this->get_first_line_item_id( $order ),
					'quantity'     => 1,
				),
			)
		);

		$this->assertEquals( 404, $response->get_status() );
	}

	/**
	 * @testdox Preview rejects unauthorized users (read-only / customer role).
	 */
	public function test_preview_read_only_user_returns_forbidden(): void {
		$customer_id = wp_insert_user(
			array(
				'user_login' => 'preview_customer_' . wp_generate_password( 6, false ),
				'user_email' => 'customer_' . wp_generate_password( 6, false ) . '@example.com',
				'user_pass'  => 'password',
				'role'       => 'customer',
			)
		);
		if ( is_wp_error( $customer_id ) ) {
			$this->fail( 'Could not create test customer: ' . $customer_id->get_error_message() );
		}
		$customer_id = (int) $customer_id;
		wp_set_current_user( $customer_id );

		$order = $this->create_order_with_product( 50.00, 1 );

		$response = $this->do_preview_request(
			$order->get_id(),
			array(
				array(
					'line_item_id' => $this->get_first_line_item_id( $order ),
					'quantity'     => 1,
				),
			)
		);

		$this->assertContains( $response->get_status(), array( 401, 403 ) );

		// Restore admin user for teardown.
		wp_set_current_user( self::$user_id );
		wp_delete_user( $customer_id );
	}

	/**
	 * @testdox Response shape matches the published schema (keys-only parity, recursive).
	 */
	public function test_schema_matches_response_shape(): void {
		// Build a mixed-section order so every section's items[] has at least one entry to walk.
		$order   = $this->create_order_with_product( 50.00, 1 );
		$item_id = $this->get_first_line_item_id( $order );

		$shipping = new \WC_Order_Item_Shipping();
		$shipping->set_props(
			array(
				'method_title' => 'Flat Rate',
				'total'        => 10.00,
			)
		);
		$shipping->save();
		$order->add_item( $shipping );

		$fee = new \WC_Order_Item_Fee();
		$fee->set_props(
			array(
				'name'  => 'Service fee',
				'total' => 5.00,
			)
		);
		$fee->save();
		$order->add_item( $fee );

		$order->set_total( 65.00 );
		$order->save();

		$response = $this->do_preview_request(
			$order->get_id(),
			array(
				array(
					'line_item_id' => $item_id,
					'quantity'     => 1,
				),
				array(
					'line_item_id' => $shipping->get_id(),
					'quantity'     => 1,
				),
				array(
					'line_item_id' => $fee->get_id(),
					'quantity'     => 1,
				),
			)
		);

		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();

		$schema_properties = wc_get_container()
			->get( \Automattic\WooCommerce\Internal\RestApi\Routes\V4\Refunds\Schema\RefundPreviewSchema::class )
			->get_item_schema_properties();

		$this->assertSchemaKeysMatchData( $schema_properties, $data, 'root' );
	}

	/**
	 * Assert that every key present in $data is declared in the schema and vice
	 * versa for object subtrees. Skips assertion at array-of-objects boundaries
	 * (the items[] array) and instead recurses into the first element's shape
	 * against the items.items schema. Optional fields (e.g. product_id only on
	 * the products section) are tolerated when absent from $data.
	 *
	 * @param array  $schema Schema fragment (an associative array of property name => spec, or a single-property spec).
	 * @param mixed  $data   Data fragment at the same path.
	 * @param string $path   Dot path for assertion messages.
	 */
	private function assertSchemaKeysMatchData( array $schema, $data, string $path ): void {
		// Treat each entry as a property descriptor.
		foreach ( $schema as $name => $spec ) {
			if ( ! is_array( $spec ) ) {
				continue;
			}
			$type = $spec['type'] ?? null;
			if ( 'object' === $type && isset( $spec['properties'] ) ) {
				if ( ! array_key_exists( $name, $data ) ) {
					$this->fail( "Schema declares object '{$path}.{$name}' but response is missing it" );
				}
				$this->assertSchemaKeysMatchData( $spec['properties'], $data[ $name ], "{$path}.{$name}" );
			} elseif ( 'array' === $type && isset( $spec['items']['properties'] ) ) {
				if ( ! array_key_exists( $name, $data ) ) {
					$this->fail( "Schema declares array '{$path}.{$name}' but response is missing it" );
				}
				if ( ! empty( $data[ $name ] ) ) {
					$this->assertSchemaKeysMatchData( $spec['items']['properties'], $data[ $name ][0], "{$path}.{$name}[0]" );
				}
			} elseif ( ! array_key_exists( $name, $data ) ) {
				// Scalar field missing from data is OK. The products-only `product_id` field is
				// legitimately absent on shipping/fees sections.
				continue;
			}
		}

		// Inverse check: every key in $data should be declared in the schema.
		if ( is_array( $data ) && array_keys( $data ) !== range( 0, count( $data ) - 1 ) ) {
			foreach ( array_keys( $data ) as $key ) {
				if ( is_string( $key ) ) {
					$this->assertArrayHasKey(
						$key,
						$schema,
						"Response key '{$path}.{$key}' is not declared in the schema"
					);
				}
			}
		}
	}

	/**
	 * @testdox Preview returns 500 with invalid_preview_request when build_refund_preview throws an invariant violation.
	 */
	public function test_preview_invariant_violation_returns_500(): void {
		$order   = $this->create_order_with_product( 50.00, 1 );
		$item_id = $this->get_first_line_item_id( $order );

		// Stub DataUtils so validate_preview_line_items passes but build_refund_preview throws.
		$stub = new class() extends \Automattic\WooCommerce\Internal\RestApi\Routes\V4\Refunds\DataUtils {
			/**
			 * Validation is forced to pass so the controller reaches the build step.
			 *
			 * @param array     $line_items Ignored.
			 * @param \WC_Order $order      Ignored.
			 * @return bool
			 */
			public function validate_preview_line_items( array $line_items, \WC_Order $order ) {
				return true;
			}
			// Stub always throws; the : array return type is never reached.
			// phpcs:disable Squiz.Commenting.FunctionComment.InvalidNoReturn
			/**
			 * Always throws to exercise the controller's InvalidArgumentException catch arm.
			 *
			 * @param \WC_Order $order      Ignored.
			 * @param array     $line_items Ignored.
			 * @return array
			 * @throws \InvalidArgumentException Always.
			 */
			public function build_refund_preview( \WC_Order $order, array $line_items ): array {
				throw new \InvalidArgumentException( 'simulated invariant violation' );
			}
			// phpcs:enable Squiz.Commenting.FunctionComment.InvalidNoReturn
		};
		wc_get_container()->get( \Automattic\WooCommerce\Internal\RestApi\Routes\V4\Refunds\Controller::class )
			->init(
				wc_get_container()->get( \Automattic\WooCommerce\Internal\RestApi\Routes\V4\Refunds\Schema\RefundSchema::class ),
				wc_get_container()->get( \Automattic\WooCommerce\Internal\RestApi\Routes\V4\Refunds\Schema\RefundPreviewSchema::class ),
				wc_get_container()->get( \Automattic\WooCommerce\Internal\RestApi\Routes\V4\Refunds\CollectionQuery::class ),
				$stub
			);

		try {
			$response = $this->do_preview_request(
				$order->get_id(),
				array(
					array(
						'line_item_id' => $item_id,
						'quantity'     => 1,
					),
				)
			);

			$this->assertEquals( 500, $response->get_status() );
			$data = $response->get_data();
			$this->assertEquals( 'invalid_preview_request', $data['code'] );
		} finally {
			// Restore the real DataUtils for subsequent tests in this run.
			wc_get_container()->get( \Automattic\WooCommerce\Internal\RestApi\Routes\V4\Refunds\Controller::class )
				->init(
					wc_get_container()->get( \Automattic\WooCommerce\Internal\RestApi\Routes\V4\Refunds\Schema\RefundSchema::class ),
					wc_get_container()->get( \Automattic\WooCommerce\Internal\RestApi\Routes\V4\Refunds\Schema\RefundPreviewSchema::class ),
					wc_get_container()->get( \Automattic\WooCommerce\Internal\RestApi\Routes\V4\Refunds\CollectionQuery::class ),
					wc_get_container()->get( \Automattic\WooCommerce\Internal\RestApi\Routes\V4\Refunds\DataUtils::class )
				);
		}
	}

	/**
	 * @testdox Preview on order with shipping-only line returns populated shipping section.
	 */
	public function test_preview_shipping_line(): void {
		$order = $this->create_order_with_shipping( 10.00 );
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
		$this->assertCount( 1, $data['breakdown']['shipping']['items'] );
		$this->assertEmpty( $data['breakdown']['products']['items'] );
		$this->assertEmpty( $data['breakdown']['fees']['items'] );
		$this->assertEquals( '10.00', $data['breakdown']['shipping']['total'] );
		$this->assertEquals( '10.00', $data['total'] );
	}

	/**
	 * @testdox Preview on order with fee-only line returns populated fees section.
	 */
	public function test_preview_fee_line(): void {
		$order = $this->create_order_with_fee( 20.00 );
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
		$this->assertCount( 1, $data['breakdown']['fees']['items'] );
		$this->assertEmpty( $data['breakdown']['products']['items'] );
		$this->assertEmpty( $data['breakdown']['shipping']['items'] );
		$this->assertEquals( '20.00', $data['breakdown']['fees']['total'] );
		$this->assertEquals( '20.00', $data['total'] );
	}

	/**
	 * @testdox Preview on mixed order aggregates products, shipping, and fees sections correctly.
	 */
	public function test_preview_mixed_sections(): void {
		$order   = $this->create_order_with_product( 50.00, 1 );
		$item_id = $this->get_first_line_item_id( $order );

		$shipping = new \WC_Order_Item_Shipping();
		$shipping->set_props(
			array(
				'method_title' => 'Flat Rate',
				'total'        => 10.00,
			)
		);
		$shipping->save();
		$order->add_item( $shipping );

		$fee = new \WC_Order_Item_Fee();
		$fee->set_props(
			array(
				'name'  => 'Service fee',
				'total' => 5.00,
			)
		);
		$fee->save();
		$order->add_item( $fee );

		$order->set_total( 65.00 );
		$order->save();

		$response = $this->do_preview_request(
			$order->get_id(),
			array(
				array(
					'line_item_id' => $item_id,
					'quantity'     => 1,
				),
				array(
					'line_item_id' => $shipping->get_id(),
					'quantity'     => 1,
				),
				array(
					'line_item_id' => $fee->get_id(),
					'quantity'     => 1,
				),
			)
		);

		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertEquals( '50.00', $data['breakdown']['products']['total'] );
		$this->assertEquals( '10.00', $data['breakdown']['shipping']['total'] );
		$this->assertEquals( '5.00', $data['breakdown']['fees']['total'] );
		$this->assertEquals( '65.00', $data['total'] );
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
		$order         = wc_get_order( $order->get_id() );
		$refunds_after = $order->get_refunds();

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

		// Create the actual refund. Drive refund_total from the preview total so a divergence
		// between preview and create produces an actual mismatch rather than passing by coincidence.
		// Both preview `total` and create `refund_total` are tax-inclusive.
		$preview_total_with_tax = (float) $preview_data['total'];

		$create_request = new WP_REST_Request( 'POST', '/wc/v4/refunds' );
		$create_request->set_body_params(
			array(
				'order_id'   => $order->get_id(),
				'line_items' => array(
					array(
						'line_item_id' => $item_id,
						'quantity'     => 1,
						'refund_total' => $preview_total_with_tax,
					),
				),
			)
		);
		$create_response = $this->server->dispatch( $create_request );
		$this->assertEquals( 201, $create_response->get_status() );
		$create_data = $create_response->get_data();

		$this->assertEquals(
			wc_format_decimal( $preview_total_with_tax, wc_get_price_decimals() ),
			$create_data['total'],
			'Preview total + tax must match create refund amount exactly'
		);
	}

	/**
	 * @testdox Preview response includes product metadata (name, product_id).
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
		$this->assertArrayNotHasKey( 'variation_id', $product_item );
		$this->assertNotEmpty( $product_item['name'] );
		$this->assertEquals( $product->get_id(), $product_item['product_id'] );

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

		$this->assertEquals( 422, $response->get_status() );
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

	/**
	 * @testdox Partial amount preview on a product line returns the tax split for the requested amount.
	 */
	public function test_preview_partial_amount_product_line(): void {
		$tax_rate_id = $this->create_tax_rate( 10.0 );
		// $100 product + $10 tax = $110 total. Request a partial $55 refund.
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

		// refund_total = 55 (tax-inclusive). Tax extracted from 55 at 10%: ~5.00.
		$this->assertEquals( '55.00', $data['total'] );
		$this->assertGreaterThan( 0.0, (float) $data['tax'] );
		// quantity is null because the caller did not supply it.
		$this->assertNull( $data['breakdown']['products']['items'][0]['quantity'] );
	}

	/**
	 * @testdox Partial amount preview on a product line with both quantity and refund_total uses refund_total.
	 */
	public function test_preview_partial_amount_overrides_quantity_for_product(): void {
		// $10/unit × 5 units = $50 total. quantity=2 would compute $20, but refund_total=30 wins.
		$order   = $this->create_order_with_product( 10.00, 5 );
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

		// refund_total wins: total = 30.
		$this->assertEquals( '30.00', $data['total'] );
		// quantity is echoed back from the request.
		$this->assertEquals( 2, $data['breakdown']['products']['items'][0]['quantity'] );
	}

	/**
	 * @testdox Partial amount preview on a fee line returns correct tax split.
	 */
	public function test_preview_partial_amount_fee_line(): void {
		// $20 fee, no tax.
		$order = $this->create_order_with_fee( 20.00 );
		$items = $order->get_items( 'fee' );
		$item  = reset( $items );

		$response = $this->do_preview_request(
			$order->get_id(),
			array(
				array(
					'line_item_id' => $item->get_id(),
					'refund_total' => 8.00,
				),
			)
		);

		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();

		$this->assertEquals( '8.00', $data['total'] );
		$this->assertEquals( '0.00', $data['tax'] );
		$this->assertNull( $data['breakdown']['fees']['items'][0]['quantity'] );
	}

	/**
	 * @testdox Partial amount preview on a shipping line returns correct total.
	 */
	public function test_preview_partial_amount_shipping_line(): void {
		$order = $this->create_order_with_shipping( 15.00 );
		$items = $order->get_items( 'shipping' );
		$item  = reset( $items );

		$response = $this->do_preview_request(
			$order->get_id(),
			array(
				array(
					'line_item_id' => $item->get_id(),
					'refund_total' => 6.00,
				),
			)
		);

		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();

		$this->assertEquals( '6.00', $data['total'] );
		$this->assertNull( $data['breakdown']['shipping']['items'][0]['quantity'] );
	}

	/**
	 * @testdox Partial amount preview returns 422 when refund_total exceeds line item total.
	 */
	public function test_preview_partial_amount_exceeds_line_total_returns_422(): void {
		$order   = $this->create_order_with_product( 20.00, 1 );
		$item_id = $this->get_first_line_item_id( $order );

		$response = $this->do_preview_request(
			$order->get_id(),
			array(
				array(
					'line_item_id' => $item_id,
					'refund_total' => 25.00,
				),
			)
		);

		$this->assertEquals( 422, $response->get_status() );
		$data = $response->get_data();
		$this->assertEquals( 'refund_total_exceeds_line', $data['code'] );
	}

	/**
	 * @testdox Partial amount preview returns 400 when neither quantity nor refund_total is provided.
	 */
	public function test_preview_missing_quantity_and_refund_total_returns_400(): void {
		$order   = $this->create_order_with_product( 20.00, 1 );
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
		$data = $response->get_data();
		$this->assertEquals( 'missing_quantity_or_refund_total', $data['code'] );
	}

	/**
	 * @testdox Partial amount preview on fee returns 422 when refund_total exceeds remaining after prior partial refund.
	 */
	public function test_preview_partial_amount_fee_exceeds_remaining_returns_422(): void {
		$order = $this->create_order_with_fee( 20.00 );
		$items = $order->get_items( 'fee' );
		$item  = reset( $items );

		// First partial refund: $12 of the $20 fee.
		wc_create_refund(
			array(
				'order_id'   => $order->get_id(),
				'amount'     => 12.00,
				'line_items' => array(
					$item->get_id() => array(
						'qty'          => 0,
						'refund_total' => 12.00,
						'refund_tax'   => array(),
					),
				),
			)
		);

		// Try to refund $15, but only $8 remains.
		$response = $this->do_preview_request(
			$order->get_id(),
			array(
				array(
					'line_item_id' => $item->get_id(),
					'refund_total' => 15.00,
				),
			)
		);

		$this->assertEquals( 422, $response->get_status() );
		$data = $response->get_data();
		$this->assertEquals( 'refund_total_exceeds_remaining', $data['code'] );
	}

	/**
	 * @testdox Partial amount preview returns 400 invalid_refund_total when refund_total is zero.
	 */
	public function test_preview_partial_amount_zero_refund_total_returns_400(): void {
		$order   = $this->create_order_with_product( 20.00, 1 );
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
		$data = $response->get_data();
		$this->assertEquals( 'invalid_refund_total', $data['code'] );
	}

	/**
	 * @testdox Partial amount preview returns 400 invalid_refund_total when refund_total is zero even if quantity is provided.
	 */
	public function test_preview_zero_refund_total_with_quantity_returns_400(): void {
		// Regression: a zero refund_total used to be treated as absent by
		// validation (which then validated the quantity path) while
		// build_refund_preview() used the explicit 0, producing a 200
		// response with a $0.00 total. The combination must be rejected.
		$order   = $this->create_order_with_product( 20.00, 2 );
		$item_id = $this->get_first_line_item_id( $order );

		$response = $this->do_preview_request(
			$order->get_id(),
			array(
				array(
					'line_item_id' => $item_id,
					'quantity'     => 2,
					'refund_total' => 0,
				),
			)
		);

		$this->assertEquals( 400, $response->get_status() );
		$data = $response->get_data();
		$this->assertEquals( 'invalid_refund_total', $data['code'] );
	}

	/**
	 * @testdox Partial amount preview treats an explicit null refund_total as the quantity form.
	 */
	public function test_preview_null_refund_total_with_quantity_uses_quantity(): void {
		// null means "use the quantity form" — mirrors the create endpoint,
		// where null means "compute the total for me".
		$order   = $this->create_order_with_product( 20.00, 2 );
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
		$data = $response->get_data();
		$this->assertEquals( '20.00', $data['total'] );
		$this->assertSame( 1, $data['breakdown']['products']['items'][0]['quantity'] );
	}

	/**
	 * @testdox Partial amount preview on a product returns 422 when refund_total exceeds remaining after prior partial refund.
	 */
	public function test_preview_partial_amount_product_exceeds_remaining_returns_422(): void {
		$order   = $this->create_order_with_product( 50.00, 1 );
		$item_id = $this->get_first_line_item_id( $order );

		// First partial refund: $30 of the $50 product.
		wc_create_refund(
			array(
				'order_id'   => $order->get_id(),
				'amount'     => 30.00,
				'line_items' => array(
					$item_id => array(
						'qty'          => 0,
						'refund_total' => 30.00,
						'refund_tax'   => array(),
					),
				),
			)
		);

		// Try to refund $25, but only $20 remains.
		$response = $this->do_preview_request(
			$order->get_id(),
			array(
				array(
					'line_item_id' => $item_id,
					'refund_total' => 25.00,
				),
			)
		);

		$this->assertEquals( 422, $response->get_status() );
		$data = $response->get_data();
		$this->assertEquals( 'refund_total_exceeds_remaining', $data['code'] );
	}

	/**
	 * @testdox Partial amount preview splits tax so subtotal + tax equals the requested total exactly.
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
	 * @testdox Partial amount preview splits tax by the line's stored ratio, not the tax rate percent.
	 */
	public function test_preview_partial_amount_non_proportional_stored_tax(): void {
		// Tax rate is labelled 10% but the stored line tax is $8 on a $100 net ($108 incl).
		// A rate-based split of a $54 refund would extract 54 - 54/1.10 = $4.91; the correct
		// proportional split is 54 * 8/108 = $4.00.
		$tax_rate_id = $this->create_tax_rate( 10.0 );
		$order       = $this->create_order_with_product_and_tax( 100.00, 1, $tax_rate_id, 8.00 );
		$item_id     = $this->get_first_line_item_id( $order );

		$response = $this->do_preview_request(
			$order->get_id(),
			array(
				array(
					'line_item_id' => $item_id,
					'refund_total' => 54.00,
				),
			)
		);

		$this->assertEquals( 200, $response->get_status() );
		$item = $response->get_data()['breakdown']['products']['items'][0];

		$this->assertEquals( '4.00', $item['tax'], 'Tax must be split by the stored 8/108 ratio, not the 10% rate.' );
		$this->assertEquals( '50.00', $item['subtotal'] );
	}

	/**
	 * @testdox Partial amount preview keeps charged tax even when the order tax rate resolves to zero.
	 */
	public function test_preview_partial_amount_zero_rate_taxed_line(): void {
		// A line that was charged $10 tax but whose order tax item has a 0% rate. A rate-based
		// split would zero the tax out; the proportional split keeps it ($55 * 10/110 = $5.00).
		$tax_rate_id = $this->create_tax_rate( 0.0 );
		$order       = $this->create_order_with_product_and_tax( 100.00, 1, $tax_rate_id, 10.00 );
		$item_id     = $this->get_first_line_item_id( $order );

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
		$item = $response->get_data()['breakdown']['products']['items'][0];

		$this->assertEquals( '5.00', $item['tax'], 'Charged tax must not be dropped when the rate is zero.' );
		$this->assertEquals( '50.00', $item['subtotal'] );
	}

	/**
	 * @testdox A partial-amount preview matches the line totals stored on the created refund.
	 */
	public function test_preview_amount_matches_created_refund(): void {
		$tax_rate_id = $this->create_tax_rate( 10.0 );
		$order       = $this->create_order_with_product_and_tax( 100.00, 1, $tax_rate_id, 10.00 );
		$item_id     = $this->get_first_line_item_id( $order );

		$preview_response = $this->do_preview_request(
			$order->get_id(),
			array(
				array(
					'line_item_id' => $item_id,
					'refund_total' => 55.00,
				),
			)
		);
		$this->assertEquals( 200, $preview_response->get_status() );
		$preview_item = $preview_response->get_data()['breakdown']['products']['items'][0];

		$create_request = new WP_REST_Request( 'POST', '/wc/v4/refunds' );
		$create_request->set_body_params(
			array(
				'order_id'   => $order->get_id(),
				'line_items' => array(
					array(
						'line_item_id' => $item_id,
						'refund_total' => 55.00,
					),
				),
			)
		);
		$create_response = $this->server->dispatch( $create_request );
		$this->assertEquals( 201, $create_response->get_status() );

		$refund       = wc_get_order( $create_response->get_data()['id'] );
		$refund_items = $refund->get_items();
		$refund_item  = reset( $refund_items );
		$dp           = wc_get_price_decimals();

		$this->assertEquals(
			$preview_item['subtotal'],
			wc_format_decimal( abs( (float) $refund_item->get_total() ), $dp ),
			'Created refund line net total must match the previewed subtotal.'
		);
		$this->assertEquals(
			$preview_item['tax'],
			wc_format_decimal( abs( (float) $refund_item->get_total_tax() ), $dp ),
			'Created refund line tax must match the previewed tax.'
		);
	}

	/**
	 * @testdox A sub-cent refund_total is rounded to currency precision identically in preview and create.
	 */
	public function test_preview_partial_amount_rounds_to_currency_precision(): void {
		$order   = $this->create_order_with_product( 100.00, 1 );
		$item_id = $this->get_first_line_item_id( $order );

		$preview_response = $this->do_preview_request(
			$order->get_id(),
			array(
				array(
					'line_item_id' => $item_id,
					'refund_total' => 33.337,
				),
			)
		);
		$this->assertEquals( 200, $preview_response->get_status() );
		$this->assertEquals( '33.34', $preview_response->get_data()['total'], 'Preview total should round to currency precision.' );

		$create_request = new WP_REST_Request( 'POST', '/wc/v4/refunds' );
		$create_request->set_body_params(
			array(
				'order_id'   => $order->get_id(),
				'line_items' => array(
					array(
						'line_item_id' => $item_id,
						'refund_total' => 33.337,
					),
				),
			)
		);
		$create_response = $this->server->dispatch( $create_request );
		$this->assertEquals( 201, $create_response->get_status() );
		$this->assertEquals( '33.34', $create_response->get_data()['total'], 'Create amount must round identically to the preview.' );
	}

	/**
	 * @testdox Partial amount preview succeeds when refund_total exactly equals the line item total.
	 */
	public function test_preview_partial_amount_equal_to_line_total_succeeds(): void {
		$order   = $this->create_order_with_product( 20.00, 1 );
		$item_id = $this->get_first_line_item_id( $order );

		$response = $this->do_preview_request(
			$order->get_id(),
			array(
				array(
					'line_item_id' => $item_id,
					'refund_total' => 20.00,
				),
			)
		);

		$this->assertEquals( 200, $response->get_status(), 'A refund_total equal to the line total must be accepted.' );
		$this->assertEquals( '20.00', $response->get_data()['total'] );
	}

	/**
	 * @testdox Partial amount preview returns 422 line_item_already_refunded when the line is fully refunded.
	 */
	public function test_preview_partial_amount_fully_refunded_line_returns_422_already_refunded(): void {
		// Two-line order so the order itself stays refundable (the fee remains) while the
		// product line is fully refunded — otherwise the order-level guard fires first.
		$order   = $this->create_order_with_product( 50.00, 1 );
		$item_id = $this->get_first_line_item_id( $order );

		$fee = new \WC_Order_Item_Fee();
		$fee->set_props(
			array(
				'name'  => 'Service fee',
				'total' => 10.00,
			)
		);
		$fee->save();
		$order->add_item( $fee );
		$order->set_total( 60.00 );
		$order->save();

		wc_create_refund(
			array(
				'order_id'   => $order->get_id(),
				'amount'     => 50.00,
				'line_items' => array(
					$item_id => array(
						'qty'          => 1,
						'refund_total' => 50.00,
						'refund_tax'   => array(),
					),
				),
			)
		);

		$response = $this->do_preview_request(
			$order->get_id(),
			array(
				array(
					'line_item_id' => $item_id,
					'refund_total' => 5.00,
				),
			)
		);

		$this->assertEquals( 422, $response->get_status() );
		$this->assertEquals( 'line_item_already_refunded', $response->get_data()['code'] );
	}

	/**
	 * @testdox Preview returns 400 duplicate_line_item when the same line item appears more than once.
	 */
	public function test_preview_duplicate_line_item_returns_400(): void {
		// Without dedup, each entry validates against the same remaining snapshot, so two
		// $8 entries on a $10 line would each pass the per-line cap and double-count.
		$order = $this->create_order_with_fee( 10.00 );
		$items = $order->get_items( 'fee' );
		$item  = reset( $items );

		$response = $this->do_preview_request(
			$order->get_id(),
			array(
				array(
					'line_item_id' => $item->get_id(),
					'refund_total' => 8.00,
				),
				array(
					'line_item_id' => $item->get_id(),
					'refund_total' => 8.00,
				),
			)
		);

		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'duplicate_line_item', $response->get_data()['code'] );
	}

	/**
	 * @testdox Partial amount preview rounds to currency precision on a zero-decimal currency.
	 */
	public function test_preview_partial_amount_zero_decimal_currency(): void {
		$tax_rate_id = $this->create_tax_rate( 10.0 );
		// $100 net + $10 tax = $110 incl, stored at 2dp before the currency switch.
		$order   = $this->create_order_with_product_and_tax( 100.00, 1, $tax_rate_id, 10.00 );
		$item_id = $this->get_first_line_item_id( $order );

		// Switch to a zero-decimal currency for the request only.
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
	 * @testdox Preview rejects a quantity exceeding refundable units even when refund_total is supplied, matching create.
	 */
	public function test_preview_quantity_with_refund_total_exceeding_units_matches_create(): void {
		$order   = $this->create_order_with_product( 10.00, 1 );
		$item_id = $this->get_first_line_item_id( $order );

		$line_items = array(
			array(
				'line_item_id' => $item_id,
				'quantity'     => 2,
				'refund_total' => 1.00,
			),
		);

		$preview_response = $this->do_preview_request( $order->get_id(), $line_items );
		$this->assertEquals( 422, $preview_response->get_status(), 'Preview must reject a quantity over the refundable units.' );
		$this->assertEquals( 'quantity_exceeds_refundable', $preview_response->get_data()['code'] );

		$create_request = new WP_REST_Request( 'POST', '/wc/v4/refunds' );
		$create_request->set_body_params(
			array(
				'order_id'   => $order->get_id(),
				'line_items' => $line_items,
			)
		);
		$create_response = $this->server->dispatch( $create_request );
		$this->assertEquals( 422, $create_response->get_status(), 'Create rejects the same input, so preview must not return 200.' );
		$this->assertEquals( 'quantity_exceeds_refundable', $create_response->get_data()['code'] );
	}

	/**
	 * @testdox Preview rejects a product quantity refund that exceeds the remaining line amount after a prior amount-only refund, matching create.
	 */
	public function test_preview_product_quantity_after_amount_refund_matches_create(): void {
		$order   = $this->create_order_with_product( 100.00, 2 );
		$item_id = $this->get_first_line_item_id( $order );

		// Prior amount-only refund of $150 on the line (no units consumed: qty 0).
		wc_create_refund(
			array(
				'order_id'   => $order->get_id(),
				'amount'     => 150.00,
				'line_items' => array(
					$item_id => array(
						'qty'          => 0,
						'refund_total' => 150.00,
						'refund_tax'   => array(),
					),
				),
			)
		);

		$line_items = array(
			array(
				'line_item_id' => $item_id,
				'quantity'     => 2,
			),
		);

		// Preview must not return 200 while create rejects the same auto-filled $200 over-refund.
		$preview_response = $this->do_preview_request( $order->get_id(), $line_items );
		$this->assertEquals( 422, $preview_response->get_status() );
		$this->assertEquals( 'refund_total_exceeds_remaining', $preview_response->get_data()['code'] );

		$create_request = new WP_REST_Request( 'POST', '/wc/v4/refunds' );
		$create_request->set_body_params(
			array(
				'order_id'   => $order->get_id(),
				'line_items' => $line_items,
			)
		);
		$create_response = $this->server->dispatch( $create_request );
		$this->assertEquals( 422, $create_response->get_status() );
		$this->assertEquals( 'refund_total_exceeds_remaining', $create_response->get_data()['code'] );
	}

	/**
	 * @testdox Preview accepts a mixed refund with a negative discount-fee line, matching create.
	 */
	public function test_preview_partial_amount_negative_fee_matches_create(): void {
		$order   = $this->create_order_with_product( 50.00, 1 );
		$item_id = $this->get_first_line_item_id( $order );

		$fee = new \WC_Order_Item_Fee();
		$fee->set_props(
			array(
				'name'  => 'Discount',
				'total' => -10.00,
			)
		);
		$fee->save();
		$order->add_item( $fee );
		$order->set_total( 40.00 );
		$order->save();

		// Refund the full product line and the full discount: net $40, the order total.
		$line_items = array(
			array(
				'line_item_id' => $item_id,
				'refund_total' => 50.00,
			),
			array(
				'line_item_id' => $fee->get_id(),
				'refund_total' => -10.00,
			),
		);

		$preview_response = $this->do_preview_request( $order->get_id(), $line_items );
		$this->assertEquals( 200, $preview_response->get_status(), 'Preview must accept the negative discount-fee line.' );
		$this->assertEquals( '40.00', $preview_response->get_data()['total'] );

		$create_request = new WP_REST_Request( 'POST', '/wc/v4/refunds' );
		$create_request->set_body_params(
			array(
				'order_id'   => $order->get_id(),
				'line_items' => $line_items,
			)
		);
		$create_response = $this->server->dispatch( $create_request );
		$this->assertEquals( 201, $create_response->get_status(), 'Create accepts the same mixed request.' );
		$this->assertEquals( '40.00', $create_response->get_data()['total'], 'Create amount must match the preview total.' );
	}

	/**
	 * @testdox Preview rejects a refund whose aggregate total is negative, matching create.
	 */
	public function test_preview_negative_only_total_rejected_matches_create(): void {
		$order   = $this->create_order_with_product( 50.00, 1 );
		$item_id = $this->get_first_line_item_id( $order );

		$fee = new \WC_Order_Item_Fee();
		$fee->set_props(
			array(
				'name'  => 'Discount',
				'total' => -10.00,
			)
		);
		$fee->save();
		$order->add_item( $fee );
		$order->set_total( 40.00 );
		$order->save();

		// A refund of only the negative discount line nets -$5.
		$line_items = array(
			array(
				'line_item_id' => $fee->get_id(),
				'refund_total' => -5.00,
			),
		);

		$preview_response = $this->do_preview_request( $order->get_id(), $line_items );
		$this->assertNotEquals( 200, $preview_response->get_status(), 'Preview must not accept a non-positive aggregate total.' );
		$this->assertEquals( 'invalid_refund_amount', $preview_response->get_data()['code'] );

		$create_request = new WP_REST_Request( 'POST', '/wc/v4/refunds' );
		$create_request->set_body_params(
			array(
				'order_id'   => $order->get_id(),
				'line_items' => $line_items,
			)
		);
		$create_response = $this->server->dispatch( $create_request );
		$this->assertEquals( 'invalid_refund_amount', $create_response->get_data()['code'], 'Create rejects the same input.' );
	}

	/**
	 * @testdox Preview rejects a refund whose aggregate total nets to zero, matching create.
	 */
	public function test_preview_zero_net_total_rejected_matches_create(): void {
		// $20 product less a $10 discount: order stays refundable ($10), but the refund nets $0.
		$order   = $this->create_order_with_product( 20.00, 1 );
		$item_id = $this->get_first_line_item_id( $order );

		$fee = new \WC_Order_Item_Fee();
		$fee->set_props(
			array(
				'name'  => 'Discount',
				'total' => -10.00,
			)
		);
		$fee->save();
		$order->add_item( $fee );
		$order->set_total( 10.00 );
		$order->save();

		$line_items = array(
			array(
				'line_item_id' => $item_id,
				'refund_total' => 10.00,
			),
			array(
				'line_item_id' => $fee->get_id(),
				'refund_total' => -10.00,
			),
		);

		$preview_response = $this->do_preview_request( $order->get_id(), $line_items );
		$this->assertNotEquals( 200, $preview_response->get_status(), 'Preview must not accept a zero aggregate total.' );
		$this->assertEquals( 'invalid_refund_amount', $preview_response->get_data()['code'] );

		$create_request = new WP_REST_Request( 'POST', '/wc/v4/refunds' );
		$create_request->set_body_params(
			array(
				'order_id'   => $order->get_id(),
				'line_items' => $line_items,
			)
		);
		$create_response = $this->server->dispatch( $create_request );
		$this->assertEquals( 'invalid_refund_amount', $create_response->get_data()['code'], 'Create rejects the same input.' );
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
	 * Create a completed order with a single shipping line.
	 *
	 * @param float $total Shipping total.
	 * @return WC_Order
	 */
	private function create_order_with_shipping( float $total ): WC_Order {
		$order    = wc_create_order();
		$shipping = new \WC_Order_Item_Shipping();
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

		$this->created_orders[] = $order->get_id();

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
		$fee   = new \WC_Order_Item_Fee();
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

		$this->created_orders[] = $order->get_id();

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
