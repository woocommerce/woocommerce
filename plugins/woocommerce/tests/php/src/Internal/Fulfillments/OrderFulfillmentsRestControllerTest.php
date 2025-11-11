<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Fulfillments;

use Automattic\WooCommerce\Internal\Fulfillments\OrderFulfillmentsRestController;
use Automattic\WooCommerce\Tests\Internal\Fulfillments\Helpers\FulfillmentsHelper;
use WC_Helper_Order;
use WC_Order;
use WC_REST_Unit_Test_Case;
use WP_Http;
use WP_REST_Request;

/**
 * Class OrderFulfillmentsRestControllerTest
 *
 * @package Automattic\WooCommerce\Tests\Internal\Orders
 */
class OrderFulfillmentsRestControllerTest extends WC_REST_Unit_Test_Case {
	/**
	 * @var OrderFulfillmentsRestController
	 */
	private OrderFulfillmentsRestController $controller;

	/**
	 * Array of created orders' ID's. Keeping it to be deleted in tearDownAfterClass.
	 *
	 * @var array
	 */
	private static array $created_order_ids = array();

	/**
	 * Created user ID for testing purposes.
	 *
	 * @var int
	 */
	private static int $created_user_id = -1;

	/**
	 * Setup test case.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->controller = new OrderFulfillmentsRestController();
		$this->controller->register_routes();
	}

	/**
	 * Initializes the test environment before all tests on this file are run.
	 */
	public static function setupBeforeClass(): void {
		parent::setupBeforeClass();

		update_option( 'woocommerce_feature_fulfillments_enabled', 'yes' );
		$controller = wc_get_container()->get( \Automattic\WooCommerce\Internal\Fulfillments\FulfillmentsController::class );
		$controller->register();
		$controller->initialize_fulfillments();

		self::$created_user_id = wp_create_user( 'test_user', 'password', 'nonadmin@example.com' );

		for ( $order_number = 1; $order_number <= 10; $order_number++ ) {
			$order                     = WC_Helper_Order::create_order( get_current_user_id() );
			self::$created_order_ids[] = $order->get_id();
			for ( $fulfillment = 1; $fulfillment <= 10; $fulfillment++ ) {
				FulfillmentsHelper::create_fulfillment(
					array(
						'entity_type' => WC_Order::class,
						'entity_id'   => $order->get_id(),
					)
				);
			}
		}
	}

