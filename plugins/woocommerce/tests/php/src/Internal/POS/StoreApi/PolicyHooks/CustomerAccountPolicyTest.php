<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\POS\StoreApi\PolicyHooks;

use Automattic\WooCommerce\Internal\POS\StoreApi\Context;
use Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks\CustomerAccountPolicy;
use Automattic\WooCommerce\StoreApi\Exceptions\RouteException;
use WC_Order;
use WC_Unit_Test_Case;
use WP_REST_Request;

/**
 * Tests for the POS Customer Account policy hook.
 *
 * @covers \Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks\CustomerAccountPolicy
 */
class CustomerAccountPolicyTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var CustomerAccountPolicy
	 */
	private $sut;

	/**
	 * Set up.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new CustomerAccountPolicy();
	}

	/**
	 * Tear down.
	 */
	public function tearDown(): void {
		remove_action( 'woocommerce_store_api_checkout_update_order_from_request', array( $this->sut, 'attach_customer_account' ), 10 );
		Context::set_test_override( null );
		parent::tearDown();
	}

	/**
	 * The action is installed unconditionally; the POS gating happens inside the
	 * callback, not at registration time.
	 *
	 * @testdox register() attaches the customer-account hook unconditionally.
	 */
	public function test_register_attaches_hook_unconditionally(): void {
		$this->sut->register();

		$this->assertNotFalse( has_action( 'woocommerce_store_api_checkout_update_order_from_request', array( $this->sut, 'attach_customer_account' ) ) );
	}

	/**
	 * @testdox attach_customer_account is a no-op outside POS context even when a customer_id is supplied.
	 */
	public function test_no_op_outside_pos_context(): void {
		Context::set_test_override( false );

		$customer_id = $this->factory->user->create( array( 'role' => 'customer' ) );
		$order       = new WC_Order();

		$this->sut->attach_customer_account( $order, $this->request_with( $customer_id ) );

		$this->assertSame( 0, $order->get_customer_id(), 'Outside POS the order must not be attributed to the supplied customer.' );

		$order->delete( true );
		wp_delete_user( $customer_id );
	}

	/**
	 * @testdox attach_customer_account sets the order customer id from a valid customer_id.
	 */
	public function test_sets_customer_id_for_valid_customer(): void {
		Context::set_test_override( true );

		$customer_id = $this->factory->user->create( array( 'role' => 'customer' ) );
		$order       = new WC_Order();

		$this->sut->attach_customer_account( $order, $this->request_with( $customer_id ) );

		$this->assertSame( $customer_id, $order->get_customer_id() );

		$order->delete( true );
		wp_delete_user( $customer_id );
	}

	/**
	 * @testdox attach_customer_account leaves the order as a guest order when no customer_id is sent.
	 */
	public function test_leaves_guest_order_when_no_customer_id(): void {
		Context::set_test_override( true );

		$order = new WC_Order();

		$this->sut->attach_customer_account( $order, new WP_REST_Request( 'POST' ) );

		$this->assertSame( 0, $order->get_customer_id() );

		$order->delete( true );
	}

	/**
	 * @testdox attach_customer_account treats an explicit zero customer_id as a guest order.
	 */
	public function test_treats_zero_as_guest_order(): void {
		Context::set_test_override( true );

		$order = new WC_Order();

		$this->sut->attach_customer_account( $order, $this->request_with( 0 ) );

		$this->assertSame( 0, $order->get_customer_id() );

		$order->delete( true );
	}

	/**
	 * @testdox attach_customer_account rejects a customer_id that does not match an existing user.
	 */
	public function test_rejects_unknown_customer_id(): void {
		Context::set_test_override( true );

		$order = new WC_Order();

		$this->expectException( RouteException::class );

		try {
			$this->sut->attach_customer_account( $order, $this->request_with( 999999 ) );
		} finally {
			$order->delete( true );
		}
	}

	/**
	 * Build a checkout request carrying the given customer_id parameter.
	 *
	 * @param mixed $customer_id Value to set as the `customer_id` body param.
	 * @return WP_REST_Request
	 */
	private function request_with( $customer_id ): WP_REST_Request {
		$request = new WP_REST_Request( 'POST' );
		$request->set_param( 'customer_id', $customer_id );
		return $request;
	}
}
