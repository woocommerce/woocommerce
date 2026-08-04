<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Utilities;

use Automattic\WooCommerce\Utilities\OrderUtil;
use WC_Helper_Order;
use WC_Unit_Test_Case;

/**
 * Tests for the OrderUtil class.
 */
class OrderUtilTest extends WC_Unit_Test_Case {

	/**
	 * @testdox `get_last_order_note` should return the most recent note of any type.
	 */
	public function test_get_last_order_note_returns_most_recent_note(): void {
		$order = WC_Helper_Order::create_order();
		$order->add_order_note( 'First note' );
		$order->add_order_note( 'Last note' );

		$note = OrderUtil::get_last_order_note( $order->get_id() );

		$this->assertNotNull( $note, 'A note should be returned for an order with notes' );
		$this->assertEquals( 'Last note', $note->content );
	}

	/**
	 * @testdox `get_last_order_note` should filter by note type.
	 */
	public function test_get_last_order_note_filters_by_type(): void {
		$order = WC_Helper_Order::create_order();
		$order->add_order_note( 'Customer note', 1 );
		$order->add_order_note( 'Internal note' );

		$customer_note = OrderUtil::get_last_order_note( $order->get_id(), 'customer' );
		$internal_note = OrderUtil::get_last_order_note( $order->get_id(), 'internal' );

		$this->assertEquals( 'Customer note', $customer_note->content );
		$this->assertEquals( 'Internal note', $internal_note->content );
	}

	/**
	 * @testdox `get_last_order_note` should return null when the order has no notes.
	 */
	public function test_get_last_order_note_returns_null_without_notes(): void {
		$order = WC_Helper_Order::create_order();

		$this->assertNull( OrderUtil::get_last_order_note( $order->get_id() ) );
	}
}
