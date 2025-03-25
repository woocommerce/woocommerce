<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Orders;

use Automattic\WooCommerce\Internal\Orders\OrderActionsRestController;
use WC_Helper_Order;
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
	 * @var int[] Associative array of user IDs.
	 */
	private $user = array();

	/**
	 * Set up test.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->controller = new OrderActionsRestController();
		$this->controller->register_routes();

		$this->user['shop_manager'] = $this->factory->user->create( array( 'role' => 'shop_manager' ) );
		$this->user['customer']     = $this->factory->user->create( array( 'role' => 'customer' ) );
	}

	/**
	 * Create a partial refund for an order.
	 *
	 * @param \WC_Order $order The order to create a refund for.
	 *
	 * @return void
	 * @throws \Exception Throws Exception if refund creation fails.
	 */
	private function do_partial_refund( \WC_Order $order ): void {
		$items      = $order->get_items();
		$first_item = reset( $items );

		wc_create_refund(
			array(
				'order_id'   => $order->get_id(),
				'line_items' => array(
					$first_item->get_product_id() => array(
						'qty' => 1,
					),
				),
			)
		);
	}

	/**
	 * Data provider for `test_email_templates`.
	 *
	 * @return \Generator
	 */
	public function provide_data_for_email_templates() {
		yield 'unauthorized request' => array(
			'customer',
			array(),
			array(
				'status' => 403,
				'error'  => 'Sorry, you cannot view resources.',
			),
		);
		yield 'order with no billing email' => array(
			'shop_manager',
			array(
				'billing_email' => '',
			),
			array(
				'status' => 200,
				'data'   => array(),
			),
		);
		yield 'auto-draft order' => array(
			'shop_manager',
			array(
				'status' => 'auto-draft',
			),
			array(
				'status' => 200,
				'data'   => array(),
			),
		);
		yield 'completed order' => array(
			'shop_manager',
			array(),
			array(
				'status' => 200,
				'data'   => array(
					'customer_completed_order',
					'customer_invoice',
				),
			),
		);
		yield 'partially refunded order' => array(
			'shop_manager',
			array(
				'partial_refund' => true,
			),
			array(
				'status' => 200,
				'data'   => array(
					'customer_completed_order',
					'customer_refunded_order',
					'customer_invoice',
				),
			),
		);
		yield 'fully refunded order' => array(
			'shop_manager',
			array(
				'status' => 'refunded',
			),
			array(
				'status' => 200,
				'data'   => array(
					'customer_refunded_order',
					'customer_invoice',
				),
			),
		);
	}

	/**
	 * Test the wc/v3/orders/{id}/actions/email_templates endpoint.
	 *
	 * @dataProvider provide_data_for_email_templates
	 *
	 * @param string $user          Which user from the $users array to use.
	 * @param array  $order_props   Properties of the order.
	 * @param array  $response_data Expected results.
	 *
	 * @return void
	 */
	public function test_email_templates( string $user, array $order_props, array $response_data ) {
		$order_defaults = array(
			'billing_email'  => 'customer@example.org',
			'status'         => 'completed',
			'partial_refund' => false,
		);
		$order_props    = wp_parse_args( $order_props, $order_defaults );

		$order = wc_create_order();
		if ( true === $order_props['partial_refund'] ) {
			$order = WC_Helper_Order::create_order();
			$this->do_partial_refund( $order );
		}

		$order->set_billing_email( $order_props['billing_email'] );
		$order->set_status( $order_props['status'] );
		$order->save();

		wp_set_current_user( $this->user[ $user ] );

		$request  = new WP_REST_Request( 'GET', '/wc/v3/orders/' . $order->get_id() . '/actions/email_templates' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( $response_data['status'], $response->get_status() );

		$data = $response->get_data();
		if ( 200 !== $response_data['status'] && array_key_exists( 'error', $response_data ) ) {
			$this->assertEquals( $response_data['error'], $data['message'] );
		} elseif ( empty( $response_data['data'] ) ) {
			$this->assertEmpty( $data );
		} else {
			$this->assertCount( count( $response_data['data'] ), $data );

			$first_item = reset( $data );
			$this->assertArrayHasKey( 'id', $first_item );
			$this->assertArrayHasKey( 'title', $first_item );
			$this->assertArrayHasKey( 'description', $first_item );

			$item_ids = wp_list_pluck( $data, 'id' );
			foreach ( $response_data['data'] as $template_id ) {
				$this->assertContains( $template_id, $item_ids );
			}
		}
	}

	/**
	 * Data provider for `test_send_email`.
	 *
	 * @return \Generator
	 */
	public function provide_data_for_send_email() {
		yield 'unauthorized request' => array(
			'customer',
			array(),
			array(),
			array(
				'status'  => 403,
				'message' => 'Sorry, you cannot create resources.',
				'notes'   => array(),
			),
		);
		yield 'unavailable template' => array(
			'shop_manager',
			array(),
			array(
				'template_id' => 'customer_failed_order',
			),
			array(
				'status'  => 400,
				'message' => 'customer_failed_order is not a valid template for this order.',
				'notes'   => array(),
			),
		);
		yield 'no billing email' => array(
			'shop_manager',
			array(
				'billing_email' => '',
			),
			array(),
			array(
				'status'  => 400,
				'message' => 'Order does not have an email address.',
				'notes'   => array(),
			),
		);
		yield 'no billing email, email specified, no force' => array(
			'shop_manager',
			array(
				'billing_email' => '',
			),
			array(
				'email' => 'another@example.org',
			),
			array(
				'status'  => 200,
				'message' => 'Billing email updated to another@example.org. Email template &quot;Completed order&quot; sent to another@example.org, via some app.',
				'notes'   => array(
					'Billing email updated to another@example.org.',
					'Email template &quot;Completed order&quot; sent to another@example.org, via some app.',
				),
			),
		);
		yield 'existing billing email, different email specified, no force' => array(
			'shop_manager',
			array(),
			array(
				'email' => 'another@example.org',
			),
			array(
				'status'  => 400,
				'message' => 'Order already has a billing email.',
				'notes'   => array(),
			),
		);
		yield 'existing billing email, different email specified, force' => array(
			'shop_manager',
			array(),
			array(
				'email' => 'another@example.org',
				'force' => true,
			),
			array(
				'status'  => 200,
				'message' => 'Billing email updated to another@example.org. Email template &quot;Completed order&quot; sent to another@example.org, via some app.',
				'notes'   => array(
					'Billing email updated to another@example.org.',
					'Email template &quot;Completed order&quot; sent to another@example.org, via some app.',
				),
			),
		);
		yield 'existing billing email, same email specified' => array(
			'shop_manager',
			array(),
			array(
				'email' => 'customer@example.org',
			),
			array(
				'status'  => 200,
				'message' => 'Email template &quot;Completed order&quot; sent to customer@example.org, via some app.',
				'notes'   => array(
					'Email template &quot;Completed order&quot; sent to customer@example.org, via some app.',
				),
			),
		);
		yield 'completed order' => array(
			'shop_manager',
			array(),
			array(),
			array(
				'status'  => 200,
				'message' => 'Email template &quot;Completed order&quot; sent to customer@example.org, via some app.',
				'notes'   => array(
					'Email template &quot;Completed order&quot; sent to customer@example.org, via some app.',
				),
			),
		);
	}

	/**
	 * Test the wc/v3/orders/{id}/actions/send_email endpoint.
	 *
	 * @dataProvider provide_data_for_send_email
	 *
	 * @param string $user           Which user from the $users array to use.
	 * @param array  $order_props    Properties of the order.
	 * @param array  $request_params Request parameters.
	 * @param array  $result         Expected results.
	 *
	 * @return void
	 */
	public function test_send_email( string $user, array $order_props, array $request_params, array $result ) {
		$order_defaults = array(
			'billing_email'  => 'customer@example.org',
			'status'         => 'completed',
			'partial_refund' => false,
		);
		$order_props    = wp_parse_args( $order_props, $order_defaults );

		$request_defaults = array(
			'template_id' => 'customer_completed_order',
			'email'       => '',
			'force'       => false,
		);
		$request_params   = wp_parse_args( $request_params, $request_defaults );

		$order = wc_create_order();
		if ( true === $order_props['partial_refund'] ) {
			$order = WC_Helper_Order::create_order();
			$this->do_partial_refund( $order );
		}

		$order->set_billing_email( $order_props['billing_email'] );
		$order->set_status( $order_props['status'] );
		$order->save();

		wp_set_current_user( $this->user[ $user ] );

		$request = new WP_REST_Request( 'POST', '/wc/v3/orders/' . $order->get_id() . '/actions/send_email' );
		$request->add_header( 'User-Agent', 'some app' );
		$request->set_param( 'template_id', $request_params['template_id'] );
		if ( ! empty( $request_params['email'] ) ) {
			$request->set_param( 'email', $request_params['email'] );
		}
		if ( ! empty( $request_params['force'] ) ) {
			$request->set_param( 'force_email_update', $request_params['force'] );
		}

		$response = $this->server->dispatch( $request );

		$this->assertEquals( $result['status'], $response->get_status() );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'message', $data );
		$this->assertEquals( $result['message'], $data['message'] );

		$notes         = wc_get_order_notes( array( 'order_id' => $order->get_id() ) );
		$notes_content = wp_list_pluck( $notes, 'content' );
		foreach ( $result['notes'] as $note ) {
			$this->assertContains( $note, $notes_content );
		}
	}

	/**
	 * Test sending order details email.
	 */
	public function test_send_order_details() {
		$order = wc_create_order();
		$order->set_billing_email( 'customer@email.com' );
		$order->save();

		wp_set_current_user( $this->user['shop_manager'] );

		$request = new WP_REST_Request( 'POST', '/wc/v3/orders/' . $order->get_id() . '/actions/send_order_details' );
		$request->add_header( 'User-Agent', 'some app' );

		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'message', $data );
		$this->assertEquals( 'Order details sent to customer@email.com, via some app.', $data['message'] );

		$notes = wc_get_order_notes( array( 'order_id' => $order->get_id() ) );
		$this->assertCount( 1, $notes );
		$this->assertEquals( 'Order details sent to customer@email.com, via some app.', $notes[0]->content );
	}

	/**
	 * Test sending order details email for a non-existent order.
	 */
	public function test_send_order_details_with_non_existent_order() {
		wp_set_current_user( $this->user['shop_manager'] );

		$request  = new WP_REST_Request( 'POST', '/wc/v3/orders/999/actions/send_order_details' );
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

		wp_set_current_user( $this->user['customer'] );

		$request  = new WP_REST_Request( 'POST', '/wc/v3/orders/' . $order->get_id() . '/actions/send_order_details' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 403, $response->get_status() );
	}

	/**
	 * Test sending order details email with a custom email, but the order already has a billing email.
	 */
	public function test_send_order_details_with_custom_email_but_order_already_has_email() {
		$order = wc_create_order();
		$order->set_billing_email( 'customer@email.com' );
		$order->save();

		wp_set_current_user( $this->user['shop_manager'] );

		$request = new WP_REST_Request( 'POST', '/wc/v3/orders/' . $order->get_id() . '/actions/send_order_details' );
		$request->add_header( 'User-Agent', 'some app' );
		$request->set_param( 'email', 'another@email.com' );

		$response = $this->server->dispatch( $request );

		$this->assertEquals( 400, $response->get_status() );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'message', $data );
		$this->assertEquals( 'Order already has a billing email.', $data['message'] );
	}

	/**
	 * Test sending order details email with a custom email and the force flag to update the order's billing email.
	 */
	public function test_send_order_details_with_custom_email_force_update() {
		$order = wc_create_order();
		$order->set_billing_email( 'customer@email.com' );
		$order->save();

		wp_set_current_user( $this->user['shop_manager'] );

		$request = new WP_REST_Request( 'POST', '/wc/v3/orders/' . $order->get_id() . '/actions/send_order_details' );
		$request->add_header( 'User-Agent', 'some app' );
		$request->set_param( 'email', 'another@email.com' );
		$request->set_param( 'force_email_update', true );

		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'message', $data );
		$this->assertEquals( 'Billing email updated to another@email.com. Order details sent to another@email.com, via some app.', $data['message'] );

		$notes = wc_get_order_notes( array( 'order_id' => $order->get_id() ) );
		$this->assertCount( 2, $notes );

		$notes_content = wp_list_pluck( $notes, 'content' );
		$this->assertContainsEquals( 'Billing email updated to another@email.com.', $notes_content );
		$this->assertContainsEquals( 'Order details sent to another@email.com, via some app.', $notes_content );
	}

	/**
	 * Test sending order details email with an invalid email parameter.
	 */
	public function test_send_order_details_with_invalid_email_param() {
		$order = wc_create_order();
		$order->save();

		wp_set_current_user( $this->user['shop_manager'] );

		$request = new WP_REST_Request( 'POST', '/wc/v3/orders/' . $order->get_id() . '/actions/send_order_details' );
		$request->add_header( 'User-Agent', 'some app' );
		$request->set_param( 'email', 'invalid-email' );

		$response = $this->server->dispatch( $request );

		$this->assertEquals( 400, $response->get_status() );

		$data = $response->get_data();
		$this->assertEquals( 'rest_invalid_param', $data['code'] );
		$this->assertEquals( 'Invalid parameter(s): email', $data['message'] );
	}

	/**
	 * Test sending order details email when the order does not have an email.
	 */
	public function test_send_order_details_without_order_email() {
		$order = wc_create_order();
		$order->set_billing_email( '' );
		$order->save();

		wp_set_current_user( $this->user['shop_manager'] );

		$request = new WP_REST_Request( 'POST', '/wc/v3/orders/' . $order->get_id() . '/actions/send_order_details' );
		$request->add_header( 'User-Agent', 'some app' );

		$response = $this->server->dispatch( $request );

		$this->assertEquals( 400, $response->get_status() );

		$data = $response->get_data();
		$this->assertEquals( 'woocommerce_rest_missing_email', $data['code'] );
		$this->assertEquals( 'Order does not have an email address.', $data['message'] );
	}
}