	/**
	 * Destroys the test environment after all tests on this file are run.
	 */
	public static function tearDownAfterClass(): void {
		// Delete the created orders and their fulfillments.
		global $wpdb;
		$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}wc_order_fulfillments;" );
		$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}wc_order_fulfillment_meta;" );
		foreach ( self::$created_order_ids as $order_id ) {
			WC_Helper_Order::delete_order( $order_id );
		}

		// Delete the created user.
		wp_delete_user( self::$created_user_id );
		update_option( 'woocommerce_feature_fulfillments_enabled', 'no' );

		parent::tearDownAfterClass();
	}

	/**
	 * Test the get_items method.
	 */
	public function test_get_fulfillments_nominal() {
		// Do the request for an order which the current user owns.
		$request  = new WP_REST_Request( 'GET', '/wc/v3/orders/' . self::$created_order_ids[0] . '/fulfillments' );
		$response = $this->server->dispatch( $request );

		// Check the response.
		$this->assertEquals( 200, $response->get_status() );
		$this->assertIsArray( $response->get_data() );

		$fulfillments = $response->get_data();
		$this->assertIsArray( $fulfillments );
		$this->assertCount( 10, $fulfillments );
		$this->assertEquals( 10, count( $fulfillments ) );

		foreach ( $fulfillments as $fulfillment ) {
			$this->assertEquals( WC_Order::class, $fulfillment['entity_type'] );
			$this->assertEquals( self::$created_order_ids[0], $fulfillment['entity_id'] );
		}
	}

	/**
	 * Test the get_items method with an invalid order ID.
	 */
	public function test_get_fulfillments_invalid_order_id() {
		// Do the request with an invalid order ID.
		$request  = new WP_REST_Request( 'GET', '/wc/v3/orders/999999/fulfillments' );
		$response = $this->server->dispatch( $request );

		// Check the response.
		$this->assertEquals( WP_Http::NOT_FOUND, $response->get_status() );
		$this->assertEquals( 'Invalid order ID.', $response->get_data()['message'] );
	}

	/**
	 * Test the get_items method with a non-matching user.
	 */
	public function test_get_fulfillments_invalid_user() {
		// Prepare the test environment.
		$current_user = wp_get_current_user();
		$this->assertEquals( 0, $current_user->ID );
		wp_set_current_user( self::$created_user_id );
		$this->assertEquals( self::$created_user_id, get_current_user_id() );
		$this->assertFalse( current_user_can( 'manage_woocommerce' ) ); // phpcs:ignore WordPress.WP.Capabilities.Unknown

		// Do the request as a non-admin user, for another user's order.
		$request  = new WP_REST_Request( 'GET', '/wc/v3/orders/' . self::$created_order_ids[0] . '/fulfillments' );
		$response = $this->server->dispatch( $request );

		// Check the response.
		$this->assertEquals( WP_Http::FORBIDDEN, $response->get_status() );
		$this->assertEquals(
			array(
				'code'    => 'woocommerce_rest_cannot_view',
				'message' => 'Sorry, you cannot view resources.',
				'data'    => array( 'status' => WP_Http::FORBIDDEN ),
			),
			$response->get_data()
		);

		// Clean up the test environment.
		wp_set_current_user( $current_user->ID );
	}

	/**
	 * Test the get_items method with an administrator.
	 */
	public function test_get_fulfillments_with_admin() {
		// Prepare the test environment.
		$current_user = wp_get_current_user();
		$this->assertEquals( 0, $current_user->ID );
		$this->assertFalse( current_user_can( 'manage_woocommerce' ) ); // phpcs:ignore WordPress.WP.Capabilities.Unknown
		wp_set_current_user( 1 );
		$this->assertTrue( current_user_can( 'manage_woocommerce' ) ); // phpcs:ignore WordPress.WP.Capabilities.Unknown
		$this->assertEquals( 1, get_current_user_id() );

		// Do the request as an admin user, for another user's order.
		$request  = new WP_REST_Request( 'GET', '/wc/v3/orders/' . self::$created_order_ids[0] . '/fulfillments' );
		$response = $this->server->dispatch( $request );

		// Check the response.
		$this->assertEquals( WP_Http::OK, $response->get_status() );
		$this->assertIsArray( $response->get_data() );

		$this->assertArrayHasKey( 'entity_id', $response->get_data()[0] );
		$this->assertEquals( self::$created_order_ids[0], $response->get_data()[0]['entity_id'] );

		// Clean up the test environment.
		wp_set_current_user( $current_user->ID );
	}

	/**
	 * Test creating a fulfillment (user doesn't have rights).
	 */
	public function test_create_fulfillment_non_admin() {
		// Create a new order.
		$order = WC_Helper_Order::create_order( get_current_user_id() );
		$this->assertInstanceOf( WC_Order::class, $order );

		// Create a fulfillment for the order.
		$request = new WP_REST_Request( 'POST', '/wc/v3/orders/' . $order->get_id() . '/fulfillments' );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				array(
					'entity_type'  => WC_Order::class,
					'entity_id'    => '' . $order->get_id(),
					'status'       => 'unfulfilled',
					'is_fulfilled' => false,
					'meta_data'    => array(
						array(
							'key'   => 'test_meta_key',
							'value' => 'test_meta_value',
						),
						array(
							'key'   => 'test_meta_key_2',
							'value' => 'test_meta_value_2',
						),
						array(
							'key'   => '_items',
							'value' => array(
								array(
									'item_id' => 1,
									'qty'     => 2,
								),
								array(
									'item_id' => 2,
									'qty'     => 3,
								),
							),
						),
					),
				)
			)
		);
		$response = $this->server->dispatch( $request );

		// Check the response. It should be an error saying that a regular user cannot create a fulfillment.
		$this->assertEquals( WP_Http::UNAUTHORIZED, $response->get_status() );
		$this->assertEquals(
			array(
				'code'    => 'woocommerce_rest_cannot_create',
				'message' => 'Sorry, you cannot create resources.',
				'data'    => array( 'status' => WP_Http::UNAUTHORIZED ),
			),
			$response->get_data()
		);
	}

	/**
	 * Test creating a fulfillment (user is admin).
	 */
	public function test_create_fulfillment_as_admin() {
		// Create a new order.
		$order = WC_Helper_Order::create_order( get_current_user_id() );
		$this->assertInstanceOf( WC_Order::class, $order );

		// Create a fulfillment for the order.
		wp_set_current_user( 1 );
		$request = new WP_REST_Request( 'POST', '/wc/v3/orders/' . $order->get_id() . '/fulfillments' );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				array(
					'entity_type'  => WC_Order::class,
					'entity_id'    => '' . $order->get_id(),
					'status'       => 'unfulfilled',
					'is_fulfilled' => false,
					'meta_data'    => array(
						array(
							'id'    => 0,
							'key'   => 'test_meta_key',
							'value' => 'test_meta_value',
						),
						array(
							'id'    => 0,
							'key'   => 'test_meta_key_2',
							'value' => 'test_meta_value_2',
						),
						array(
							'id'    => 0,
							'key'   => '_items',
							'value' => array(
								array(
									'item_id' => 1,
									'qty'     => 2,
								),
								array(
									'item_id' => 2,
									'qty'     => 3,
								),
							),
						),
					),
				)
			)
		);
		$response = $this->server->dispatch( $request );

		// Check the response. It should be ok.
		$this->assertEquals( WP_Http::CREATED, $response->get_status() );
		$this->assertIsArray( $response->get_data() );
		$fulfillment = $response->get_data();
		$this->assertIsArray( $fulfillment );
		$this->assertArrayHasKey( 'id', $fulfillment );
		$this->assertNotNull( $fulfillment['id'] );
		$this->assertEquals( WC_Order::class, $fulfillment['entity_type'] );
		$this->assertEquals( $order->get_id(), $fulfillment['entity_id'] );
		$this->assertEquals( 'unfulfilled', $fulfillment['status'] );
		$this->assertEquals( false, $fulfillment['is_fulfilled'] );
		$this->assertIsArray( $fulfillment['meta_data'] );
		$this->assertCount( 3, $fulfillment['meta_data'] );
		$this->assertEquals( 'test_meta_value', $fulfillment['meta_data'][0]['value'] );
		$this->assertEquals( 'test_meta_value_2', $fulfillment['meta_data'][1]['value'] );
		$this->assertEquals( 'test_meta_key', $fulfillment['meta_data'][0]['key'] );
		$this->assertEquals( 'test_meta_key_2', $fulfillment['meta_data'][1]['key'] );
		$this->assertEquals( '_items', $fulfillment['meta_data'][2]['key'] );
		$this->assertEquals(
			array(
				array(
					'item_id' => 1,
					'qty'     => 2,
				),
				array(
					'item_id' => 2,
					'qty'     => 3,
				),
			),
			$fulfillment['meta_data'][2]['value']
		);

		// Clean up the test environment.
		wp_set_current_user( 0 );
	}

	/**
	 * Test creating a fulfillment without items.
	 */
	public function test_create_fulfillment_without_items() {
		// Create a new order.
		$order = WC_Helper_Order::create_order( get_current_user_id() );
		$this->assertInstanceOf( WC_Order::class, $order );

		// Set the current user to an admin.
		wp_set_current_user( 1 );

		// Create a fulfillment for the order.
		$request = new WP_REST_Request( 'POST', '/wc/v3/orders/' . $order->get_id() . '/fulfillments' );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				array(
					'entity_type'  => WC_Order::class,
					'entity_id'    => '' . $order->get_id(),
					'status'       => 'unfulfilled',
					'is_fulfilled' => false,
					'meta_data'    => array(
						array(
							'key'   => 'test_meta_key',
							'value' => 'test_meta_value',
						),
						array(
							'key'   => 'test_meta_key_2',
							'value' => 'test_meta_value_2',
						),
					),
				)
			)
		);
		$response = $this->server->dispatch( $request );

		// Check the response. It should be an error saying that a fulfillment should contain at least one item.
		$this->assertEquals( WP_Http::BAD_REQUEST, $response->get_status() );
		$this->assertEquals(
			array(
				'code'    => 0,
				'message' => 'The fulfillment should contain at least one item.',
				'data'    => array( 'status' => WP_Http::BAD_REQUEST ),
			),
			$response->get_data()
		);
	}

	/**
	 * Test creating a fulfillment with invalid items.
	 *
	 * @param array $items Invalid items to test.
	 *
	 * @dataProvider invalid_items_provider
	 */
	public function test_create_fulfillment_with_invalid_items( $items ) {
		// Create a new order.
		$order = WC_Helper_Order::create_order( get_current_user_id() );
		$this->assertInstanceOf( WC_Order::class, $order );

		// Set the current user to an admin.
		wp_set_current_user( 1 );

		// Create a fulfillment for the order with invalid items.
		$request = new WP_REST_Request( 'POST', '/wc/v3/orders/' . $order->get_id() . '/fulfillments' );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				array(
					'entity_type'  => WC_Order::class,
					'entity_id'    => '' . $order->get_id(),
					'status'       => 'unfulfilled',
					'is_fulfilled' => false,
					'meta_data'    => array(
						array(
							'id'    => 0,
							'key'   => '_items',
							'value' => $items,
						),
					),
				)
			)
		);
		$response = $this->server->dispatch( $request );

		// Check the response. It should be an error saying that the items are invalid.
		$this->assertEquals( WP_Http::BAD_REQUEST, $response->get_status() );
		$this->assertEquals(
			array(
				'code'    => 0,
				'message' => 'Invalid item.',
				'data'    => array( 'status' => WP_Http::BAD_REQUEST ),
			),
			$response->get_data()
		);
	}

	/**
	 * Test creating a fulfillment with an invalid order ID.
	 */
	public function test_create_fulfillment_invalid_order_id() {
		// Create a new order.
		$order = WC_Helper_Order::create_order( get_current_user_id() );
		$this->assertInstanceOf( WC_Order::class, $order );

		// Set the current user to an admin.
		wp_set_current_user( 1 );

		// Create a fulfillment for the order with an invalid order ID.
		$request = new WP_REST_Request( 'POST', '/wc/v3/orders/999999/fulfillments' );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				array(
					'entity_type'  => WC_Order::class,
					'entity_id'    => '' . $order->get_id(),
					'status'       => 'unfulfilled',
					'is_fulfilled' => false,
					'meta_data'    => array(
						array(
							'id'    => 0,
							'key'   => 'test_meta_key',
							'value' => 'test_meta_value',
						),
						array(
							'id'    => 0,
							'key'   => 'test_meta_key_2',
							'value' => 'test_meta_value_2',
						),
						array(
							'id'    => 0,
							'key'   => '_items',
							'value' => array(
								array(
									'item_id' => 1,
									'qty'     => 2,
								),
								array(
									'item_id' => 2,
									'qty'     => 3,
								),
							),
						),
					),
				)
			)
		);
		$response = $this->server->dispatch( $request );

		// Check the response. It should be an error saying that the order ID is invalid.
		$this->assertEquals( WP_Http::NOT_FOUND, $response->get_status() );
		$this->assertEquals(
			array(
				'code'    => 'woocommerce_rest_order_invalid_id',
				'message' => 'Invalid order ID.',
				'data'    => array( 'status' => WP_Http::NOT_FOUND ),
			),
			$response->get_data()
		);
	}

	/**
	 * Test getting a single fulfillment for a regular user.
	 */
	public function test_get_fulfillment_for_regular_user() {
		// Get a previously created order.
		$order_id = self::$created_order_ids[0];
		$request  = new WP_REST_Request( 'GET', '/wc/v3/orders/' . $order_id . '/fulfillments' );
		$response = $this->server->dispatch( $request );

		$fulfillments = $response->get_data();
		$this->assertIsArray( $fulfillments );
		$this->assertCount( 10, $fulfillments );

		$fulfillment_id = $fulfillments[0]['id'];

		// Get the fulfillment for the order.
		$request  = new WP_REST_Request( 'GET', '/wc/v3/orders/' . $order_id . '/fulfillments/' . $fulfillment_id );
		$response = $this->server->dispatch( $request );

		// Check if $fulfillments[0] is the same as $response.
		$this->assertEquals( WP_Http::OK, $response->get_status() );
		$this->assertIsArray( $response->get_data() );
		$fulfillment = $response->get_data();
		$this->assertEquals( $fulfillments[0]['id'], $fulfillment['id'] );
		$this->assertEquals( $fulfillments[0]['entity_type'], $fulfillment['entity_type'] );
		$this->assertEquals( $fulfillments[0]['entity_id'], $fulfillment['entity_id'] );
		$this->assertEquals( $fulfillments[0]['status'], $fulfillment['status'] );
		$this->assertEquals( $fulfillments[0]['is_fulfilled'], $fulfillment['is_fulfilled'] );
		$this->assertEquals( $fulfillments[0]['meta_data'], $fulfillment['meta_data'] );
		$this->assertEquals( $fulfillments[0]['date_updated'], $fulfillment['date_updated'] );
	}

	/**
	 * Test getting a single fulfillment for an admin user.
	 */
	public function test_get_fulfillment_for_admin_user() {
		// Get a previously created order.
		$order_id = self::$created_order_ids[0];
		$request  = new WP_REST_Request( 'GET', '/wc/v3/orders/' . $order_id . '/fulfillments' );
		$response = $this->server->dispatch( $request );

		$fulfillments = $response->get_data();
		$this->assertIsArray( $fulfillments );
		$this->assertCount( 10, $fulfillments );

		$fulfillment_id = $fulfillments[0]['id'];

		// Set the current user to an admin.
		wp_set_current_user( 1 );

		// Get the fulfillment for the order.
		$request  = new WP_REST_Request( 'GET', '/wc/v3/orders/' . $order_id . '/fulfillments/' . $fulfillment_id );
		$response = $this->server->dispatch( $request );

		// Check if $fulfillments[0] is the same as $response.
		$this->assertEquals( WP_Http::OK, $response->get_status() );
		$this->assertIsArray( $response->get_data() );
		$fulfillment = $response->get_data();
		$this->assertEquals( $fulfillments[0]['id'], $fulfillment['id'] );
	}

	/**
	 * Test getting a single fulfillment with an invalid order ID.
	 */
	public function test_get_fulfillment_invalid_order_id() {
		// Get a previously created order.
		$order_id = self::$created_order_ids[0];
		$request  = new WP_REST_Request( 'GET', '/wc/v3/orders/' . $order_id . '/fulfillments' );
		$response = $this->server->dispatch( $request );

		$fulfillments = $response->get_data();
		$this->assertIsArray( $fulfillments );
		$this->assertCount( 10, $fulfillments );

		$fulfillment_id = $fulfillments[0]['id'];

		// Get the fulfillment for the order with an invalid order ID.
		$request  = new WP_REST_Request( 'GET', '/wc/v3/orders/999999/fulfillments/' . $fulfillment_id );
		$response = $this->server->dispatch( $request );

		// Check the response. It should be an error saying that the order ID is invalid.
		$this->assertEquals( WP_Http::NOT_FOUND, $response->get_status() );
		$this->assertEquals(
			array(
				'code'    => 'woocommerce_rest_order_invalid_id',
				'message' => 'Invalid order ID.',
				'data'    => array( 'status' => WP_Http::NOT_FOUND ),
			),
			$response->get_data()
		);
	}

	/**
	 * Test getting a single fulfillment with an invalid fulfillment ID.
	 */
	public function test_get_fulfillment_invalid_fulfillment_id() {
		// Get a previously created order.
		$order_id = self::$created_order_ids[0];
		$request  = new WP_REST_Request( 'GET', '/wc/v3/orders/' . $order_id . '/fulfillments' );
		$response = $this->server->dispatch( $request );

		$fulfillments = $response->get_data();
		$this->assertIsArray( $fulfillments );
		$this->assertCount( 10, $fulfillments );

		// Get the fulfillment for the order with an invalid fulfillment ID.
		$request  = new WP_REST_Request( 'GET', '/wc/v3/orders/' . $order_id . '/fulfillments/999999' );
		$response = $this->server->dispatch( $request );

		// Check the response. It should be an error saying that the fulfillment ID is invalid.
		$this->assertEquals( WP_Http::BAD_REQUEST, $response->get_status() );

		$this->assertEquals(
			array(
				'code'    => 0,
				'message' => 'Fulfillment not found.',
				'data'    => array( 'status' => WP_Http::BAD_REQUEST ),
			),
			$response->get_data()
		);
	}

	/**
	 * Test getting a single fulfillment for a non-matching user.
	 */
	public function test_get_fulfillment_invalid_user() {
		// Prepare the test environment.
		$current_user = wp_get_current_user();

		// Get a previously created order.
		$order_id = self::$created_order_ids[0];
		$request  = new WP_REST_Request( 'GET', '/wc/v3/orders/' . $order_id . '/fulfillments' );
		$response = $this->server->dispatch( $request );

		$fulfillments = $response->get_data();
		$this->assertIsArray( $fulfillments );
		$this->assertCount( 10, $fulfillments );

		$fulfillment_id = $fulfillments[0]['id'];

		wp_set_current_user( self::$created_user_id );

		// Get the fulfillment for the order, with a different user.
		$request  = new WP_REST_Request( 'GET', '/wc/v3/orders/' . $order_id . '/fulfillments/' . $fulfillment_id );
		$response = $this->server->dispatch( $request );

		// Check the response. It should be an error saying that a regular user cannot view a fulfillment.
		$this->assertEquals( WP_Http::FORBIDDEN, $response->get_status() );
		$this->assertEquals(
			array(
				'code'    => 'woocommerce_rest_cannot_view',
				'message' => 'Sorry, you cannot view resources.',
				'data'    => array( 'status' => WP_Http::FORBIDDEN ),
			),
			$response->get_data()
		);

		wp_set_current_user( $current_user->ID );
	}

	/**
	 * Test updating a fulfillment for a regular user.
	 */
	public function test_update_fulfillment_for_regular_user() {
		// Get a previously created order.
		$order_id = self::$created_order_ids[0];
		$request  = new WP_REST_Request( 'GET', '/wc/v3/orders/' . $order_id . '/fulfillments' );
		$response = $this->server->dispatch( $request );

		$fulfillments = $response->get_data();
		$this->assertIsArray( $fulfillments );
		$this->assertCount( 10, $fulfillments );

		$fulfillment_id = $fulfillments[0]['id'];

		// Update the fulfillment for the order.
		wp_set_current_user( self::$created_user_id );
		$request = new WP_REST_Request( 'PUT', '/wc/v3/orders/' . $order_id . '/fulfillments/' . $fulfillment_id );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				array(
					'status'       => 'fulfilled',
					'is_fulfilled' => true,
					'meta_data'    => array(
						array(
							'id'    => 0,
							'key'   => 'test_meta_key',
							'value' => 'test_meta_value',
						),
						array(
							'id'    => 0,
							'key'   => 'test_meta_key_2',
							'value' => 'test_meta_value_2',
						),
						array(
							'id'    => 0,
							'key'   => '_items',
							'value' => array(
								array(
									'item_id' => 1,
									'qty'     => 2,
								),
								array(
									'item_id' => 2,
									'qty'     => 3,
								),
							),
						),
					),
				)
			)
		);
		wp_set_current_user( self::$created_user_id );
		wp_set_current_user( self::$created_user_id );
		$response = $this->server->dispatch( $request );
		// Check the response. It should be an error saying that a regular user cannot update a fulfillment.
		$this->assertEquals( WP_Http::FORBIDDEN, $response->get_status() );
		$this->assertEquals(
			array(
				'code'    => 'rest_forbidden',
				'message' => 'Sorry, you are not allowed to do that.',
				'data'    => array( 'status' => WP_Http::FORBIDDEN ),
			),
			$response->get_data()
		);
	}

	/**
	 * Test updating a fulfillment for an admin user.
	 */
	public function test_update_fulfillment_for_admin_user() {
		// Get a previously created order.
		$order_id = self::$created_order_ids[0];
		$request  = new WP_REST_Request( 'GET', '/wc/v3/orders/' . $order_id . '/fulfillments' );
		$response = $this->server->dispatch( $request );

		$fulfillments = $response->get_data();
		$this->assertIsArray( $fulfillments );
		$this->assertCount( 10, $fulfillments );

		$fulfillment_id = $fulfillments[0]['id'];

		// Update the fulfillment for the order.
		wp_set_current_user( 1 );
		$request = new WP_REST_Request( 'PUT', '/wc/v3/orders/' . $order_id . '/fulfillments/' . $fulfillment_id );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				array(
					'status'       => 'fulfilled',
					'is_fulfilled' => true,
					'meta_data'    => array(
						// Test value delete by changing the key.
						array(
							'id'    => 0,
							'key'   => 'test_meta_key_ok',
							'value' => 'test_meta_value_ok',
						),
						// Test new value.
						array(
							'id'    => 0,
							'key'   => 'test_meta_key_2',
							'value' => 'test_meta_value_2_ok',
						),
						// Test items update.
						array(
							'id'    => 0,
							'key'   => '_items',
							'value' => array(
								array(
									'item_id' => 10,
									'qty'     => 20,
								),
								array(
									'item_id' => 20,
									'qty'     => 30,
								),
							),
						),
					),
				)
			)
		);

		$response = $this->server->dispatch( $request );

		// Check the response. It should be ok.
		$this->assertEquals( WP_Http::OK, $response->get_status() );

		$this->assertIsArray( $response->get_data() );

		$fulfillment = $response->get_data();
		$this->assertIsArray( $fulfillment );

		$this->assertArrayHasKey( 'id', $fulfillment );
		$this->assertNotNull( $fulfillment['id'] );

		$this->assertEquals( WC_Order::class, $fulfillment['entity_type'] );
		$this->assertEquals( $order_id, $fulfillment['entity_id'] );
		$this->assertEquals( 'fulfilled', $fulfillment['status'] );
		$this->assertEquals( true, $fulfillment['is_fulfilled'] );

		$this->assertIsArray( $fulfillment['meta_data'] );
		$this->assertCount( 4, $fulfillment['meta_data'] ); // _fulfilled_date is added automatically.

		// Test updated meta data.
		$this->assertNotContains( 'test_meta_key', wp_list_pluck( $fulfillment['meta_data'], 'key' ) );
		foreach ( $fulfillment['meta_data'] as $meta ) {
			$this->assertArrayHasKey( 'id', $meta );
			$this->assertArrayHasKey( 'key', $meta );
			$this->assertArrayHasKey( 'value', $meta );
			switch ( $meta['key'] ) {
				case 'test_meta_key_ok':
					$this->assertEquals( 'test_meta_value_ok', $meta['value'] );
					break;
				case 'test_meta_key_2':
					$this->assertEquals( 'test_meta_value_2_ok', $meta['value'] );
					break;
				case '_items':
					$this->assertEquals(
						array(
							array(
								'item_id' => 10,
								'qty'     => 20,
							),
							array(
								'item_id' => 20,
								'qty'     => 30,
							),
						),
						$meta['value']
					);
					break;
			}
		}

		wp_set_current_user( self::$created_user_id );
	}

	/**
	 * Test updating a fulfillment with an invalid order ID.
	 */
	public function test_update_fulfillment_invalid_order_id() {
		// Get a previously created order.
		$order_id = self::$created_order_ids[0];
		$request  = new WP_REST_Request( 'GET', '/wc/v3/orders/' . $order_id . '/fulfillments' );
		$response = $this->server->dispatch( $request );

		$fulfillments = $response->get_data();
		$this->assertIsArray( $fulfillments );
		$this->assertCount( 10, $fulfillments );

		$fulfillment_id = $fulfillments[0]['id'];

		// Update the fulfillment for the order with an invalid order ID.
		wp_set_current_user( 1 );
		$request = new WP_REST_Request( 'PUT', '/wc/v3/orders/999999/fulfillments/' . $fulfillment_id );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				array(
					'status'       => 'fulfilled',
					'is_fulfilled' => true,
					'meta_data'    => array(
						array(
							'id'    => 0,
							'key'   => 'test_meta_key',
							'value' => 'test_meta_value',
						),
						array(
							'id'    => 0,
							'key'   => 'test_meta_key_2',
							'value' => 'test_meta_value_2',
						),
						array(
							'id'    => 0,
							'key'   => '_items',
							'value' => array(
								array(
									'item_id' => 1,
									'qty'     => 2,
								),
								array(
									'item_id' => 2,
									'qty'     => 3,
								),
							),
						),
					),
				)
			)
		);
		$response = $this->server->dispatch( $request );
		// Check the response. It should be an error saying that the order ID is invalid.
		$this->assertEquals( WP_Http::NOT_FOUND, $response->get_status() );
		$this->assertEquals(
			array(
				'code'    => 'woocommerce_rest_order_invalid_id',
				'message' => 'Invalid order ID.',
				'data'    => array( 'status' => WP_Http::NOT_FOUND ),
			),
			$response->get_data()
		);

		// Clean up the test environment.
		wp_set_current_user( 0 );
	}

	/**
	 * Test updating a fulfillment with an invalid fulfillment ID.
	 */
	public function test_update_fulfillment_invalid_fulfillment_id() {
		// Get a previously created order.
		$order_id = self::$created_order_ids[0];
		$request  = new WP_REST_Request( 'GET', '/wc/v3/orders/' . $order_id . '/fulfillments' );
		$response = $this->server->dispatch( $request );

		$fulfillments = $response->get_data();
		$this->assertIsArray( $fulfillments );
		$this->assertCount( 10, $fulfillments );

		// Update the fulfillment for the order with an invalid fulfillment ID.
		wp_set_current_user( 1 );
		$request = new WP_REST_Request( 'PUT', '/wc/v3/orders/' . $order_id . '/fulfillments/999999' );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				array(
					'status'       => 'fulfilled',
					'is_fulfilled' => true,
					'meta_data'    => array(
						array(
							'id'    => 0,
							'key'   => 'test_meta_key',
							'value' => 'test_meta_value',
						),
						array(
							'id'    => 0,
							'key'   => 'test_meta_key_2',
							'value' => 'test_meta_value_2',
						),
						array(
							'id'    => 0,
							'key'   => '_items',
							'value' =>
								array(
									array(
										'item_id' => 1,
										'qty'     => 2,
									),
									array(
										'item_id' => 2,
										'qty'     => 3,
									),

								),
						),
					),
				)
			)
		);

		$response = $this->server->dispatch( $request );
		// Check the response. It should be an error saying that the fulfillment ID is invalid.
		$this->assertEquals( WP_Http::BAD_REQUEST, $response->get_status() );
		$this->assertEquals(
			array(
				'code'    => 0,
				'message' => 'Fulfillment not found.',
				'data'    => array( 'status' => WP_Http::BAD_REQUEST ),
			),
			$response->get_data()
		);

		// Clean up the test environment.
		wp_set_current_user( 0 );
	}

	/**
	 * Test updating a fulfillment without items.
	 */
	public function test_update_fulfillment_without_items() {
		// Get a previously created order.
		$order_id = self::$created_order_ids[0];
		$request  = new WP_REST_Request( 'GET', '/wc/v3/orders/' . $order_id . '/fulfillments' );
		$response = $this->server->dispatch( $request );

		$fulfillments = $response->get_data();
		$this->assertIsArray( $fulfillments );
		$this->assertCount( 10, $fulfillments );

		$fulfillment_id = $fulfillments[0]['id'];

		// Update the fulfillment for the order with an invalid fulfillment ID.
		wp_set_current_user( 1 );
		$request = new WP_REST_Request( 'PUT', '/wc/v3/orders/' . $order_id . '/fulfillments/' . $fulfillment_id );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				array(
					'status'       => 'fulfilled',
					'is_fulfilled' => true,
					'meta_data'    => array(
						array(
							'id'    => 0,
							'key'   => 'test_meta_key',
							'value' => 'test_meta_value',
						),
						array(
							'id'    => 0,
							'key'   => 'test_meta_key_2',
							'value' => 'test_meta_value_2',
						),
					),
				)
			)
		);

		$response = $this->server->dispatch( $request );
		// Check the response. It should be an error saying that a fulfillment should contain at least one item.
		$this->assertEquals( WP_Http::BAD_REQUEST, $response->get_status() );
		$this->assertEquals(
			array(
				'code'    => 0,
				'message' => 'The fulfillment should contain at least one item.',
				'data'    => array( 'status' => WP_Http::BAD_REQUEST ),
			),
			$response->get_data()
		);

		// Clean up the test environment.
		wp_set_current_user( 0 );
	}

	/**
	 * Test updating a fulfillment with invalid items.
	 *
	 * @param array $items Invalid items to test.
	 *
	 * @dataProvider invalid_items_provider
	 */
	public function test_update_fulfillment_with_invalid_items( $items ) {
		// Get a previously created order.
		$order_id = self::$created_order_ids[0];
		$request  = new WP_REST_Request( 'GET', '/wc/v3/orders/' . $order_id . '/fulfillments' );
		$response = $this->server->dispatch( $request );

		$fulfillments = $response->get_data();
		$this->assertIsArray( $fulfillments );
		$this->assertCount( 10, $fulfillments );

		$fulfillment_id = $fulfillments[0]['id'];

		wp_set_current_user( 1 );

		// Update the fulfillment for the order with an invalid fulfillment ID.
		$request = new WP_REST_Request( 'PUT', '/wc/v3/orders/' . $order_id . '/fulfillments/' . $fulfillment_id );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				array(
					'status'       => 'fulfilled',
					'is_fulfilled' => true,
					'meta_data'    => array(
						array(
							'id'    => 0,
							'key'   => '_items',
							'value' => $items,
						),
					),
				)
			)
		);
		$response = $this->server->dispatch( $request );
		// Check the response. It should be an error saying that the item quantity is invalid.
		$this->assertEquals( WP_Http::BAD_REQUEST, $response->get_status() );
		$this->assertEquals(
			array(
				'code'    => 0,
				'message' => 'Invalid item.',
				'data'    => array( 'status' => WP_Http::BAD_REQUEST ),
			),
			$response->get_data()
		);
		// Clean up the test environment.
		wp_set_current_user( 0 );
	}

	/**
	 * Data provider for test_update_fulfillment_with_invalid_items.
	 *
	 * @return array
	 */
	public function invalid_items_provider() {
		return array(
			// Invalid item ID.
			array(
				array(
					array(
						'item_id' => 0,
						'qty'     => 2,
					),
					array(
						'item_id' => 2,
						'qty'     => 3,
					),
				),
			),
			// Invalid item quantity.
			array(
				array(
					array(
						'item_id' => 1,
						'qty'     => -2,
					),
					array(
						'item_id' => 2,
						'qty'     => 3,
					),
				),
			),
			// Invalid numeric format.
			array(
				array(
					array(
						'item_id' => '1',
						'qty'     => '2',
					),
					array(
						'item_id' => '2',
						'qty'     => '3',
					),
				),
			),
			// Invalid item format.
			array(
				array( 'invalid_item_format' ),
			),
		);
	}

	/**
	 * Test deleting a fulfillment for a regular user.
	 */
	public function test_delete_fulfillment_for_regular_user() {
		// Get a previously created order.
		$order_id = self::$created_order_ids[0];
		$request  = new WP_REST_Request( 'GET', '/wc/v3/orders/' . $order_id . '/fulfillments' );
		$response = $this->server->dispatch( $request );

		$fulfillments = $response->get_data();
		$this->assertIsArray( $fulfillments );
		$this->assertCount( 10, $fulfillments );

		$fulfillment_id = $fulfillments[0]['id'];

		wp_set_current_user( self::$created_user_id );

		// Delete the fulfillment for the order.
		$request  = new WP_REST_Request( 'DELETE', '/wc/v3/orders/' . $order_id . '/fulfillments/' . $fulfillment_id );
		$response = $this->server->dispatch( $request );

		// Check the response. It should be an error saying that a regular user cannot delete a fulfillment.
		$this->assertEquals( WP_Http::FORBIDDEN, $response->get_status() );
		$this->assertEquals(
			array(
				'code'    => 'woocommerce_rest_cannot_delete',
				'message' => 'Sorry, you cannot delete resources.',
				'data'    => array( 'status' => WP_Http::FORBIDDEN ),
			),
			$response->get_data()
		);
	}

	/**
	 * Test deleting a fulfillment for an admin user.
	 */
	public function test_delete_fulfillment_for_admin_user() {
		// Get a previously created order.
		$order_id = self::$created_order_ids[0];
		$request  = new WP_REST_Request( 'GET', '/wc/v3/orders/' . $order_id . '/fulfillments' );
		$response = $this->server->dispatch( $request );

		$fulfillments = $response->get_data();
		$this->assertIsArray( $fulfillments );
		$this->assertCount( 10, $fulfillments );

		$fulfillment_id = $fulfillments[0]['id'];

		wp_set_current_user( 1 );

		// Delete the fulfillment for the order.
		$request  = new WP_REST_Request( 'DELETE', '/wc/v3/orders/' . $order_id . '/fulfillments/' . $fulfillment_id );
		$response = $this->server->dispatch( $request );

		// Check the response. It should be ok.
		$this->assertEquals( WP_Http::OK, $response->get_status() );
	}

	/**
	 * Test deleting a fulfillment with an invalid order ID.
	 */
	public function test_delete_fulfillment_invalid_order_id() {
		// Get a previously created order.
		$order_id = self::$created_order_ids[0];
		$request  = new WP_REST_Request( 'GET', '/wc/v3/orders/' . $order_id . '/fulfillments' );
		$response = $this->server->dispatch( $request );

		$fulfillments = $response->get_data();
		$this->assertIsArray( $fulfillments );
		$this->assertCount( 10, $fulfillments );

		$fulfillment_id = $fulfillments[0]['id'];

		wp_set_current_user( 1 );

		// Delete the fulfillment for the order with an invalid order ID.
		$request  = new WP_REST_Request( 'DELETE', '/wc/v3/orders/999999/fulfillments/' . $fulfillment_id );
		$response = $this->server->dispatch( $request );

		// Check the response. It should be an error saying that the order ID is invalid.
		$this->assertEquals( WP_Http::NOT_FOUND, $response->get_status() );
		$this->assertEquals(
			array(
				'code'    => 'woocommerce_rest_order_invalid_id',
				'message' => 'Invalid order ID.',
				'data'    => array( 'status' => WP_Http::NOT_FOUND ),
			),
			$response->get_data()
		);
	}

	/**
	 * Test deleting a fulfillment with an invalid fulfillment ID.
	 */
	public function test_delete_fulfillment_invalid_fulfillment_id() {
		// Get a previously created order.
		$order_id = self::$created_order_ids[0];
		$request  = new WP_REST_Request( 'GET', '/wc/v3/orders/' . $order_id . '/fulfillments' );
		$response = $this->server->dispatch( $request );

		$fulfillments = $response->get_data();
		$this->assertIsArray( $fulfillments );
		$this->assertCount( 10, $fulfillments );

		wp_set_current_user( 1 );

		// Delete the fulfillment for the order with an invalid fulfillment ID.
		$request  = new WP_REST_Request( 'DELETE', '/wc/v3/orders/' . $order_id . '/fulfillments/999999' );
		$response = $this->server->dispatch( $request );

		// Check the response. It should be an error saying that the fulfillment ID is invalid.
		$this->assertEquals( WP_Http::BAD_REQUEST, $response->get_status() );
		$this->assertEquals(
			array(
				'code'    => 0,
				'message' => 'Fulfillment not found.',
				'data'    => array( 'status' => WP_Http::BAD_REQUEST ),
			),
			$response->get_data()
		);
	}

	/**
	 * Test deleting a fulfillment for a non-matching user.
	 */
	public function test_delete_fulfillment_invalid_user() {
		// Prepare the test environment.
		$current_user = wp_get_current_user();

		// Get a previously created order.
		$order_id = self::$created_order_ids[0];
		$request  = new WP_REST_Request( 'GET', '/wc/v3/orders/' . $order_id . '/fulfillments' );
		$response = $this->server->dispatch( $request );

		$fulfillments = $response->get_data();
		$this->assertIsArray( $fulfillments );
		$this->assertCount( 10, $fulfillments );

		$fulfillment_id = $fulfillments[0]['id'];

		wp_set_current_user( self::$created_user_id );

		// Delete the fulfillment for the order, with a different user.
		$request  = new WP_REST_Request( 'DELETE', '/wc/v3/orders/' . $order_id . '/fulfillments/' . $fulfillment_id );
		$response = $this->server->dispatch( $request );

		// Check the response. It should be an error saying that a regular user cannot delete a fulfillment.
		$this->assertEquals( WP_Http::FORBIDDEN, $response->get_status() );
		$this->assertEquals(
			array(
				'code'    => 'woocommerce_rest_cannot_delete',
				'message' => 'Sorry, you cannot delete resources.',
				'data'    => array( 'status' => WP_Http::FORBIDDEN ),
			),
			$response->get_data()
		);

		wp_set_current_user( $current_user->ID );
	}

	/**
	 * Test getting fulfillment meta data for a regular user.
	 */
	public function test_get_fulfillment_meta_data_for_regular_user() {
		// Get a previously created order.
		$order_id = self::$created_order_ids[0];
		$request  = new WP_REST_Request( 'GET', '/wc/v3/orders/' . $order_id . '/fulfillments' );
		$response = $this->server->dispatch( $request );

		$fulfillments = $response->get_data();
		$this->assertIsArray( $fulfillments );
		$this->assertCount( 10, $fulfillments );

		$fulfillment_id = $fulfillments[0]['id'];

		// Get the fulfillment meta data for the order.
		$request  = new WP_REST_Request( 'GET', '/wc/v3/orders/' . $order_id . '/fulfillments/' . $fulfillment_id . '/metadata' );
		$response = $this->server->dispatch( $request );

		// Check the response. It should be an error saying that a regular user cannot view a fulfillment.
		$this->assertEquals( WP_Http::OK, $response->get_status() );
		$this->assertEquals(
			array(
				array(
					'key'   => 'test_meta_key',
					'value' => 'test_meta_value',
				),
				array(
					'key'   => '_items',
					'value' =>
						array(
							array(
								'item_id' => 1,
								'qty'     => 2,
							),
							array(
								'item_id' => 2,
								'qty'     => 3,
							),
						),
				),
			),
			array_map(
				function ( $meta ) {
					unset( $meta['id'] );
					return $meta;
				},
				$response->get_data()
			)
		);
	}

	/**
	 * Test getting fulfillment meta data for an admin user.
	 */
	public function test_get_fulfillment_meta_data_for_admin_user() {
		// Get a previously created order.
		$order_id = self::$created_order_ids[0];
		$request  = new WP_REST_Request( 'GET', '/wc/v3/orders/' . $order_id . '/fulfillments' );
		$response = $this->server->dispatch( $request );

		$fulfillments = $response->get_data();
		$this->assertIsArray( $fulfillments );
		$this->assertCount( 10, $fulfillments );

		$fulfillment_id = $fulfillments[0]['id'];

		wp_set_current_user( 1 );

		// Get the fulfillment meta data for the order.
		$request  = new WP_REST_Request( 'GET', '/wc/v3/orders/' . $order_id . '/fulfillments/' . $fulfillment_id . '/metadata' );
		$response = $this->server->dispatch( $request );

		// Check the response. It should be ok.
		$this->assertEquals( WP_Http::OK, $response->get_status() );
		$this->assertEquals(
			array(
				array(
					'key'   => 'test_meta_key',
					'value' => 'test_meta_value',
				),
				array(
					'key'   => '_items',
					'value' => array(
						array(
							'item_id' => 1,
							'qty'     => 2,
						),
						array(
							'item_id' => 2,
							'qty'     => 3,
						),
					),
				),
			),
			array_map(
				function ( $meta ) {
					unset( $meta['id'] );
					return $meta;
				},
				$response->get_data()
			)
		);
	}

	/**
	 * Test getting fulfillment meta data with an invalid order ID.
	 */
	public function test_get_fulfillment_meta_data_invalid_order_id() {
		// Get a previously created order.
		$order_id = self::$created_order_ids[0];
		$request  = new WP_REST_Request( 'GET', '/wc/v3/orders/' . $order_id . '/fulfillments' );
		$response = $this->server->dispatch( $request );

		$fulfillments = $response->get_data();
		$this->assertIsArray( $fulfillments );
		$this->assertCount( 10, $fulfillments );

		$fulfillment_id = $fulfillments[0]['id'];

		wp_set_current_user( 1 );

		// Get the fulfillment meta data for the order with an invalid order ID.
		$request  = new WP_REST_Request( 'GET', '/wc/v3/orders/999999/fulfillments/' . $fulfillment_id . '/metadata' );
		$response = $this->server->dispatch( $request );

		// Check the response. It should be an error saying that the order ID is invalid.
		$this->assertEquals( WP_Http::NOT_FOUND, $response->get_status() );
		$this->assertEquals(
			array(
				'code'    => 'woocommerce_rest_order_invalid_id',
				'message' => 'Invalid order ID.',
				'data'    => array( 'status' => WP_Http::NOT_FOUND ),
			),
			$response->get_data()
		);
	}

	/**
	 * Test getting fulfillment meta data with an invalid fulfillment ID.
	 */
	public function test_get_fulfillment_meta_data_invalid_fulfillment_id() {
		// Get a previously created order.
		$order_id = self::$created_order_ids[0];
		$request  = new WP_REST_Request( 'GET', '/wc/v3/orders/' . $order_id . '/fulfillments' );
		$response = $this->server->dispatch( $request );

		$fulfillments = $response->get_data();
		$this->assertIsArray( $fulfillments );
		$this->assertCount( 10, $fulfillments );

		wp_set_current_user( 1 );

		// Get the fulfillment meta data for the order with an invalid fulfillment ID.
		$request  = new WP_REST_Request( 'GET', '/wc/v3/orders/' . $order_id . '/fulfillments/999999/metadata' );
		$response = $this->server->dispatch( $request );

		// Check the response. It should be an error saying that the fulfillment ID is invalid.
		$this->assertEquals( WP_Http::BAD_REQUEST, $response->get_status() );
		$this->assertEquals(
			array(
				'code'    => 0,
				'message' => 'Fulfillment not found.',
				'data'    => array( 'status' => WP_Http::BAD_REQUEST ),
			),
			$response->get_data()
		);
	}

	/**
	 * Test getting fulfillment meta data for a non-matching user.
	 */
	public function test_get_fulfillment_meta_data_invalid_user() {
		// Prepare the test environment.
		$current_user = wp_get_current_user();

		// Get a previously created order.
		$order_id = self::$created_order_ids[0];
		$request  = new WP_REST_Request( 'GET', '/wc/v3/orders/' . $order_id . '/fulfillments' );
		$response = $this->server->dispatch( $request );

		$fulfillments = $response->get_data();
		$this->assertIsArray( $fulfillments );
		$this->assertCount( 10, $fulfillments );

		$fulfillment_id = $fulfillments[0]['id'];

		wp_set_current_user( self::$created_user_id );

		// Get the fulfillment meta data for the order, with a different user.
		$request  = new WP_REST_Request( 'GET', '/wc/v3/orders/' . $order_id . '/fulfillments/' . $fulfillment_id . '/metadata' );
		$response = $this->server->dispatch( $request );

		// Check the response. It should be an error saying that a regular user cannot view a fulfillment.
		$this->assertEquals( WP_Http::FORBIDDEN, $response->get_status() );
		$this->assertEquals(
			array(
				'code'    => 'woocommerce_rest_cannot_view',
				'message' => 'Sorry, you cannot view resources.',
				'data'    => array( 'status' => WP_Http::FORBIDDEN ),
			),
			$response->get_data()
		);

		wp_set_current_user( $current_user->ID );
	}

	/**
	 * Test updating fulfillment meta data for a regular user.
	 */
	public function test_update_fulfillment_meta_data_for_regular_user() {
		// Get a previously created order.
		$order_id = self::$created_order_ids[1];
		$request  = new WP_REST_Request( 'GET', '/wc/v3/orders/' . $order_id . '/fulfillments' );
		$response = $this->server->dispatch( $request );

		$fulfillments = $response->get_data();
		$this->assertIsArray( $fulfillments );
		$this->assertCount( 10, $fulfillments );

		$fulfillment_id = $fulfillments[1]['id'];

		// Update the fulfillment meta data for the order.
		$request = new WP_REST_Request( 'PUT', '/wc/v3/orders/' . $order_id . '/fulfillments/' . $fulfillment_id . '/metadata' );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				array(
					'meta_data' => array(
						array(
							array(
								'id'    => 0,
								'key'   => 'test_meta_key',
								'value' => 'test_meta_value_updated',
							),
						),
					),
				)
			)
		);

		$response = $this->server->dispatch( $request );

		// Check the response. It should be an error saying that a regular user cannot update a fulfillment.
		$this->assertEquals( WP_Http::UNAUTHORIZED, $response->get_status() );
		$this->assertEquals(
			array(
				'code'    => 'rest_forbidden',
				'message' => 'Sorry, you are not allowed to do that.',
				'data'    => array( 'status' => WP_Http::UNAUTHORIZED ),
			),
			$response->get_data()
		);
	}

	/**
	 * Test updating fulfillment meta data for an admin user.
	 */
	public function test_update_fulfillment_meta_data_for_admin_user() {
		// Get a previously created order.
		$order_id = self::$created_order_ids[2];
		$request  = new WP_REST_Request( 'GET', '/wc/v3/orders/' . $order_id . '/fulfillments' );
		$response = $this->server->dispatch( $request );

		$fulfillments = $response->get_data();
		$this->assertIsArray( $fulfillments );
		$this->assertCount( 10, $fulfillments );

		$fulfillment_id = $fulfillments[2]['id'];

		wp_set_current_user( 1 );

		// Update the fulfillment meta data for the order.
		$request = new WP_REST_Request( 'PUT', '/wc/v3/orders/' . $order_id . '/fulfillments/' . $fulfillment_id . '/metadata' );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				array(
					'meta_data' => array(
						array(
							'id'    => 0,
							'key'   => 'test_meta_key',
							'value' => 'test_meta_value_updated',
						),
						array(
							'id'    => 0,
							'key'   => '_items',
							'value' => array(
								array(
									'item_id' => 1,
									'qty'     => 2,
								),
								array(
									'item_id' => 2,
									'qty'     => 3,
								),
							),
						),
					),
				)
			)
		);
		$response = $this->server->dispatch( $request );

		// Check the response. It should be ok.
		$this->assertEquals( WP_Http::OK, $response->get_status() );
		$this->assertEquals(
			array(
				array(
					'key'   => 'test_meta_key',
					'value' => 'test_meta_value_updated',
				),
				array(
					'key'   => '_items',
					'value' => array(
						array(
							'item_id' => 1,
							'qty'     => 2,
						),
						array(
							'item_id' => 2,
							'qty'     => 3,
						),
					),
				),
			),
			array_map(
				function ( $meta ) {
					unset( $meta['id'] );
					return $meta;
				},
				$response->get_data()
			)
		);

		// Clean up the test environment.
		wp_set_current_user( 0 );
	}

	/**
	 * Test updating fulfillment meta data with an invalid order ID.
	 */
	public function test_update_fulfillment_meta_data_invalid_order_id() {
		// Get a previously created order.
		$order_id = self::$created_order_ids[0];
		$request  = new WP_REST_Request( 'GET', '/wc/v3/orders/' . $order_id . '/fulfillments' );
		$response = $this->server->dispatch( $request );

		$fulfillments = $response->get_data();
		$this->assertIsArray( $fulfillments );
		$this->assertCount( 10, $fulfillments );

		$fulfillment_id = $fulfillments[0]['id'];

		wp_set_current_user( 1 );

		// Update the fulfillment meta data for the order with an invalid order ID.
		$request = new WP_REST_Request( 'PUT', '/wc/v3/orders/999999/fulfillments/' . $fulfillment_id . '/metadata' );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				array(
					'meta_data' => array(
						array(
							array(
								'id'    => 0,
								'key'   => 'test_meta_key',
								'value' => 'test_meta_value_updated',
							),
						),
					),
				)
			)
		);
		$response = $this->server->dispatch( $request );

		// Check the response. It should be an error saying that the order ID is invalid.
		$this->assertEquals( WP_Http::NOT_FOUND, $response->get_status() );
		$this->assertEquals(
			array(
				'code'    => 'woocommerce_rest_order_invalid_id',
				'message' => 'Invalid order ID.',
				'data'    => array( 'status' => WP_Http::NOT_FOUND ),
			),
			$response->get_data()
		);

		// Clean up the test environment.
		wp_set_current_user( 0 );
	}

	/**
	 * Test updating fulfillment meta data with an invalid fulfillment ID.
	 */
	public function test_update_fulfillment_meta_data_invalid_fulfillment_id() {
		// Get a previously created order.
		$order_id = self::$created_order_ids[0];
		$request  = new WP_REST_Request( 'GET', '/wc/v3/orders/' . $order_id . '/fulfillments' );
		$response = $this->server->dispatch( $request );

		$fulfillments = $response->get_data();
		$this->assertIsArray( $fulfillments );
		$this->assertCount( 10, $fulfillments );

		wp_set_current_user( 1 );

		// Update the fulfillment meta data for the order with an invalid fulfillment ID.
		$request = new WP_REST_Request( 'PUT', '/wc/v3/orders/' . $order_id . '/fulfillments/999999/metadata' );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				array(
					'meta_data' => array(
						array(
							array(
								'id'    => 0,
								'key'   => 'test_meta_key',
								'value' => 'test_meta_value_updated',
							),
						),
					),
				)
			)
		);
		$response = $this->server->dispatch( $request );

		// Check the response. It should be an error saying that the fulfillment ID is invalid.
		$this->assertEquals( WP_Http::BAD_REQUEST, $response->get_status() );
		$this->assertEquals(
			array(
				'code'    => 0,
				'message' => 'Fulfillment not found.',
				'data'    => array( 'status' => WP_Http::BAD_REQUEST ),
			),
			$response->get_data()
		);

		// Clean up the test environment.
		wp_set_current_user( 0 );
	}

	/**
	 * Test updating fulfillment meta data for a non-matching user.
	 */
	public function test_update_fulfillment_meta_data_invalid_user() {
		// Prepare the test environment.
		$current_user = wp_get_current_user();

		// Get a previously created order.
		$order_id = self::$created_order_ids[0];
		$request  = new WP_REST_Request( 'GET', '/wc/v3/orders/' . $order_id . '/fulfillments' );
		$response = $this->server->dispatch( $request );

		$fulfillments = $response->get_data();
		$this->assertIsArray( $fulfillments );
		$this->assertCount( 10, $fulfillments );

		$fulfillment_id = $fulfillments[0]['id'];

		wp_set_current_user( self::$created_user_id );

		// Update the fulfillment meta data for the order, with a different user.
		$request = new WP_REST_Request( 'PUT', '/wc/v3/orders/' . $order_id . '/fulfillments/' . $fulfillment_id . '/metadata' );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				array(
					'meta_data' => array(
						array(
							array(
								'id'    => 0,
								'key'   => 'test_meta_key',
								'value' => 'test_meta_value_updated',
							),
						),
					),
				)
			)
		);
		$response = $this->server->dispatch( $request );

		// Check the response. It should be an error saying that a regular user cannot update a fulfillment.
		$this->assertEquals( WP_Http::FORBIDDEN, $response->get_status() );
		$this->assertEquals(
			array(
				'code'    => 'rest_forbidden',
				'message' => 'Sorry, you are not allowed to do that.',
				'data'    => array( 'status' => WP_Http::FORBIDDEN ),
			),
			$response->get_data()
		);

		// Clean up the test environment.
		wp_set_current_user( $current_user->ID );
	}

	/**
	 * Test deleting fulfillment meta data for a regular user.
	 */
	public function test_delete_fulfillment_meta_data_for_regular_user() {
		// Get a previously created order.
		$order_id = self::$created_order_ids[4];
		$request  = new WP_REST_Request( 'GET', '/wc/v3/orders/' . $order_id . '/fulfillments' );
		$response = $this->server->dispatch( $request );

		$fulfillments = $response->get_data();
		$this->assertIsArray( $fulfillments );
		$this->assertCount( 10, $fulfillments );

		$fulfillment_id = $fulfillments[4]['id'];

		// Delete the fulfillment meta data for the order.
		$request = new WP_REST_Request( 'DELETE', '/wc/v3/orders/' . $order_id . '/fulfillments/' . $fulfillment_id . '/metadata' );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				array(
					'meta_key' => 'test_meta_key', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				),
			)
		);
		$response = $this->server->dispatch( $request );

		// Check the response. It should be an error saying that a regular user cannot delete a fulfillment.
		$this->assertEquals( WP_Http::UNAUTHORIZED, $response->get_status() );
		$this->assertEquals(
			array(
				'code'    => 'woocommerce_rest_cannot_delete',
				'message' => 'Sorry, you cannot delete resources.',
				'data'    => array( 'status' => WP_Http::UNAUTHORIZED ),
			),
			$response->get_data()
		);
	}

	/**
	 * Test deleting fulfillment meta data for an admin user.
	 */
	public function test_delete_fulfillment_meta_data_for_admin_user() {
		// Get a previously created order.
		$order_id = self::$created_order_ids[0];
		$request  = new WP_REST_Request( 'GET', '/wc/v3/orders/' . $order_id . '/fulfillments' );
		$response = $this->server->dispatch( $request );

		$fulfillments = $response->get_data();
		$this->assertIsArray( $fulfillments );
		$this->assertCount( 10, $fulfillments );

		$fulfillment_id = $fulfillments[0]['id'];

		wp_set_current_user( 1 );

		// Delete the fulfillment meta data for the order.
		$request = new WP_REST_Request( 'DELETE', '/wc/v3/orders/' . $order_id . '/fulfillments/' . $fulfillment_id . '/metadata' );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				array(
					'meta_key' => 'test_meta_key', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				),
			)
		);
		$response = $this->server->dispatch( $request );

		// Check the response. It should be ok.
		$this->assertEquals( WP_Http::OK, $response->get_status() );
		// Clean up the test environment.
		wp_set_current_user( 0 );
	}

	/**
	 * Test deleting fulfillment meta data with an invalid order ID.
	 */
	public function test_delete_fulfillment_meta_data_invalid_order_id() {
		// Get a previously created order.
		$order_id = self::$created_order_ids[0];
		$request  = new WP_REST_Request( 'GET', '/wc/v3/orders/' . $order_id . '/fulfillments' );
		$response = $this->server->dispatch( $request );

		$fulfillments = $response->get_data();
		$this->assertIsArray( $fulfillments );
		$this->assertCount( 10, $fulfillments );

		$fulfillment_id = $fulfillments[0]['id'];

		wp_set_current_user( 1 );

		// Delete the fulfillment meta data for the order with an invalid order ID.
		$request = new WP_REST_Request( 'DELETE', '/wc/v3/orders/999999/fulfillments/' . $fulfillment_id . '/metadata' );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				array(
					'meta_key' => 'test_meta_key', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				),
			)
		);
		$response = $this->server->dispatch( $request );

		// Check the response. It should be an error saying that the order ID is invalid.
		$this->assertEquals( WP_Http::NOT_FOUND, $response->get_status() );
		$this->assertEquals(
			array(
				'code'    => 'woocommerce_rest_order_invalid_id',
				'message' => 'Invalid order ID.',
				'data'    => array( 'status' => WP_Http::NOT_FOUND ),
			),
			$response->get_data()
		);
		// Clean up the test environment.
		wp_set_current_user( 0 );
	}

	/**
	 * Test deleting fulfillment meta data with an invalid fulfillment ID.
	 */
	public function test_delete_fulfillment_meta_data_invalid_fulfillment_id() {
		// Get a previously created order.
		$order_id = self::$created_order_ids[0];
		$request  = new WP_REST_Request( 'GET', '/wc/v3/orders/' . $order_id . '/fulfillments' );
		$response = $this->server->dispatch( $request );

		$fulfillments = $response->get_data();
		$this->assertIsArray( $fulfillments );
		$this->assertCount( 10, $fulfillments );

		wp_set_current_user( 1 );

		// Delete the fulfillment meta data for the order with an invalid fulfillment ID.
		$request = new WP_REST_Request( 'DELETE', '/wc/v3/orders/' . $order_id . '/fulfillments/999999/metadata' );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				array(
					'meta_key' => 'test_meta_key', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				),
			)
		);
		$response = $this->server->dispatch( $request );

		// Check the response. It should be an error saying that the fulfillment ID is invalid.
		$this->assertEquals( WP_Http::BAD_REQUEST, $response->get_status() );
		$this->assertEquals(
			array(
				'code'    => 0,
				'message' => 'Fulfillment not found.',
				'data'    => array( 'status' => WP_Http::BAD_REQUEST ),
			),
			$response->get_data()
		);
		// Clean up the test environment.
		wp_set_current_user( 0 );
	}

	/**
	 * Test deleting fulfillment meta data for a non-matching user.
	 */
	public function test_delete_fulfillment_meta_data_invalid_user() {
		// Prepare the test environment.
		$current_user = wp_get_current_user();

		// Get a previously created order.
		$order_id = self::$created_order_ids[0];
		$request  = new WP_REST_Request( 'GET', '/wc/v3/orders/' . $order_id . '/fulfillments' );
		$response = $this->server->dispatch( $request );

		$fulfillments = $response->get_data();
		$this->assertIsArray( $fulfillments );
		$this->assertCount( 10, $fulfillments );

		$fulfillment_id = $fulfillments[0]['id'];

		wp_set_current_user( self::$created_user_id );

		// Delete the fulfillment meta data for the order, with a different user.
		$request = new WP_REST_Request( 'DELETE', '/wc/v3/orders/' . $order_id . '/fulfillments/' . $fulfillment_id . '/metadata' );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				array(
					'meta_key' => 'test_meta_key', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				),
			)
		);
		$response = $this->server->dispatch( $request );

		// Check the response. It should be an error saying that a regular user cannot delete a fulfillment.
		$this->assertEquals( WP_Http::FORBIDDEN, $response->get_status() );
		$this->assertEquals(
			array(
				'code'    => 'woocommerce_rest_cannot_delete',
				'message' => 'Sorry, you cannot delete resources.',
				'data'    => array( 'status' => WP_Http::FORBIDDEN ),
			),
			$response->get_data()
		);

		wp_set_current_user( $current_user->ID );
	}
}
