<?php
/**
 * Order Route Tests.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\StoreApi\Routes;

use Automattic\WooCommerce\Tests\Blocks\Helpers\FixtureData;

/**
 * Order Route Tests.
 *
 * Tests for the /wc/store/v1/order endpoint, focusing on authorization.
 */
class Order extends ControllerTestCase {

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

		$fixtures      = new FixtureData();
		$this->product = $fixtures->get_simple_product(
			array(
				'name'          => 'Test Product',
				'regular_price' => 10,
			)
		);

		// Create test customers.
		$this->customer_id   = $this->factory->user->create(
			array(
				'role'       => 'customer',
				'user_email' => 'customer1@test.com',
			)
		);
		$this->customer_id_2 = $this->factory->user->create(
			array(
				'role'       => 'customer',
				'user_email' => 'customer2@test.com',
			)
		);
	}

	/**
	 * Tear down test data.
	 */
	protected function tearDown(): void {
		parent::tearDown();

		if ( $this->customer_id ) {
			wp_delete_user( $this->customer_id );
		}
		if ( $this->customer_id_2 ) {
			wp_delete_user( $this->customer_id_2 );
		}
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
