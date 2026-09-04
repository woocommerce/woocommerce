<?php
/**
 * Order Route Tests.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\StoreApi\Routes;

use Automattic\WooCommerce\Tests\Blocks\Helpers\ValidateSchema;

/**
 * Order Route Tests.
 *
 * Tests for the /wc/store/v1/order endpoint, focusing on authorization.
 */
class Order extends ControllerTestCase {
	/**
	 * Product ID shared by the class.
	 *
	 * @var int
	 */
	private static $product_id;

	/**
	 * Customer IDs shared by the class.
	 *
	 * @var int[]
	 */
	private static $customer_ids = array();

	/**
	 * Create immutable fixtures shared by all test methods.
	 *
	 * @param \WP_UnitTest_Factory $factory WordPress unit test factory.
	 */
	public static function wpSetUpBeforeClass( $factory ): void {
		$product          = self::create_class_fixture_products(
			array(
				array(
					'name'          => 'Test Product',
					'regular_price' => 10,
				),
			)
		)[0];
		self::$product_id = $product->get_id();

		self::$customer_ids = array(
			$factory->user->create(
				array(
					'role'       => 'customer',
					'user_email' => 'customer1@test.com',
				)
			),
			$factory->user->create(
				array(
					'role'       => 'customer',
					'user_email' => 'customer2@test.com',
				)
			),
		);
	}

	/**
	 * Delete the class-owned product through its data store.
	 */
	public static function wpTearDownAfterClass(): void {
		self::delete_class_fixture_products( array( self::$product_id ) );
	}

	/**
	 * Test product.
	 *
	 * @var \WC_Product
	 */
	private $product;

	/**
	 * Test customer user ID.
	 *
	 * @var int
	 */
	private $customer_id;

	/**
	 * Second test customer user ID.
	 *
	 * @var int
	 */
	private $customer_id_2;

	/**
	 * Setup test data.
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->product       = wc_get_product( self::$product_id );
		$this->customer_id   = self::$customer_ids[0];
		$this->customer_id_2 = self::$customer_ids[1];
	}

	/**
	 * Create a guest order for testing.
	 *
	 * @return \WC_Order
	 */
	private function create_guest_order() {
		$order = new \WC_Order();
		$order->set_billing_email( 'guest@example.com' );
		$order->set_billing_first_name( 'Guest' );
		$order->set_billing_last_name( 'User' );
		$order->add_product( $this->product, 1 );
		$order->calculate_totals();
		$order->save();

		return $order;
	}

	/**
	 * Create an order for a registered customer.
	 *
	 * @param int $customer_id Customer user ID.
	 * @return \WC_Order
	 */
	private function create_customer_order( $customer_id ) {
		$order = new \WC_Order();
		$order->set_customer_id( $customer_id );
		$order->set_billing_email( get_userdata( $customer_id )->user_email );
		$order->set_billing_first_name( 'Customer' );
		$order->set_billing_last_name( 'User' );
		$order->add_product( $this->product, 1 );
		$order->calculate_totals();
		$order->save();

		return $order;
	}

	/**
	 * The Order route has no other schema validation coverage.
	 *
	 * The item needs metadata, or the nested item_data schema is never exercised.
	 *
	 * @testdox Order response matches the published schema.
	 */
	public function test_response_matches_schema(): void {
		$order = $this->create_guest_order();
		$item  = current( $order->get_items() );
		$item->add_meta_data( 'Gift message', 'Happy birthday', true );
		$item->save();

		wp_set_current_user( 0 );

		$request = new \WP_REST_Request( 'GET', '/wc/store/v1/order/' . $order->get_id() );
		$request->set_param( 'key', $order->get_order_key() );
		$request->set_param( 'billing_email', $order->get_billing_email() );

		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );

		$routes     = new \Automattic\WooCommerce\StoreApi\RoutesController( new \Automattic\WooCommerce\StoreApi\SchemaController( $this->mock_extend ) );
		$controller = $routes->get( 'order', 'v1' );
		$validate   = new ValidateSchema( $controller->get_item_schema() );

		$data = $response->get_data();
		$diff = $validate->get_diff_from_object( $data );

		// Other mismatches on this response are out of scope (items' `type` and `extensions`,
		// `quantity_limits`, `fees`). Filter to item_data so this does not snapshot them.
		$item_data_diff = array_values(
			array_filter(
				array_merge( $diff['missing'] ?? array(), $diff['invalid_type'] ?? array(), $diff['no_schema'] ?? array() ),
				function ( $entry ) {
					return false !== strpos( $entry, 'item_data' );
				}
			)
		);
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
		$this->assertEmpty( $item_data_diff, print_r( $item_data_diff, true ) );

