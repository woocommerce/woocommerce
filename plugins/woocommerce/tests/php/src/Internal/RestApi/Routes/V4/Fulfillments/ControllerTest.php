<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\RestApi\Routes\V4\Fulfillments;

use Automattic\WooCommerce\Internal\Fulfillments\Fulfillment;
use Automattic\WooCommerce\Internal\Fulfillments\OrderFulfillmentsRestController;
use Automattic\WooCommerce\Internal\RestApi\Routes\V4\Fulfillments\Controller as FulfillmentsController;
use Automattic\WooCommerce\Internal\RestApi\Routes\V4\Fulfillments\Schema\FulfillmentSchema;
use Automattic\WooCommerce\Tests\Internal\Fulfillments\Helpers\FulfillmentsHelper;
use WC_REST_Unit_Test_Case;
use WC_Helper_Order;
use WC_Order;
use WP_REST_Request;

/**
 * Fulfillments Controller test class
 */
class ControllerTest extends WC_REST_Unit_Test_Case {

	/**
	 * Controller instance
	 *
	 * @var FulfillmentsController
	 */
	private FulfillmentsController $controller;

	/**
	 * Admin user for tests
	 *
	 * @var int
	 */
	private int $admin_user_id;

	/**
	 * Customer user for tests
	 *
	 * @var int
	 */
	private int $customer_user_id;

	/**
	 * Test order
	 *
	 * @var WC_Order
	 */
	private WC_Order $test_order;

	/**
	 * Test fulfillment
	 *
	 * @var Fulfillment
	 */
	private Fulfillment $test_fulfillment;

