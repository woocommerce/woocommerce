<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Orders;

use Automattic\WooCommerce\Internal\Orders\OrderActionsRestController;
use WC_REST_Unit_Test_Case;
use WP_REST_Request;

/**
 * OrderActionsRestController API controller test.
 *
 * @class OrderActionsRestController
 */
class OrderActionsRestControllerTest extends WC_REST_Unit_Test_Case {
	/**
	 * @var OrderActionsRestController
	 */
	protected $controller;

	/**
	 * @var int User ID.
	 */
	private $user;

	/**
	 * Set up test.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->controller = new OrderActionsRestController();
		$this->controller->register_routes();

		$this->user = $this->factory->user->create( array( 'role' => 'shop_manager' ) );
	}

	/**
	 * Test calling the endpoint without an action.
	 */
	public function test_missing_action() {
		wp_set_current_user( $this->user );

		$order   = wc_create_order();
		$request = new WP_REST_Request( 'POST', '/wc/v3/orders/' . $order->get_id() . '/actions' );

		$response = $this->server->dispatch( $request );

		$this->assertEquals( 400, $response->get_status() );
		$data = $response->get_data();
		$this->assertEquals( 'rest_missing_callback_param', $data['code'] );
		$this->assertEquals( 'Missing parameter(s): action', $data['message'] );
	}

	/**
	 * Test calling the endpoint with an unknown action.
	 */
	public function test_invalid_action() {
		wp_set_current_user( $this->user );

		$order   = wc_create_order();
		$request = new WP_REST_Request( 'POST', '/wc/v3/orders/' . $order->get_id() . '/actions' );
		$request->set_param( 'action', 'unknown_action' );

		$response = $this->server->dispatch( $request );

		$this->assertEquals( 400, $response->get_status() );
		$data = $response->get_data();
		$this->assertEquals( 'rest_invalid_param', $data['code'] );
		$this->assertEquals( 'Invalid parameter(s): action', $data['message'] );
	}

	/**
	 * Test sending order details email.
	 */
	public function test_send_order_details() {
		$order = wc_create_order();

		wp_set_current_user( $this->user );

		$request = new WP_REST_Request( 'POST', '/wc/v3/orders/' . $order->get_id() . '/actions' );
		$request->set_param( 'action', 'send_order_details' );

		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'message', $data );
		$this->assertEquals( 'Order details email sent to customer.', $data['message'] );

		$notes = wc_get_order_notes( array( 'order_id' => $order->get_id() ) );
		$this->assertCount( 1, $notes );
		$this->assertEquals( 'Order details sent to customer via REST API.', $notes[0]->content );
	}

	/**
	 * Test sending order details email for a non-existent order.
	 */
	public function test_send_order_details_with_non_existent_order() {
		wp_set_current_user( $this->user );

		$request = new WP_REST_Request( 'POST', '/wc/v3/orders/999/actions' );
		$request->set_param( 'action', 'send_order_details' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 404, $response->get_status() );

		$data = $response->get_data();
		$this->assertEquals( 'woocommerce_rest_not_found', $data['code'] );
		$this->assertEquals( 'Order not found', $data['message'] );
	}

	/**
	 * Test sending order details email without proper permissions.
	 */
	public function test_send_order_details_without_permission() {
		$order = wc_create_order();

		// Use a customer user who shouldn't have permission.
		$customer = $this->factory->user->create( array( 'role' => 'customer' ) );

		wp_set_current_user( $customer );

		$request = new WP_REST_Request( 'POST', '/wc/v3/orders/' . $order->get_id() . '/actions' );
		$request->set_param( 'action', 'send_order_details' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 403, $response->get_status() );
	}
}