		$item_data = $data['items'][0]['item_data'];
		$this->assertNotEmpty( $item_data );

		// ValidateSchema only recurses into the first entry, so pin the exact public shape here.
		$entry = $item_data[0];
		$this->assertEqualSets( array( 'id', 'key', 'value', 'display_key', 'display_value' ), array_keys( $entry ) );
		$this->assertSame( 'Gift message', $entry['key'] );
		$this->assertSame( 'Happy birthday', $entry['value'] );
	}

	/**
	 * Consumers read this as a list, the way the cart endpoint sends it.
	 *
	 * @testdox Order item_data serializes as a JSON list carrying the meta row ID.
	 */
	public function test_item_data_serializes_as_a_json_list_carrying_the_meta_id(): void {
		$order = $this->create_guest_order();
		$item  = current( $order->get_items() );
		$item->add_meta_data( 'Gift message', 'Happy birthday', true );
		$item->save();

		wp_set_current_user( 0 );

		$request = new \WP_REST_Request( 'GET', '/wc/store/v1/order/' . $order->get_id() );
		$request->set_param( 'key', $order->get_order_key() );
		$request->set_param( 'billing_email', $order->get_billing_email() );

		$item_data = rest_get_server()->dispatch( $request )->get_data()['items'][0]['item_data'];
		$meta_id   = current( $item->get_meta_data() )->id;

		$this->assertSame( array( 0 ), array_keys( $item_data ), 'item_data must be a list, not keyed by meta ID.' );
		$this->assertStringStartsWith( '[', wp_json_encode( $item_data ), 'item_data must serialize as a JSON list, not an object.' );
		$this->assertSame( $meta_id, $item_data[0]['id'], 'The meta row ID must survive as `id`.' );
	}

	/**
	 * Callbacks can add their own fields; reshaping the container must not drop them.
	 *
	 * @testdox Order item_data keeps fields added by extensions.
	 */
	public function test_item_data_keeps_fields_added_by_extensions(): void {
		$order = $this->create_guest_order();
		$item  = current( $order->get_items() );
		$item->add_meta_data( 'Gift message', 'Happy birthday', true );
		$item->save();

		add_filter(
			'woocommerce_order_item_get_formatted_meta_data',
			function ( $formatted_meta ) {
				foreach ( $formatted_meta as $meta ) {
					$meta->custom_field = 'from-extension';
				}
				return $formatted_meta;
			}
		);

		wp_set_current_user( 0 );

		$request = new \WP_REST_Request( 'GET', '/wc/store/v1/order/' . $order->get_id() );
		$request->set_param( 'key', $order->get_order_key() );
		$request->set_param( 'billing_email', $order->get_billing_email() );

		$entry = rest_get_server()->dispatch( $request )->get_data()['items'][0]['item_data'][0];

		$this->assertSame( 'from-extension', $entry['custom_field'] ?? null, 'Extension-added fields must survive.' );
		$this->assertSame( current( $item->get_meta_data() )->id, $entry['id'], 'The row ID must win over anything a callback sets.' );
	}

	/**
	 * A misbehaving callback should cost the endpoint its metadata, not its response.
	 *
	 * @testdox Order endpoint survives a callback that returns a non-array.
	 */
	public function test_item_data_survives_a_hostile_filter(): void {
		$order = $this->create_guest_order();
		$item  = current( $order->get_items() );
		$item->add_meta_data( 'Gift message', 'Happy birthday', true );
		$item->save();

		add_filter(
			'woocommerce_order_item_get_formatted_meta_data',
			function () {
				return 'not-an-array';
			}
		);

		wp_set_current_user( 0 );

		$request = new \WP_REST_Request( 'GET', '/wc/store/v1/order/' . $order->get_id() );
		$request->set_param( 'key', $order->get_order_key() );
		$request->set_param( 'billing_email', $order->get_billing_email() );

		$response = rest_get_server()->dispatch( $request );

		$this->assertSame( 200, $response->get_status(), 'A bad callback must not fail the request.' );
		$this->assertSame( [], $response->get_data()['items'][0]['item_data'] );
	}

	/**
	 * @testdox Order item_data skips metadata entries that are not objects or arrays.
	 */
	public function test_item_data_skips_non_object_entries(): void {
		$order = $this->create_guest_order();
		$item  = current( $order->get_items() );
		$item->add_meta_data( 'Gift message', 'Happy birthday', true );
		$item->save();

		add_filter(
			'woocommerce_order_item_get_formatted_meta_data',
			function ( $formatted_meta ) {
				$formatted_meta[999] = 'scalar-entry';
				return $formatted_meta;
			}
		);

		wp_set_current_user( 0 );

		$request = new \WP_REST_Request( 'GET', '/wc/store/v1/order/' . $order->get_id() );
		$request->set_param( 'key', $order->get_order_key() );
		$request->set_param( 'billing_email', $order->get_billing_email() );

		$item_data = rest_get_server()->dispatch( $request )->get_data()['items'][0]['item_data'];

		$this->assertCount( 1, $item_data, 'The scalar entry must be skipped, the real one kept.' );
		$this->assertSame( 'Gift message', $item_data[0]['key'] );
	}

	/**
	 * Still metadata the store wants shown, so it is published with a null `id` rather than dropped.
	 *
	 * @testdox Order item_data reports a null id for an entry not keyed by a meta row ID.
	 */
	public function test_item_data_reports_null_id_for_entries_not_keyed_by_a_meta_row_id(): void {
		$order = $this->create_guest_order();
		$item  = current( $order->get_items() );
		$item->add_meta_data( 'Gift message', 'Happy birthday', true );
		$item->save();

		add_filter(
			'woocommerce_order_item_get_formatted_meta_data',
			function ( $formatted_meta ) {
				$formatted_meta['not-a-row-id'] = (object) array(
					'key'           => 'Injected',
					'value'         => 'value',
					'display_key'   => 'Injected',
					'display_value' => 'value',
				);
				return $formatted_meta;
			}
		);

		wp_set_current_user( 0 );

		$request = new \WP_REST_Request( 'GET', '/wc/store/v1/order/' . $order->get_id() );
		$request->set_param( 'key', $order->get_order_key() );
		$request->set_param( 'billing_email', $order->get_billing_email() );

		$item_data = rest_get_server()->dispatch( $request )->get_data()['items'][0]['item_data'];

		$this->assertCount( 2, $item_data, 'The entry must be kept, not dropped.' );
		$this->assertSame( current( $item->get_meta_data() )->id, $item_data[0]['id'] );
		$this->assertSame( 'Injected', $item_data[1]['key'] );
		$this->assertNull( $item_data[1]['id'], 'An entry with no stored row has no ID to report.' );
	}

	/**
	 * `get_formatted_meta_data()` skips hidden rows, so an appended entry's next-int key can be a
	 * hidden row's ID: a hidden row saved right after the item's visible ones sits on that number.
	 *
	 * @testdox Order item_data does not report a hidden meta row's ID for an appended entry.
	 */
	public function test_item_data_ignores_hidden_meta_rows_when_matching_ids(): void {
		$order = $this->create_guest_order();
		$item  = current( $order->get_items() );
		$item->add_meta_data( 'Gift message', 'Happy birthday', true );
		$item->save();

		// What `wc_reduce_stock_levels()` does once the order is paid.
		$item->add_meta_data( '_reduced_stock', 1, true );
		$item->save();

		$row_ids = array_values( wp_list_pluck( $item->get_meta_data(), 'id' ) );

		$this->assertSame(
			$row_ids[0] + 1,
			$row_ids[1],
			'The hidden row must follow the visible one for this to exercise the collision.'
		);

		add_filter(
			'woocommerce_order_item_get_formatted_meta_data',
			function ( $formatted_meta ) {
				$formatted_meta[] = (object) array(
					'key'           => 'Appended',
					'value'         => 'value',
					'display_key'   => 'Appended',
					'display_value' => 'value',
				);
				return $formatted_meta;
			}
		);

		wp_set_current_user( 0 );

		$request = new \WP_REST_Request( 'GET', '/wc/store/v1/order/' . $order->get_id() );
		$request->set_param( 'key', $order->get_order_key() );
		$request->set_param( 'billing_email', $order->get_billing_email() );

		$item_data = rest_get_server()->dispatch( $request )->get_data()['items'][0]['item_data'];

		$this->assertCount( 2, $item_data, 'The hidden row must not become an entry of its own.' );
		$this->assertSame( $row_ids[0], $item_data[0]['id'] );
		$this->assertSame( 'Appended', $item_data[1]['key'] );
		$this->assertNull( $item_data[1]['id'], 'An entry with no stored row has no ID to report.' );
	}

	/**
	 * `WC_Meta_Data` keeps its fields protected, so casting one publishes mangled property names.
	 * Trunk serialized it through `JsonSerializable`, and so must this.
	 *
	 * @testdox Order item_data serializes an appended WC_Meta_Data through JsonSerializable.
	 */
	public function test_item_data_serializes_an_appended_wc_meta_data(): void {
		$order = $this->create_guest_order();
		$item  = current( $order->get_items() );
		$item->add_meta_data( 'Gift message', 'Happy birthday', true );
		$item->save();

		add_filter(
			'woocommerce_order_item_get_formatted_meta_data',
			function ( $formatted_meta ) {
				$formatted_meta[] = new \WC_Meta_Data(
					array(
						'id'    => 0,
						'key'   => 'Appended',
						'value' => 'value',
					)
				);
				return $formatted_meta;
			}
		);

		wp_set_current_user( 0 );

		$request = new \WP_REST_Request( 'GET', '/wc/store/v1/order/' . $order->get_id() );
		$request->set_param( 'key', $order->get_order_key() );
		$request->set_param( 'billing_email', $order->get_billing_email() );

		$item_data = rest_get_server()->dispatch( $request )->get_data()['items'][0]['item_data'];

		$this->assertSame( 'Appended', $item_data[1]['key'] );
		$this->assertSame( 'value', $item_data[1]['value'] );
		$this->assertNull( $item_data[1]['id'], 'The wrapped id is 0, not a row on this item.' );
		$this->assertStringNotContainsString( "\0", wp_json_encode( $item_data ), 'Protected property names must not reach the response.' );
	}

	/**
	 * An extension's own object need not offer `JsonSerializable`, and casting one publishes its
	 * non-public fields under mangled names.
	 *
	 * @testdox Order item_data publishes only the public fields of an appended plain object.
	 */
	public function test_item_data_publishes_only_public_fields_of_an_appended_object(): void {
		$order = $this->create_guest_order();
		$item  = current( $order->get_items() );
		$item->add_meta_data( 'Gift message', 'Happy birthday', true );
		$item->save();

		add_filter(
			'woocommerce_order_item_get_formatted_meta_data',
			function ( $formatted_meta ) {
				$formatted_meta[] = new class() {
					/**
					 * Metadata key.
					 *
					 * @var string
					 */
					public $key = 'Appended';

					/**
					 * Metadata value.
					 *
					 * @var string
					 */
					public $value = 'value';

					/**
					 * Metadata key, formatted for display.
					 *
					 * @var string
					 */
					public $display_key = 'Appended';

					/**
					 * Metadata value, formatted for display.
					 *
					 * @var string
					 */
					public $display_value = 'value';

					/**
					 * A field the extension keeps to itself.
					 *
					 * @var string
					 */
					protected $internal = 'internal';
				};
				return $formatted_meta;
			}
		);

		wp_set_current_user( 0 );

		$request = new \WP_REST_Request( 'GET', '/wc/store/v1/order/' . $order->get_id() );
		$request->set_param( 'key', $order->get_order_key() );
		$request->set_param( 'billing_email', $order->get_billing_email() );

		$item_data = rest_get_server()->dispatch( $request )->get_data()['items'][0]['item_data'];

		$this->assertSame( 'Appended', $item_data[1]['key'] );
		$this->assertSame( array( 'id', 'key', 'value', 'display_key', 'display_value' ), array_keys( $item_data[1] ), 'Only public fields belong in the response.' );
	}

	/**
	 * Appending gets PHP's next integer key. Publishing that as `id` would point consumers at
	 * another item's row.
	 *
	 * @testdox Order item_data reports a null id for an appended entry.
	 */
	public function test_item_data_reports_null_id_for_an_appended_entry(): void {
		$order = $this->create_guest_order();
		$item  = current( $order->get_items() );
		$item->add_meta_data( 'Gift message', 'Happy birthday', true );
		$item->save();

		add_filter(
			'woocommerce_order_item_get_formatted_meta_data',
			function ( $formatted_meta ) {
				$formatted_meta[] = (object) array(
					'key'           => 'Appended',
					'value'         => 'value',
					'display_key'   => 'Appended',
					'display_value' => 'value',
				);
				return $formatted_meta;
			}
		);

		wp_set_current_user( 0 );

		$request = new \WP_REST_Request( 'GET', '/wc/store/v1/order/' . $order->get_id() );
		$request->set_param( 'key', $order->get_order_key() );
		$request->set_param( 'billing_email', $order->get_billing_email() );

		$item_data = rest_get_server()->dispatch( $request )->get_data()['items'][0]['item_data'];

		$this->assertCount( 2, $item_data, 'The appended entry must be kept, not dropped.' );
		$this->assertSame( 'Appended', $item_data[1]['key'] );
		$this->assertNull( $item_data[1]['id'], 'An appended entry has no stored row, so no ID.' );
	}


	/**
	 * Test that a guest can access a guest order with valid order key and billing email.
	 */
	public function test_guest_can_access_guest_order_with_valid_credentials() {
		$order = $this->create_guest_order();

		wp_set_current_user( 0 );

		$request = new \WP_REST_Request( 'GET', '/wc/store/v1/order/' . $order->get_id() );
		$request->set_param( 'key', $order->get_order_key() );
		$request->set_param( 'billing_email', $order->get_billing_email() );

		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( $order->get_id(), $response->get_data()['id'] );
	}

	/**
	 * Test that billing email matching is case-insensitive.
	 */
	public function test_guest_can_access_guest_order_with_different_case_email() {
		$order = $this->create_guest_order(); // Has email: guest@example.com.

		wp_set_current_user( 0 );

		$request = new \WP_REST_Request( 'GET', '/wc/store/v1/order/' . $order->get_id() );
		$request->set_param( 'key', $order->get_order_key() );
		$request->set_param( 'billing_email', 'GUEST@EXAMPLE.COM' );

		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( $order->get_id(), $response->get_data()['id'] );
	}

	/**
	 * Test that a guest cannot access a guest order with invalid order key.
	 */
	public function test_guest_cannot_access_guest_order_with_invalid_key() {
		$order = $this->create_guest_order();

		wp_set_current_user( 0 );

		$request = new \WP_REST_Request( 'GET', '/wc/store/v1/order/' . $order->get_id() );
		$request->set_param( 'key', 'invalid_key' );
		$request->set_param( 'billing_email', $order->get_billing_email() );

		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test that a guest cannot access a guest order with invalid billing email.
	 */
	public function test_guest_cannot_access_guest_order_with_invalid_email() {
		$order = $this->create_guest_order();

		wp_set_current_user( 0 );

		$request = new \WP_REST_Request( 'GET', '/wc/store/v1/order/' . $order->get_id() );
		$request->set_param( 'key', $order->get_order_key() );
		$request->set_param( 'billing_email', 'wrong@example.com' );

		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test that a guest cannot access a guest order without providing billing email.
	 */
	public function test_guest_cannot_access_guest_order_without_email() {
		$order = $this->create_guest_order(); // Order has billing email set.

		wp_set_current_user( 0 );

		$request = new \WP_REST_Request( 'GET', '/wc/store/v1/order/' . $order->get_id() );
		$request->set_param( 'key', $order->get_order_key() );
		// Not providing billing_email at all.

		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test that a logged-in user CANNOT access a guest order without order key and billing email.
	 *
	 * This is the main security fix test - previously logged-in users could access ANY guest order.
	 */
	public function test_logged_in_user_cannot_access_guest_order_without_credentials() {
		$order = $this->create_guest_order();

		wp_set_current_user( $this->customer_id );

		$request = new \WP_REST_Request( 'GET', '/wc/store/v1/order/' . $order->get_id() );
		// Not providing order key or billing email.

		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test that a logged-in user cannot access a guest order with invalid key but valid email.
	 */
	public function test_logged_in_user_cannot_access_guest_order_with_invalid_key() {
		$order = $this->create_guest_order();

		wp_set_current_user( $this->customer_id );

		$request = new \WP_REST_Request( 'GET', '/wc/store/v1/order/' . $order->get_id() );
		$request->set_param( 'key', 'invalid_key' );
		$request->set_param( 'billing_email', $order->get_billing_email() );

		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test that a logged-in user cannot access a guest order with valid key but invalid email.
	 */
	public function test_logged_in_user_cannot_access_guest_order_with_invalid_email() {
		$order = $this->create_guest_order();

		wp_set_current_user( $this->customer_id );

		$request = new \WP_REST_Request( 'GET', '/wc/store/v1/order/' . $order->get_id() );
		$request->set_param( 'key', $order->get_order_key() );
		$request->set_param( 'billing_email', 'wrong@example.com' );

		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test that a logged-in user CAN access a guest order with valid order key and billing email.
	 */
	public function test_logged_in_user_can_access_guest_order_with_valid_credentials() {
		$order = $this->create_guest_order();

		wp_set_current_user( $this->customer_id );

		$request = new \WP_REST_Request( 'GET', '/wc/store/v1/order/' . $order->get_id() );
		$request->set_param( 'key', $order->get_order_key() );
		$request->set_param( 'billing_email', $order->get_billing_email() );

		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( $order->get_id(), $response->get_data()['id'] );
	}

	/**
	 * Test that a customer can access their own order.
	 */
	public function test_customer_can_access_own_order() {
		$order = $this->create_customer_order( $this->customer_id );

		wp_set_current_user( $this->customer_id );

		$request = new \WP_REST_Request( 'GET', '/wc/store/v1/order/' . $order->get_id() );

		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( $order->get_id(), $response->get_data()['id'] );
	}

	/**
	 * Test that a customer cannot access another customer's order.
	 */
	public function test_customer_cannot_access_other_customer_order() {
		$order = $this->create_customer_order( $this->customer_id );

		// Log in as a different customer.
		wp_set_current_user( $this->customer_id_2 );

		$request = new \WP_REST_Request( 'GET', '/wc/store/v1/order/' . $order->get_id() );

		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 403, $response->get_status() );
	}

	/**
	 * Test that a guest cannot access a customer's order.
	 */
	public function test_guest_cannot_access_customer_order() {
		$order = $this->create_customer_order( $this->customer_id );

		wp_set_current_user( 0 );

		$request = new \WP_REST_Request( 'GET', '/wc/store/v1/order/' . $order->get_id() );
		// Even with the order key, a guest should not be able to access a customer order.
		$request->set_param( 'key', $order->get_order_key() );
		$request->set_param( 'billing_email', $order->get_billing_email() );

		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 403, $response->get_status() );
	}

	/**
	 * Test that requesting a non-existent order returns 404.
	 */
	public function test_non_existent_order_returns_404() {
		wp_set_current_user( $this->customer_id );

		$request = new \WP_REST_Request( 'GET', '/wc/store/v1/order/999999999' );

		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 404, $response->get_status() );
	}

	/**
	 * Test that a subscriber (low-privileged logged-in user) cannot access guest orders.
	 *
	 * This specifically tests the reported vulnerability scenario.
	 */
	public function test_subscriber_cannot_access_guest_order() {
		$order = $this->create_guest_order();

		// Create a subscriber user (low privileged).
		$subscriber_id = \WC_Unit_Test_Case::factory()->user->create(
			array(
				'role' => 'subscriber',
			)
		);

		wp_set_current_user( $subscriber_id );

		$request = new \WP_REST_Request( 'GET', '/wc/store/v1/order/' . $order->get_id() );
		// Not providing any credentials - this should be denied.

		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 401, $response->get_status() );

		// Clean up.
		wp_delete_user( $subscriber_id );
	}

	/**
	 * Test guest order without billing email can be accessed with valid key and no email param.
	 *
	 * Orders without billing emails (e.g., manually created) can be accessed
	 * if no billing_email param is provided (empty matches empty).
	 */
	public function test_guest_order_without_billing_email_can_be_accessed_with_empty_email() {
		$order = new \WC_Order();
		$order->set_billing_first_name( 'Guest' );
		$order->set_billing_last_name( 'User' );
		$order->set_billing_email( '' );
		$order->add_product( $this->product, 1 );
		$order->calculate_totals();
		$order->save();

		wp_set_current_user( 0 );

		// Valid key with no email param - empty matches empty, so access granted.
		$request = new \WP_REST_Request( 'GET', '/wc/store/v1/order/' . $order->get_id() );
		$request->set_param( 'key', $order->get_order_key() );

		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( $order->get_id(), $response->get_data()['id'] );
	}

	/**
	 * Test guest order without billing email cannot be accessed with a non-empty email param.
	 *
	 * If an email param is provided but the order has no email, they won't match.
	 */
	public function test_guest_order_without_billing_email_cannot_be_accessed_with_wrong_email() {
		$order = new \WC_Order();
		$order->set_billing_first_name( 'Guest' );
		$order->set_billing_last_name( 'User' );
		$order->set_billing_email( '' );
		$order->add_product( $this->product, 1 );
		$order->calculate_totals();
		$order->save();

		wp_set_current_user( 0 );

		// Valid key but email param provided - won't match empty order email.
		$request = new \WP_REST_Request( 'GET', '/wc/store/v1/order/' . $order->get_id() );
		$request->set_param( 'key', $order->get_order_key() );
		$request->set_param( 'billing_email', 'any@example.com' );

		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 401, $response->get_status() );
	}
}