	/**
	 * Set up the test environment.
	 */
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		update_option( 'woocommerce_feature_fulfillments_enabled', 'yes' );
		$controller = wc_get_container()->get( \Automattic\WooCommerce\Internal\Fulfillments\FulfillmentsController::class );
		$controller->register();
		$controller->initialize_fulfillments();
	}

	/**
	 * Tear down the test environment.
	 */
	public static function tearDownAfterClass(): void {
		update_option( 'woocommerce_feature_fulfillments_enabled', 'no' );
		parent::tearDownAfterClass();
	}

	/**
	 * Setup test environment
	 */
	public function setUp(): void {
		parent::setUp();

		$this->controller = new FulfillmentsController();
		$this->controller->init( new FulfillmentSchema(), new OrderFulfillmentsRestController() );
		$this->controller->register_routes();

		$this->admin_user_id = $this->factory->user->create(
			array(
				'role' => 'administrator',
			)
		);

		$this->customer_user_id = $this->factory->user->create(
			array(
				'role' => 'customer',
			)
		);

		$this->test_order       = WC_Helper_Order::create_order( $this->customer_user_id );
		$this->test_fulfillment = FulfillmentsHelper::create_fulfillment(
			array(
				'entity_id' => $this->test_order->get_id(),
			)
		);
	}

	/**
	 * Teardown test environment
	 */
	public function tearDown(): void {
		// Delete the created users.
		wp_delete_user( $this->admin_user_id );
		wp_delete_user( $this->customer_user_id );

		// Delete the created orders and their fulfillments.
		WC_Helper_Order::delete_order( $this->test_order->get_id() );
		global $wpdb;
		$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}wc_order_fulfillments;" );
		$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}wc_order_fulfillment_meta;" );

		parent::tearDown();
	}

	/**
	 * Test route registration
	 */
	public function test_register_routes() {
		$routes = rest_get_server()->get_routes();

		$this->assertArrayHasKey( '/wc/v4/fulfillments', $routes );
		$this->assertArrayHasKey( '/wc/v4/fulfillments/(?P<fulfillment_id>[\d]+)', $routes );
	}

	/**
	 * Test get_fulfillments endpoint
	 */
	public function test_get_fulfillments_success() {
		wp_set_current_user( $this->admin_user_id );

		$request = new WP_REST_Request( 'GET', '/wc/v4/fulfillments' );
		$request->set_param( 'order_id', $this->test_order->get_id() );

		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$this->assertIsArray( $response->get_data() );
	}

	/**
	 * Test get_fulfillments without order_id
	 */
	public function test_get_fulfillments_missing_order_id() {
		wp_set_current_user( $this->admin_user_id );

		$request = new WP_REST_Request( 'GET', '/wc/v4/fulfillments' );

		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 400, $response->get_status() );
		$data = $response->get_data();
		$this->assertEquals( 'rest_missing_callback_param', $data['code'] );
	}

	/**
	 * Test get_fulfillments with invalid order_id
	 */
	public function test_get_fulfillments_invalid_order_id() {
		wp_set_current_user( $this->admin_user_id );

		$request = new WP_REST_Request( 'GET', '/wc/v4/fulfillments' );
		$request->set_param( 'order_id', 99999 );

		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 400, $response->get_status() );
	}

	/**
	 * Test create_fulfillment endpoint
	 */
	public function test_create_fulfillment_success() {
		wp_set_current_user( $this->admin_user_id );

		$request = new WP_REST_Request( 'POST', '/wc/v4/fulfillments' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( $this->get_test_fulfillment_data() ) );

		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 201, $response->get_status(), wp_json_encode( $response->get_data() ) );
	}

	/**
	 * Test create_fulfillment without entity_id
	 */
	public function test_create_fulfillment_missing_entity_id() {
		wp_set_current_user( $this->admin_user_id );
		$test_data = $this->get_test_fulfillment_data();
		unset( $test_data['entity_id'] );

		$request = new WP_REST_Request( 'POST', '/wc/v4/fulfillments' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( $test_data ) );

		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 400, $response->get_status() );
		$data = $response->get_data();
		$this->assertEquals( 'rest_missing_callback_param', $data['code'] );
	}

	/**
	 * Test create_fulfillment with invalid entity_type
	 */
	public function test_create_fulfillment_invalid_entity_type() {
		wp_set_current_user( $this->admin_user_id );

		$request = new WP_REST_Request( 'POST', '/wc/v4/fulfillments' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( $this->get_test_fulfillment_data( array( 'entity_type' => 'invalid' ) ) ) );

		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 400, $response->get_status() );
		$data = $response->get_data();
		$this->assertEquals( 'woocommerce_rest_invalid_entity_type', $data['code'] );
	}

	/**
	 * Test get_fulfillment endpoint
	 */
	public function test_get_fulfillment_success() {
		wp_set_current_user( $this->admin_user_id );

		$request = new WP_REST_Request( 'GET', '/wc/v4/fulfillments/' . $this->test_fulfillment->get_id() );

		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertEquals( $this->test_fulfillment->get_id(), $data['id'] );
	}

	/**
	 * Test get_fulfillment with invalid ID
	 */
	public function test_get_fulfillment_invalid_id() {
		wp_set_current_user( $this->admin_user_id );

		$request = new WP_REST_Request( 'GET', '/wc/v4/fulfillments/99999' );

		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 400, $response->get_status() );
		$data = $response->get_data();
		$this->assertEquals( 'woocommerce_rest_fulfillment_invalid_id', $data['code'] );
	}

	/**
	 * Test update_fulfillment endpoint
	 */
	public function test_update_fulfillment_success() {
		wp_set_current_user( $this->admin_user_id );

		$request = new WP_REST_Request( 'PUT', '/wc/v4/fulfillments/' . $this->test_fulfillment->get_id() );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( $this->get_test_fulfillment_data() ) );

		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
	}

	/**
	 * Test update_fulfillment with invalid ID
	 */
	public function test_update_fulfillment_invalid_id() {
		wp_set_current_user( $this->admin_user_id );

		$request = new WP_REST_Request( 'PUT', '/wc/v4/fulfillments/99999' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( $this->get_test_fulfillment_data() ) );

		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 400, $response->get_status() );
		$data = $response->get_data();
		$this->assertEquals( 'woocommerce_rest_fulfillment_invalid_id', $data['code'] );
	}

	/**
	 * Test delete_fulfillment endpoint
	 */
	public function test_delete_fulfillment_success() {
		wp_set_current_user( $this->admin_user_id );

		$fulfillment = FulfillmentsHelper::create_fulfillment(
			array( 'entity_id' => $this->test_order->get_id() )
		);

		$request  = new WP_REST_Request( 'DELETE', '/wc/v4/fulfillments/' . $fulfillment->get_id() );
		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );

		// Verify the fulfillment is deleted.
		$get_request  = new WP_REST_Request( 'GET', '/wc/v4/fulfillments/' . $fulfillment->get_id() );
		$get_response = rest_get_server()->dispatch( $get_request );
		$this->assertEquals( 400, $get_response->get_status() );
	}

	/**
	 * Test delete_fulfillment with invalid ID
	 */
	public function test_delete_fulfillment_invalid_id() {
		wp_set_current_user( $this->admin_user_id );

		$request = new WP_REST_Request( 'DELETE', '/wc/v4/fulfillments/99999' );

		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 400, $response->get_status() );
		$data = $response->get_data();
		$this->assertEquals( 'woocommerce_rest_fulfillment_invalid_id', $data['code'] );
	}

	/**
	 * Test permission check - admin user
	 */
	public function test_permission_check_admin() {
		wp_set_current_user( $this->admin_user_id );

		$request = new WP_REST_Request( 'GET', '/wc/v4/fulfillments' );
		$request->set_param( 'order_id', $this->test_order->get_id() );

		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
	}

	/**
	 * Test permission check - customer reading their own order
	 */
	public function test_permission_check_customer_own_order() {
		wp_set_current_user( $this->customer_user_id );

		$request = new WP_REST_Request( 'GET', '/wc/v4/fulfillments' );
		$request->set_param( 'order_id', $this->test_order->get_id() );

		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
	}

	/**
	 * Test permission check - customer trying to create fulfillment
	 */
	public function test_permission_check_customer_create() {
		wp_set_current_user( $this->customer_user_id );

		$request = new WP_REST_Request( 'POST', '/wc/v4/fulfillments' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( $this->get_test_fulfillment_data() ) );

		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 403, $response->get_status() );
	}

	/**
	 * Test permission check - unauthorized user
	 */
	public function test_permission_check_unauthorized() {
		wp_set_current_user( 0 );

		$request = new WP_REST_Request( 'GET', '/wc/v4/fulfillments' );
		$request->set_param( 'order_id', $this->test_order->get_id() );

		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test permission check - customer accessing other's order
	 */
	public function test_permission_check_customer_other_order() {
		$other_order = WC_Helper_Order::create_order();
		wp_set_current_user( $this->customer_user_id );

		$request = new WP_REST_Request( 'GET', '/wc/v4/fulfillments' );
		$request->set_param( 'order_id', $other_order->get_id() );

		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 403, $response->get_status() );
	}

	/**
	 * Test schema validation for get fulfillments
	 */
	public function test_get_fulfillments_schema() {
		wp_set_current_user( $this->admin_user_id );

		$request  = new WP_REST_Request( 'OPTIONS', '/wc/v4/fulfillments' );
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertArrayHasKey( 'endpoints', $data );
		$get_endpoint = array_filter(
			$data['endpoints'],
			function ( $endpoint ) {
				return in_array( 'GET', $endpoint['methods'], true );
			}
		);
		$this->assertNotEmpty( $get_endpoint );
		$this->assertIsArray( $get_endpoint[0] );
		$this->assertArrayHasKey( 'args', $get_endpoint[0] );
		$this->assertArrayHasKey( 'order_id', $get_endpoint[0]['args'] );
	}

	/**
	 * Test schema validation for create fulfillment
	 */
	public function test_create_fulfillment_schema() {
		wp_set_current_user( $this->admin_user_id );

		$request  = new WP_REST_Request( 'OPTIONS', '/wc/v4/fulfillments' );
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertArrayHasKey( 'endpoints', $data );
		$post_endpoint = array_filter(
			$data['endpoints'],
			function ( $endpoint ) {
				return in_array( 'POST', $endpoint['methods'], true );
			}
		);
		$this->assertIsArray( $post_endpoint );
		$this->assertNotEmpty( $post_endpoint );
		$post_endpoint = reset( $post_endpoint );
		$this->assertArrayHasKey( 'args', $post_endpoint );
		$this->assertArrayHasKey( 'entity_type', $post_endpoint['args'] );
	}

	/**
	 * Test error response format
	 */
	public function test_error_response_format() {
		wp_set_current_user( $this->admin_user_id );

		$request = new WP_REST_Request( 'GET', '/wc/v4/fulfillments' );
		$request->set_param( 'order_id', 0 );

		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 400, $response->get_status() );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'code', $data );
		$this->assertArrayHasKey( 'message', $data );
		$this->assertArrayHasKey( 'data', $data );
		$this->assertArrayHasKey( 'status', $data['data'] );
	}

	/**
	 * Test authentication error messages
	 */
	public function test_authentication_error_messages() {
		wp_set_current_user( 0 );

		// Test GET error.
		$request = new WP_REST_Request( 'GET', '/wc/v4/fulfillments' );
		$request->set_param( 'order_id', $this->test_order->get_id() );
		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 401, $response->get_status() );

		// Test POST error.
		$request = new WP_REST_Request( 'POST', '/wc/v4/fulfillments' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( $this->get_test_fulfillment_data() ) );
		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 401, $response->get_status() );

		// Test DELETE error.
		$request  = new WP_REST_Request( 'DELETE', '/wc/v4/fulfillments/' . $this->test_fulfillment->get_id() );
		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Helper to get test fulfillment data
	 *
	 * @param array $overrides Key-value pairs to override default data.
	 *
	 * @return array
	 */
	private function get_test_fulfillment_data( array $overrides = array() ): array {
		$items = $this->test_order->get_items();

		return array_merge(
			array(
				'entity_id'   => (string) $this->test_order->get_id(),
				'entity_type' => WC_Order::class,
				'status'      => 'fulfilled',
				'meta_data'   => array(
					array(
						'id'    => 0,
						'key'   => '_tracking_number',
						'value' => 'TEST123456',
					),
					array(
						'id'    => 0,
						'key'   => '_tracking_provider',
						'value' => 'Test Carrier',
					),
					array(
						'id'    => 0,
						'key'   => '_items',
						'value' => array(
							array(
								'item_id' => reset( $items )->get_id(),
								'qty'     => 1,
							),
						),
					),
				),
			),
			$overrides
		);
	}
}
