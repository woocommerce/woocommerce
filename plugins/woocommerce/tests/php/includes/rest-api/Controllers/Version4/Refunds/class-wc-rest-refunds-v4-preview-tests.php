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

		$this->assertEquals( 404, $response->get_status() );
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
	 * others by DataUtils::validate_preview_line_items (`invalid_quantity`).
	 * The test accepts either so it documents the actual observable behaviour
	 * without coupling to which layer rejects first.
	 *
	 * @return array<string, array<int, mixed>>
	 */
	public function invalid_quantity_provider(): array {
		return array(
			'zero'        => array( array( 'quantity' => 0 ), array( 'rest_invalid_param', 'invalid_quantity' ) ),
			'negative'    => array( array( 'quantity' => -1 ), array( 'rest_invalid_param', 'invalid_quantity' ) ),
			'missing key' => array( array(), array( 'rest_invalid_param', 'missing_line_item_id', 'invalid_quantity' ) ),
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
			$create_data['amount'],
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
