<?php
/**
 * Tests for the Universal class.
 *
 * @package automattic/woocommerce-analytics
 */

namespace Automattic\Woocommerce_Analytics;

use WC_Order;
use WorDBless\BaseTestCase;

/**
 * Tests for the Universal class.
 */
class Universal_Test extends BaseTestCase {

	/**
	 * Reset global mocks before each test.
	 */
	public function set_up(): void {
		parent::set_up();
		global $wc_get_order_calls, $wc_get_order_mock_return;
		$wc_get_order_calls       = array();
		$wc_get_order_mock_return = false;
	}

	/**
	 * Test that order_process calls wc_get_order with an integer order ID.
	 */
	public function test_order_process_handles_integer_order_id(): void {
		global $wc_get_order_calls, $wc_get_order_mock_return;

		// Set up mock to return false (order not found).
		$wc_get_order_mock_return = false;

		$universal = new Universal();
		$universal->order_process( 12345 );

		$this->assertCount( 1, $wc_get_order_calls, 'wc_get_order should be called once.' );
		$this->assertSame( 12345, $wc_get_order_calls[0], 'wc_get_order should receive the integer order ID.' );
	}

	/**
	 * Test that order_process calls wc_get_order with a string order ID.
	 */
	public function test_order_process_handles_string_order_id(): void {
		global $wc_get_order_calls, $wc_get_order_mock_return;

		// Set up mock to return false (order not found).
		$wc_get_order_mock_return = false;

		$universal = new Universal();
		$universal->order_process( '12345' );

		$this->assertCount( 1, $wc_get_order_calls, 'wc_get_order should be called once.' );
		$this->assertSame( '12345', $wc_get_order_calls[0], 'wc_get_order should receive the string order ID.' );
	}

	/**
	 * Test that order_process calls wc_get_order with a WC_Order object.
	 */
	public function test_order_process_handles_wc_order_object(): void {
		global $wc_get_order_calls, $wc_get_order_mock_return;

		// Set up mock to return false (order not found).
		$wc_get_order_mock_return = false;

		$order = new WC_Order();

		$universal = new Universal();
		$universal->order_process( $order );

		$this->assertCount( 1, $wc_get_order_calls, 'wc_get_order should be called once.' );
		$this->assertSame( $order, $wc_get_order_calls[0], 'wc_get_order should receive the WC_Order object.' );
	}

	/**
	 * Test that order_process returns early when wc_get_order returns false.
	 */
	public function test_order_process_returns_early_when_order_not_found(): void {
		global $wc_get_order_mock_return;

		// Set up mock to return false.
		$wc_get_order_mock_return = false;

		$universal = new Universal();
		$universal->order_process( 12345 );

		// If we get here without errors, the method completed without processing a non-existent order.
		$this->assertTrue( true, 'order_process should handle a missing order without throwing an exception.' );
	}
}
