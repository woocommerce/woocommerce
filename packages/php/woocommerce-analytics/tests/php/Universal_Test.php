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

	/**
	 * capture_remove_from_cart should return early — without raising a PHP
	 * warning or queueing a pixel — when the cart key is not present in
	 * removed_cart_contents. Reproduces the warnings reported on
	 * line 141/142 of class-universal.php where third-party code fires the
	 * woocommerce_cart_item_removed action with a key that was never copied
	 * into removed_cart_contents.
	 */
	public function test_capture_remove_from_cart_skips_when_item_missing(): void {
		$cart                        = new \stdClass();
		$cart->removed_cart_contents = array();

		$this->reset_pixel_batch_queue();

		$universal = new Universal();
		$universal->capture_remove_from_cart( 'unknown_key', $cart );

		$this->assertSame( array(), $this->get_pixel_batch_queue(), 'No pixel should be queued when the removed cart item is missing.' );
	}

	/**
	 * capture_remove_from_cart should also bail when the entry exists but is
	 * missing the product_id/quantity keys the event payload requires.
	 */
	public function test_capture_remove_from_cart_skips_when_item_missing_keys(): void {
		$cart                        = new \stdClass();
		$cart->removed_cart_contents = array(
			'partial_key' => array( 'product_id' => 5 ), // No quantity key.
		);

		$this->reset_pixel_batch_queue();

		$universal = new Universal();
		$universal->capture_remove_from_cart( 'partial_key', $cart );

		$this->assertSame( array(), $this->get_pixel_batch_queue(), 'No pixel should be queued when the removed cart item lacks required keys.' );
	}

	/**
	 * capture_cart_quantity_update should return early — without raising a
	 * PHP warning or queueing a pixel — when the cart key is not present in
	 * cart_contents. Reproduces the warning reported on line 159 of
	 * class-universal.php.
	 */
	public function test_capture_cart_quantity_update_skips_when_item_missing(): void {
		$cart                = new \stdClass();
		$cart->cart_contents = array();

		$this->reset_pixel_batch_queue();

		$universal = new Universal();
		$universal->capture_cart_quantity_update( 'unknown_key', 2, 1, $cart );

		$this->assertSame( array(), $this->get_pixel_batch_queue(), 'No pixel should be queued when the cart item is missing.' );
	}

	/**
	 * Reset the WC_Analytics_Tracking::$pixel_batch_queue static so each
	 * test starts from a known-empty state.
	 */
	private function reset_pixel_batch_queue(): void {
		$reflection = new \ReflectionClass( WC_Analytics_Tracking::class );
		$queue      = $reflection->getProperty( 'pixel_batch_queue' );
		$queue->setAccessible( true );
		$queue->setValue( null, array() );
	}

	/**
	 * Read WC_Analytics_Tracking::$pixel_batch_queue via reflection to
	 * verify whether a pixel was queued.
	 *
	 * @return array
	 */
	private function get_pixel_batch_queue(): array {
		$reflection = new \ReflectionClass( WC_Analytics_Tracking::class );
		$property   = $reflection->getProperty( 'pixel_batch_queue' );
		$property->setAccessible( true );
		return $property->getValue();
	}
}
