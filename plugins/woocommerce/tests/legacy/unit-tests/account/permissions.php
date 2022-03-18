<?php
/** Account permission tests
 *
 * @package WooCommerce\Tests\Account
 */

/**
 * Class WC_Tests_Account_Permissions.
 */
class WC_Tests_Account_Permissions extends WC_Unit_Test_Case {

	/**
	 * Setup:
	 * 1. Set current user to zero to simulate not logged in state.
	 */
	public function setUp() {
		parent::setUp();
		wp_set_current_user( 0 );
	}

	/**
	 * Teardown:
	 * 1. Set current user to 0 because we change current user in some tests.
	 */
	public function tearDown() {
		wp_set_current_user( 0 );
		parent::tearDown();
	}

	/**
	 * Test that logged in user can pay a guest order.
	 */
	public function test_wc_customer_can_pay_guest_order() {
		$order    = WC_Helper_Order::create_order( 0 );
		$customer = WC_Helper_Customer::create_customer();
		wp_set_current_user( $customer->get_id() );
		$this->assertEquals( true, current_user_can( 'pay_for_order', $order->get_id() ) );
	}

	/**
	 * Test that guest orders can be paid when not logged in.
	 */
	public function test_wc_guest_pay_guest_order() {
		$order    = WC_Helper_Order::create_order( 0 );
		$this->assertEquals( true, current_user_can( 'pay_for_order', $order->get_id() ) );
	}

	/**
	 * Test that a customer cannot pay another customer's order.
	 */
	public function test_wc_customer_cannot_pay_another_customer_order() {
		$customer1 = WC_Helper_Customer::create_customer();
		$order     = WC_Helper_Order::create_order( $customer1->get_id() );
		$customer2 = WC_Helper_Customer::create_customer( 'testcustomer2', 'woo', 'test2@local.woo' );
		wp_set_current_user( $customer2->get_id() );
		$this->assertEquals( false, current_user_can( 'pay_for_order', $order->get_id() ) );
	}

	/**
	 * Test that customer can pay their own order.
	 */
	public function test_wc_customer_can_pay_their_order() {
		$customer = WC_Helper_Customer::create_customer();
		wp_set_current_user( $customer->get_id() );
		$order     = WC_Helper_Order::create_order( $customer->get_id() );
		$this->assertEquals( true, current_user_can( 'pay_for_order', $order->get_id() ) );
	}

}
