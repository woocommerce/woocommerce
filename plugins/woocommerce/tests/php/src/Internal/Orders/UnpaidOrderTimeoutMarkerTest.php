<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Orders;

use Automattic\WooCommerce\Caches\OrderCache;
use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Internal\Orders\UnpaidOrderTimeoutMarker;
use WC_Helper_Order;
use WC_Order;
use WC_Unit_Test_Case;

/**
 * Tests for the UnpaidOrderTimeoutMarker class.
 *
 * @covers \Automattic\WooCommerce\Internal\Orders\UnpaidOrderTimeoutMarker
 */
class UnpaidOrderTimeoutMarkerTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var UnpaidOrderTimeoutMarker
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new UnpaidOrderTimeoutMarker();
		$this->sut->register();
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		remove_all_actions( 'woocommerce_order_status_cancelled' );
		parent::tearDown();
	}

	/**
	 * Load an order from the database, bypassing the order cache.
	 *
	 * @param int $order_id Order ID.
	 * @return WC_Order
	 */
	private function reload( int $order_id ): WC_Order {
		wc_get_container()->get( OrderCache::class )->remove( $order_id );
		return wc_get_order( $order_id );
	}

	/**
	 * @testdox cancel_unpaid_order cancels the order, marks it and adds the note.
	 */
	public function test_cancel_unpaid_order_cancels_and_marks_order(): void {
		$order = WC_Helper_Order::create_order();

		$this->sut->cancel_unpaid_order( $order, 'Timer note.' );

		$fresh = $this->reload( $order->get_id() );
		$this->assertSame( OrderStatus::CANCELLED, $fresh->get_status() );
		$this->assertTrue( UnpaidOrderTimeoutMarker::is_marked( $fresh ) );
		$notes = implode( ' ', wp_list_pluck( wc_get_order_notes( array( 'order_id' => $order->get_id() ) ), 'content' ) );
		$this->assertStringContainsString( 'Timer note.', $notes );
	}

	/**
	 * @testdox The marker survives a restore: $to
	 * @dataProvider provide_restore_statuses
	 *
	 * @param string $to Status the order is restored to.
	 */
	public function test_marker_survives_restore( string $to ): void {
		$order = WC_Helper_Order::create_order();
		$this->sut->cancel_unpaid_order( $order, 'Timer note.' );

		$order->update_status( $to );

		$this->assertTrue( UnpaidOrderTimeoutMarker::is_marked( $this->reload( $order->get_id() ) ) );
	}

	/**
	 * @testdox A later cancellation clears the marker after a restore to $to
	 * @dataProvider provide_restore_statuses
	 *
	 * @param string $to Status the order is restored to.
	 */
	public function test_later_cancellation_clears_marker( string $to ): void {
		$order = WC_Helper_Order::create_order();
		$this->sut->cancel_unpaid_order( $order, 'Timer note.' );
		$order->update_status( $to );

		$order->update_status( OrderStatus::CANCELLED );

		$this->assertFalse( UnpaidOrderTimeoutMarker::is_marked( $order ), 'The caller\'s object should be cleared.' );
		$this->assertFalse( UnpaidOrderTimeoutMarker::is_marked( $this->reload( $order->get_id() ) ) );
	}

	/**
	 * Data provider for statuses an order can be restored to.
	 *
	 * @return array
	 */
	public function provide_restore_statuses(): array {
		return array(
			'pending'    => array( OrderStatus::PENDING ),
			'processing' => array( OrderStatus::PROCESSING ),
			'on-hold'    => array( OrderStatus::ON_HOLD ),
		);
	}

	/**
	 * @testdox A leftover marker on a pending order is cleared by a manual cancellation.
	 */
	public function test_manual_cancellation_clears_leftover_marker(): void {
		$order = WC_Helper_Order::create_order();
		$order->update_meta_data( UnpaidOrderTimeoutMarker::META_KEY, 'yes' );
		$order->save();

		$order->update_status( OrderStatus::CANCELLED );

		$this->assertFalse( UnpaidOrderTimeoutMarker::is_marked( $this->reload( $order->get_id() ) ) );
	}

	/**
	 * @testdox A nested timer cancellation does not clear the outer order's marker.
	 */
	public function test_nested_timer_cancellation_keeps_outer_marker(): void {
		$order_a = WC_Helper_Order::create_order();
		$order_b = WC_Helper_Order::create_order();
		$sut     = $this->sut;

		add_action(
			'woocommerce_order_status_cancelled',
			function ( $order_id ) use ( $order_a, $order_b, $sut ) {
				if ( $order_id === $order_a->get_id() ) {
					$sut->cancel_unpaid_order( $order_b, 'Nested.' );
				}
			},
			5
		);

		$sut->cancel_unpaid_order( $order_a, 'Outer.' );

		$this->assertTrue( UnpaidOrderTimeoutMarker::is_marked( $this->reload( $order_a->get_id() ) ) );
		$this->assertTrue( UnpaidOrderTimeoutMarker::is_marked( $this->reload( $order_b->get_id() ) ) );
	}
}
