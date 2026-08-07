<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\RestApi\Routes\V4\Fulfillments;

use Automattic\WooCommerce\Admin\Features\Fulfillments\Fulfillment;
use Automattic\WooCommerce\Admin\Features\Fulfillments\OrderFulfillmentsRestController;
use Automattic\WooCommerce\Internal\RestApi\Routes\V4\Fulfillments\Controller as FulfillmentsController;
use Automattic\WooCommerce\Internal\RestApi\Routes\V4\Fulfillments\Schema\FulfillmentSchema;
use Automattic\WooCommerce\Tests\Admin\Features\Fulfillments\Helpers\FulfillmentsHelper;
use WC_Helper_Order;
use WC_Order;
use WC_Unit_Test_Case;
use WP_REST_Request;

/**
 * Fulfillments Controller test class
 */
class ControllerTest extends WC_Unit_Test_Case {

	/**
	 * Controller instance
	 *
	 * @var FulfillmentsController
	 */
	private FulfillmentsController $controller;

	/**
	 * Original value of the fulfillments feature flag.
	 *
	 * @var mixed
	 */
	private static $original_fulfillments_flag;

	/**
	 * Admin user for tests, shared across all tests in the class for REST authentication.
	 *
	 * @var int
	 */
	private static int $admin_user_id;

	/**
	 * Customer user for tests, shared across all tests in the class.
	 *
	 * @var int
	 */
	private static int $customer_user_id;

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
		self::$original_fulfillments_flag = get_option( 'woocommerce_feature_fulfillments_enabled' );
		update_option( 'woocommerce_feature_fulfillments_enabled', 'yes' );
		$controller = wc_get_container()->get( \Automattic\WooCommerce\Admin\Features\Fulfillments\FulfillmentsController::class );
		$controller->register();
		$controller->initialize_fulfillments();
	}

	/**
	 * Tear down the test environment.
	 */
	public static function tearDownAfterClass(): void {
		if ( false === self::$original_fulfillments_flag ) {
			delete_option( 'woocommerce_feature_fulfillments_enabled' );
		} else {
			update_option( 'woocommerce_feature_fulfillments_enabled', self::$original_fulfillments_flag );
		}
		parent::tearDownAfterClass();
	}

	/**
	 * Create the shared users once for the whole class.
	 *
	 * @param object $factory Factory object.
	 */
	public static function wpSetUpBeforeClass( $factory ) {
		self::$admin_user_id = $factory->user->create(
			array(
				'role' => 'administrator',
			)
		);

		self::$customer_user_id = $factory->user->create(
			array(
				'role' => 'customer',
			)
		);
	}

	/**
	 * Setup test environment
	 */
	public function setUp(): void {
		parent::setUp();

		$this->controller = new FulfillmentsController();
		$this->controller->init( new FulfillmentSchema(), new OrderFulfillmentsRestController() );
		$this->create_rest_server_with_routes(
			array( array( $this->controller, 'register_routes' ) ),
			true
		);

		$this->test_order       = WC_Helper_Order::create_order( self::$customer_user_id );
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
		$this->clear_rest_server();
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
		wp_set_current_user( self::$admin_user_id );

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
		wp_set_current_user( self::$admin_user_id );

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
		wp_set_current_user( self::$admin_user_id );

		$request = new WP_REST_Request( 'GET', '/wc/v4/fulfillments' );
		$request->set_param( 'order_id', 99999 );

		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 400, $response->get_status() );
	}

	/**
	 * Test create_fulfillment endpoint
	 */
	public function test_create_fulfillment_success() {
		wp_set_current_user( self::$admin_user_id );

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
		wp_set_current_user( self::$admin_user_id );
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
		wp_set_current_user( self::$admin_user_id );

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
		wp_set_current_user( self::$admin_user_id );

		$request = new WP_REST_Request( 'GET', '/wc/v4/fulfillments/' . $this->test_fulfillment->get_id() );

		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertEquals( $this->test_fulfillment->get_id(), $data['id'] );
	}

	/**
	 * V4 must format `_date_fulfilled` meta as ISO 8601 with 'Z' suffix in
	 * responses, matching V3 and the storage UTC contract — clients should not
	 * see the raw 'Y-m-d H:i:s' form.
	 */
	public function test_get_fulfillment_formats_date_fulfilled_meta_as_iso8601() {
		wp_set_current_user( self::$admin_user_id );

		$this->test_fulfillment->set_date_fulfilled( '2025-01-15T10:30:00Z' );
		$this->test_fulfillment->save();

		$request  = new WP_REST_Request( 'GET', '/wc/v4/fulfillments/' . $this->test_fulfillment->get_id() );
		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );

		$data           = $response->get_data();
		$date_fulfilled = null;
		foreach ( $data['meta_data'] as $meta ) {
			if ( '_date_fulfilled' === $meta['key'] ) {
				$date_fulfilled = $meta['value'];
				break;
			}
		}
		$this->assertSame( '2025-01-15T10:30:00Z', $date_fulfilled );
	}

	/**
	 * Test get_fulfillment with invalid ID
	 */
	public function test_get_fulfillment_invalid_id() {
		wp_set_current_user( self::$admin_user_id );

		$request = new WP_REST_Request( 'GET', '/wc/v4/fulfillments/99999' );

		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 404, $response->get_status() );
		$data = $response->get_data();
		$this->assertEquals( 'woocommerce_rest_fulfillment_invalid_id', $data['code'] );
	}

	/**
	 * Test update_fulfillment endpoint
	 */
	public function test_update_fulfillment_success() {
		wp_set_current_user( self::$admin_user_id );

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
		wp_set_current_user( self::$admin_user_id );

		$request = new WP_REST_Request( 'PUT', '/wc/v4/fulfillments/99999' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( $this->get_test_fulfillment_data() ) );

		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 404, $response->get_status() );
		$data = $response->get_data();
		$this->assertEquals( 'woocommerce_rest_fulfillment_invalid_id', $data['code'] );
	}

	/**
	 * Test delete_fulfillment endpoint
	 */
	public function test_delete_fulfillment_success() {
		wp_set_current_user( self::$admin_user_id );

		$fulfillment = FulfillmentsHelper::create_fulfillment(
			array( 'entity_id' => $this->test_order->get_id() )
		);

		$request  = new WP_REST_Request( 'DELETE', '/wc/v4/fulfillments/' . $fulfillment->get_id() );
		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );

		// Verify the fulfillment is deleted. Deleted fulfillments are soft-deleted, so the
		// permission lookup still resolves them and the handler reports the deletion as 400.
		$get_request  = new WP_REST_Request( 'GET', '/wc/v4/fulfillments/' . $fulfillment->get_id() );
		$get_response = rest_get_server()->dispatch( $get_request );
		$this->assertEquals( 400, $get_response->get_status() );
	}

	/**
	 * Test delete_fulfillment with invalid ID
	 */
	public function test_delete_fulfillment_invalid_id() {
		wp_set_current_user( self::$admin_user_id );

		$request = new WP_REST_Request( 'DELETE', '/wc/v4/fulfillments/99999' );

		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 404, $response->get_status() );
		$data = $response->get_data();
		$this->assertEquals( 'woocommerce_rest_fulfillment_invalid_id', $data['code'] );
	}

	/**
	 * Test permission check - admin user
	 */
	public function test_permission_check_admin() {
		wp_set_current_user( self::$admin_user_id );

		$request = new WP_REST_Request( 'GET', '/wc/v4/fulfillments' );
		$request->set_param( 'order_id', $this->test_order->get_id() );

		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
	}

	/**
	 * Test permission check - customer reading their own order
	 */
	public function test_permission_check_customer_own_order() {
		wp_set_current_user( self::$customer_user_id );

		$request = new WP_REST_Request( 'GET', '/wc/v4/fulfillments' );
		$request->set_param( 'order_id', $this->test_order->get_id() );

		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
	}

	/**
	 * Test permission check - customer trying to create fulfillment
	 */
	public function test_permission_check_customer_create() {
		wp_set_current_user( self::$customer_user_id );

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
	 * Regression test for WOO13-227: an unauthenticated request must not be
	 * able to read a guest order's fulfillments. The owner-read check compares
	 * get_current_user_id() to $order->get_customer_id(); for guest orders
	 * both are 0, so the check needs a `get_current_user_id() > 0` guard.
	 */
	public function test_permission_check_unauthenticated_cannot_read_guest_order_fulfillments() {
		$guest_order       = WC_Helper_Order::create_order( 0 );
		$guest_fulfillment = FulfillmentsHelper::create_fulfillment(
			array( 'entity_id' => $guest_order->get_id() )
		);

		wp_set_current_user( 0 );

		$list_request = new WP_REST_Request( 'GET', '/wc/v4/fulfillments' );
		$list_request->set_param( 'order_id', $guest_order->get_id() );
		$list_response = rest_get_server()->dispatch( $list_request );
		$this->assertSame( 401, $list_response->get_status() );

		$get_request  = new WP_REST_Request( 'GET', '/wc/v4/fulfillments/' . $guest_fulfillment->get_id() );
		$get_response = rest_get_server()->dispatch( $get_request );
		$this->assertSame( 401, $get_response->get_status() );

		WC_Helper_Order::delete_order( $guest_order->get_id() );
	}

	/**
	 * Test permission check - customer accessing other's order
	 */
	public function test_permission_check_customer_other_order() {
		$other_order = WC_Helper_Order::create_order();
		wp_set_current_user( self::$customer_user_id );

		$request = new WP_REST_Request( 'GET', '/wc/v4/fulfillments' );
		$request->set_param( 'order_id', $other_order->get_id() );

		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 403, $response->get_status() );
	}

	/**
	 * Regression test: on the single-item route a customer must not be able to read another
	 * order's fulfillment by passing an order they own as the `order_id` query parameter. The
	 * order authorized against is derived from the fulfillment itself, not from the request
	 * `order_id`, so the non-owner is rejected and no fulfillment data is returned.
	 */
	public function test_permission_check_customer_cannot_read_other_orders_fulfillment_via_spoofed_order_id() {
		$attacker_user_id = $this->factory->user->create( array( 'role' => 'customer' ) );
		$attacker_order   = WC_Helper_Order::create_order( $attacker_user_id );

		wp_set_current_user( $attacker_user_id );

		// Request the victim's fulfillment while passing an order the attacker owns.
		$request = new WP_REST_Request( 'GET', '/wc/v4/fulfillments/' . $this->test_fulfillment->get_id() );
		$request->set_param( 'order_id', $attacker_order->get_id() );
		$response = rest_get_server()->dispatch( $request );

		$this->assertSame( 404, $response->get_status() );
		$data = $response->get_data();
		$this->assertArrayHasKey( 'code', $data );
		$this->assertArrayNotHasKey( 'id', $data );

		// Without the spoofed order_id the non-owner is likewise rejected.
		$control_request  = new WP_REST_Request( 'GET', '/wc/v4/fulfillments/' . $this->test_fulfillment->get_id() );
		$control_response = rest_get_server()->dispatch( $control_request );
		$this->assertSame( 404, $control_response->get_status() );

		WC_Helper_Order::delete_order( $attacker_order->get_id() );
		wp_delete_user( $attacker_user_id );
	}

	/**
	 * A fulfillment the caller cannot read must be indistinguishable from one that does not
	 * exist, otherwise the status code alone tells an attacker which IDs are real.
	 */
	public function test_permission_check_unreadable_fulfillment_is_reported_as_not_found() {
		$attacker_user_id = $this->factory->user->create( array( 'role' => 'customer' ) );
		wp_set_current_user( $attacker_user_id );

		$existing_request  = new WP_REST_Request( 'GET', '/wc/v4/fulfillments/' . $this->test_fulfillment->get_id() );
		$existing_response = rest_get_server()->dispatch( $existing_request );

		$missing_request  = new WP_REST_Request( 'GET', '/wc/v4/fulfillments/99999' );
		$missing_response = rest_get_server()->dispatch( $missing_request );

		$this->assertSame( $missing_response->get_status(), $existing_response->get_status() );
		$this->assertSame( $missing_response->get_data()['code'], $existing_response->get_data()['code'] );
		$this->assertSame( 'woocommerce_rest_fulfillment_invalid_id', $existing_response->get_data()['code'] );

		wp_delete_user( $attacker_user_id );
	}

	/**
	 * The masking above must not swallow the real reason when the caller can read the order:
	 * an owner refused a write needs to know the write was refused, not that their own
	 * fulfillment disappeared.
	 */
	public function test_permission_check_owner_write_refusal_is_not_masked_as_not_found() {
		wp_set_current_user( self::$customer_user_id );

		$request  = new WP_REST_Request( 'DELETE', '/wc/v4/fulfillments/' . $this->test_fulfillment->get_id() );
		$response = rest_get_server()->dispatch( $request );

		$this->assertSame( 403, $response->get_status() );
		$this->assertSame( 'woocommerce_rest_api_v4_fulfillments_cannot_delete', $response->get_data()['code'] );
	}

	/**
	 * Regression test: WP_REST_Request ranks query string arguments above URL placeholders, so a
	 * handler reading fulfillment_id with get_param() would act on a different fulfillment than
	 * the one the permission callback authorized. The handler must follow the path, which means a
	 * caller asking for their own fulfillment while naming someone else's in the query string
	 * gets back their own.
	 */
	public function test_customer_cannot_read_other_orders_fulfillment_via_query_fulfillment_id() {
		$attacker_user_id     = $this->factory->user->create( array( 'role' => 'customer' ) );
		$attacker_order       = WC_Helper_Order::create_order( $attacker_user_id );
		$attacker_fulfillment = FulfillmentsHelper::create_fulfillment(
			array( 'entity_id' => $attacker_order->get_id() )
		);

		wp_set_current_user( $attacker_user_id );

		// The path addresses the attacker's own fulfillment; the query string asks for the victim's.
		$request = new WP_REST_Request( 'GET', '/wc/v4/fulfillments/' . $attacker_fulfillment->get_id() );
		$request->set_param( 'fulfillment_id', $this->test_fulfillment->get_id() );
		$response = rest_get_server()->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertSame( $attacker_fulfillment->get_id(), $data['id'] );
		$this->assertSame( (string) $attacker_order->get_id(), $data['entity_id'] );

		WC_Helper_Order::delete_order( $attacker_order->get_id() );
		wp_delete_user( $attacker_user_id );
	}

	/**
	 * The same query string argument must not redirect an update: a PUT addressed at one
	 * fulfillment must not write to the one named in the query string.
	 */
	public function test_update_fulfillment_ignores_query_fulfillment_id() {
		wp_set_current_user( self::$admin_user_id );

		$target = FulfillmentsHelper::create_fulfillment(
			array( 'entity_id' => $this->test_order->get_id() )
		);

		$request = new WP_REST_Request( 'PUT', '/wc/v4/fulfillments/' . $target->get_id() );
		$request->set_param( 'fulfillment_id', $this->test_fulfillment->get_id() );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( $this->get_test_fulfillment_data() ) );
		$response = rest_get_server()->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( $target->get_id(), $response->get_data()['id'] );

		// The fulfillment named in the query string keeps its original status.
		$bystander = new Fulfillment( $this->test_fulfillment->get_id() );
		$this->assertSame( 'unfulfilled', $bystander->get_status() );
	}

	/**
	 * A JSON request body is the highest priority source in
	 * WP_REST_Request::get_parameter_order(), so it outranks both the query string and the URL
	 * placeholder. A fulfillment_id in the body must not move the request off the addressed
	 * fulfillment either.
	 */
	public function test_update_fulfillment_ignores_body_fulfillment_id() {
		wp_set_current_user( self::$admin_user_id );

		$target = FulfillmentsHelper::create_fulfillment(
			array( 'entity_id' => $this->test_order->get_id() )
		);

		$request = new WP_REST_Request( 'PUT', '/wc/v4/fulfillments/' . $target->get_id() );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				$this->get_test_fulfillment_data(
					array( 'fulfillment_id' => $this->test_fulfillment->get_id() )
				)
			)
		);
		$response = rest_get_server()->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( $target->get_id(), $response->get_data()['id'] );

		$bystander = new Fulfillment( $this->test_fulfillment->get_id() );
		$this->assertSame( 'unfulfilled', $bystander->get_status() );
	}

	/**
	 * The delegate feeds the whole request body into Fulfillment::set_props(), where entity_type
	 * and entity_id are settable. Both are pinned to the stored fulfillment so an update cannot
	 * move it to another order or leave it with an entity type that resolves to nothing.
	 */
	public function test_update_fulfillment_ignores_body_entity_fields() {
		wp_set_current_user( self::$admin_user_id );

		$other_order = WC_Helper_Order::create_order( self::$customer_user_id );

		$request = new WP_REST_Request( 'PUT', '/wc/v4/fulfillments/' . $this->test_fulfillment->get_id() );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				$this->get_test_fulfillment_data(
					array(
						'entity_id'   => (string) $other_order->get_id(),
						'entity_type' => 'Not\\An\\Entity',
					)
				)
			)
		);
		$response = rest_get_server()->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );

		$stored = new Fulfillment( $this->test_fulfillment->get_id() );
		$this->assertSame( WC_Order::class, $stored->get_entity_type() );
		$this->assertSame( (string) $this->test_order->get_id(), $stored->get_entity_id() );

		WC_Helper_Order::delete_order( $other_order->get_id() );
	}

	/**
	 * id is a settable prop too, and it decides which row save() writes to. Left unpinned, an id
	 * in the request body would make the update land on that row instead, overwriting it with the
	 * addressed fulfillment's values.
	 */
	public function test_update_fulfillment_ignores_body_id() {
		wp_set_current_user( self::$admin_user_id );

		$other_order = WC_Helper_Order::create_order( self::$customer_user_id );
		$bystander   = FulfillmentsHelper::create_fulfillment(
			array( 'entity_id' => (string) $other_order->get_id() )
		);

		$request = new WP_REST_Request( 'PUT', '/wc/v4/fulfillments/' . $this->test_fulfillment->get_id() );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				$this->get_test_fulfillment_data(
					array(
						'id'     => $bystander->get_id(),
						'status' => 'fulfilled',
					)
				)
			)
		);
		$response = rest_get_server()->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( $this->test_fulfillment->get_id(), $response->get_data()['id'] );

		$stored = new Fulfillment( $bystander->get_id() );
		$this->assertSame( 'unfulfilled', $stored->get_status() );
		$this->assertSame( (string) $other_order->get_id(), $stored->get_entity_id() );

		WC_Helper_Order::delete_order( $other_order->get_id() );
	}

	/**
	 * Fulfillments can be attached to entities other than orders. The single-item routes only
	 * serve order fulfillments, so anything else is rejected before the order lookup rather than
	 * authorized against an order ID that means nothing in that entity's namespace.
	 */
	public function test_permission_check_rejects_non_order_fulfillment() {
		wp_set_current_user( self::$admin_user_id );

		$foreign_fulfillment = FulfillmentsHelper::create_fulfillment(
			array(
				'entity_type' => 'Some\\Other\\Entity',
				'entity_id'   => (string) $this->test_order->get_id(),
			)
		);

		$request  = new WP_REST_Request( 'GET', '/wc/v4/fulfillments/' . $foreign_fulfillment->get_id() );
		$response = rest_get_server()->dispatch( $request );

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'woocommerce_rest_invalid_entity_type', $response->get_data()['code'] );
	}

	/**
	 * Deleting an order normally takes its fulfillments with it, but a row can still point at an
	 * order that is no longer there. With no order to authorize against, the request is refused
	 * rather than falling through to the handler.
	 */
	public function test_permission_check_fulfillment_whose_order_is_missing() {
		wp_set_current_user( self::$admin_user_id );

		// Delete first, so the fulfillment is created against an ID that is already gone.
		$doomed_order     = WC_Helper_Order::create_order( self::$customer_user_id );
		$missing_order_id = $doomed_order->get_id();
		WC_Helper_Order::delete_order( $missing_order_id );

		$orphan = FulfillmentsHelper::create_fulfillment(
			array( 'entity_id' => (string) $missing_order_id )
		);

		$request  = new WP_REST_Request( 'GET', '/wc/v4/fulfillments/' . $orphan->get_id() );
		$response = rest_get_server()->dispatch( $request );

		$this->assertSame( 404, $response->get_status() );
		$this->assertSame( 'woocommerce_rest_order_invalid_id', $response->get_data()['code'] );
	}

	/**
	 * The same query string argument must not redirect a write either: a delete addressed at one
	 * fulfillment must not remove the one named in the query string.
	 */
	public function test_delete_fulfillment_ignores_query_fulfillment_id() {
		wp_set_current_user( self::$admin_user_id );

		$target = FulfillmentsHelper::create_fulfillment(
			array( 'entity_id' => $this->test_order->get_id() )
		);

		$request = new WP_REST_Request( 'DELETE', '/wc/v4/fulfillments/' . $target->get_id() );
		$request->set_param( 'fulfillment_id', $this->test_fulfillment->get_id() );
		$response = rest_get_server()->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );

		// The fulfillment named in the query string is untouched.
		$survivor = new Fulfillment( $this->test_fulfillment->get_id() );
		$this->assertNull( $survivor->get_date_deleted() );
	}

	/**
	 * delete_fulfillment() must resolve the fulfillment itself rather than relying on the
	 * permission callback having done it. The Fulfillment constructor throws when no row matches,
	 * so a handler without its own not-found path fatals instead of returning a response.
	 */
	public function test_delete_fulfillment_handler_reports_unknown_id() {
		wp_set_current_user( self::$admin_user_id );

		$request = new WP_REST_Request( 'DELETE', '/wc/v4/fulfillments/99999' );
		$request->set_url_params( array( 'fulfillment_id' => '99999' ) );

		$response = $this->controller->delete_fulfillment( $request );

		$this->assertSame( 404, $response->get_status() );
		$data = $response->get_data();
		$this->assertSame( 'woocommerce_rest_fulfillment_invalid_id', $data['code'] );
	}

	/**
	 * wc_get_order() returns a WC_Order_Refund for a refund ID, and refunds have no
	 * get_customer_id(). The collection route must reject one as a bad order rather than fataling
	 * in the owner check.
	 */
	public function test_get_fulfillments_rejects_refund_id_as_order_id() {
		wp_set_current_user( self::$admin_user_id );

		$refund = wc_create_refund(
			array(
				'order_id' => $this->test_order->get_id(),
				'amount'   => 1,
			)
		);
		$this->assertNotWPError( $refund );

		$request = new WP_REST_Request( 'GET', '/wc/v4/fulfillments' );
		$request->set_param( 'order_id', $refund->get_id() );
		$response = rest_get_server()->dispatch( $request );

		$this->assertSame( 400, $response->get_status() );
		$data = $response->get_data();
		$this->assertSame( 'woocommerce_rest_order_invalid_id', $data['code'] );
	}

	/**
	 * The collection route authorizes against order_id only. When the order_id does not resolve
	 * to an order, a fulfillment_id query argument the caller owns must not move authorization
	 * to the caller's own order: the request is rejected for the missing order instead.
	 */
	public function test_permission_check_customer_cannot_read_other_orders_collection_via_query_fulfillment_id() {
		$attacker_user_id     = $this->factory->user->create( array( 'role' => 'customer' ) );
		$attacker_order       = WC_Helper_Order::create_order( $attacker_user_id );
		$attacker_fulfillment = FulfillmentsHelper::create_fulfillment(
			array( 'entity_id' => $attacker_order->get_id() )
		);

		wp_set_current_user( $attacker_user_id );

		// Collection request: an order_id that resolves to no order, plus a fulfillment_id the attacker owns.
		$request = new WP_REST_Request( 'GET', '/wc/v4/fulfillments' );
		$request->set_param( 'order_id', 99999 );
		$request->set_param( 'fulfillment_id', $attacker_fulfillment->get_id() );
		$response = rest_get_server()->dispatch( $request );

		$this->assertSame( 400, $response->get_status() );
		$data = $response->get_data();
		$this->assertSame( 'woocommerce_rest_order_id_required', $data['code'] );
	}

	/**
	 * The order owner can still read their own fulfillment on the single-item route (no order_id
	 * needed): the order is derived from the fulfillment, confirming the fix does not block
	 * legitimate access.
	 */
	public function test_permission_check_customer_can_read_own_single_fulfillment() {
		wp_set_current_user( self::$customer_user_id );

		$request  = new WP_REST_Request( 'GET', '/wc/v4/fulfillments/' . $this->test_fulfillment->get_id() );
		$response = rest_get_server()->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertSame( $this->test_fulfillment->get_id(), $data['id'] );
	}

	/**
	 * The order owner only gets read access on the single-item route: update and delete
	 * requests on their own fulfillment are refused.
	 */
	public function test_permission_check_customer_cannot_write_own_single_fulfillment() {
		wp_set_current_user( self::$customer_user_id );

		$update_request = new WP_REST_Request( 'PUT', '/wc/v4/fulfillments/' . $this->test_fulfillment->get_id() );
		$update_request->set_header( 'Content-Type', 'application/json' );
		$update_request->set_body( wp_json_encode( $this->get_test_fulfillment_data() ) );
		$update_response = rest_get_server()->dispatch( $update_request );
		$this->assertSame( 403, $update_response->get_status() );

		$delete_request  = new WP_REST_Request( 'DELETE', '/wc/v4/fulfillments/' . $this->test_fulfillment->get_id() );
		$delete_response = rest_get_server()->dispatch( $delete_request );
		$this->assertSame( 403, $delete_response->get_status() );
	}

	/**
	 * A zero fulfillment ID matches the single-item route regex and must be rejected by the
	 * permission callback before any lookup.
	 */
	public function test_permission_check_zero_fulfillment_id() {
		wp_set_current_user( self::$admin_user_id );

		$request  = new WP_REST_Request( 'GET', '/wc/v4/fulfillments/0' );
		$response = rest_get_server()->dispatch( $request );

		$this->assertSame( 400, $response->get_status() );
		$data = $response->get_data();
		$this->assertSame( 'woocommerce_rest_fulfillment_invalid_id', $data['code'] );
	}

	/**
	 * Test schema validation for get fulfillments
	 */
	public function test_get_fulfillments_schema() {
		wp_set_current_user( self::$admin_user_id );

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
		wp_set_current_user( self::$admin_user_id );

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
		wp_set_current_user( self::$admin_user_id );

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
