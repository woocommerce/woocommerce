<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Payments;

use Automattic\WooCommerce\Internal\Payments\OrderPaymentLifecycleService;
use Automattic\WooCommerce\Internal\Payments\OrderPaymentStore;
use Automattic\WooCommerce\Internal\Payments\PaymentLifecycleEvent;
use WC_Order;
use WC_Unit_Test_Case;

/**
 * Tests for the OrderPaymentLifecycleService class.
 */
class OrderPaymentLifecycleServiceTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var OrderPaymentLifecycleService
	 */
	private $sut;

	/**
	 * Order payment store.
	 *
	 * @var OrderPaymentStore
	 */
	private $order_payment_store;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut                 = wc_get_container()->get( OrderPaymentLifecycleService::class );
		$this->order_payment_store = wc_get_container()->get( OrderPaymentStore::class );
	}

	/**
	 * @testdox Completed events mark the order paid and preserve payment meta.
	 */
	public function test_completed_event_marks_order_paid_and_preserves_meta(): void {
		$order = $this->create_woopayments_order();

		$this->sut->apply(
			$order,
			new PaymentLifecycleEvent(
				PaymentLifecycleEvent::STATUS_COMPLETED,
				'pi_123',
				array(
					'_intent_id'        => 'pi_123',
					'_charge_id'        => 'ch_123',
					'_intention_status' => 'succeeded',
				),
				array(),
				'Payment complete.'
			)
		);

		$order = wc_get_order( $order->get_id() );

		$this->assertInstanceOf( WC_Order::class, $order );
		$this->assertSame( 'completed', $order->get_status() );
		$this->assertSame( 'pi_123', $order->get_transaction_id() );
		$this->assertSame( 'pi_123', $order->get_meta( '_intent_id', true ) );
		$this->assertSame( 'ch_123', $order->get_meta( '_charge_id', true ) );
		$this->assertSame( 'succeeded', $order->get_meta( '_intention_status', true ) );
		$this->assertOrderHasNote( $order, 'Payment complete.' );
	}

	/**
	 * @testdox Authorized events move the order on hold.
	 */
	public function test_authorized_event_moves_order_on_hold(): void {
		$order = $this->create_woopayments_order();

		$this->sut->apply(
			$order,
			new PaymentLifecycleEvent(
				PaymentLifecycleEvent::STATUS_AUTHORIZED,
				'pi_auth',
				array( '_intention_status' => 'requires_capture' ),
				array(),
				'Payment authorized.'
			)
		);

		$order = wc_get_order( $order->get_id() );

		$this->assertInstanceOf( WC_Order::class, $order );
		$this->assertSame( 'on-hold', $order->get_status() );
		$this->assertSame( 'requires_capture', $order->get_meta( '_intention_status', true ) );
		$this->assertOrderHasNote( $order, 'Payment authorized.' );
	}

	/**
	 * @testdox Failed events mark the order failed and unlock the order.
	 */
	public function test_failed_event_marks_order_failed_and_unlocks_order(): void {
		$order = $this->create_woopayments_order();

		$this->sut->apply(
			$order,
			new PaymentLifecycleEvent(
				PaymentLifecycleEvent::STATUS_FAILED,
				'pi_failed',
				array( '_intention_status' => 'requires_payment_method' ),
				array(),
				'Payment failed.'
			)
		);

		$order = wc_get_order( $order->get_id() );

		$this->assertInstanceOf( WC_Order::class, $order );
		$this->assertSame( 'failed', $order->get_status() );
		$this->assertSame( 'requires_payment_method', $order->get_meta( '_intention_status', true ) );
		$this->assertFalse( $this->order_payment_store->is_order_payment_locked( $order, 'pi_failed' ) );
		$this->assertOrderHasNote( $order, 'Payment failed.' );
	}

	/**
	 * @testdox Canceled events mark the order cancelled and delete stale fee meta.
	 */
	public function test_canceled_event_marks_order_cancelled_and_deletes_fee_meta(): void {
		$order = $this->create_woopayments_order();
		$order->update_meta_data( '_wcpay_transaction_fee', '123' );
		$order->update_meta_data( '_wcpay_net', '900' );
		$order->save();

		$this->sut->apply(
			$order,
			new PaymentLifecycleEvent(
				PaymentLifecycleEvent::STATUS_CANCELED,
				'pi_canceled',
				array( '_intention_status' => 'canceled' ),
				array( '_wcpay_transaction_fee', '_wcpay_net' ),
				'Payment canceled.'
			)
		);

		$order = wc_get_order( $order->get_id() );

		$this->assertInstanceOf( WC_Order::class, $order );
		$this->assertSame( 'cancelled', $order->get_status() );
		$this->assertSame( 'canceled', $order->get_meta( '_intention_status', true ) );
		$this->assertSame( '', $order->get_meta( '_wcpay_transaction_fee', true ) );
		$this->assertSame( '', $order->get_meta( '_wcpay_net', true ) );
		$this->assertOrderHasNote( $order, 'Payment canceled.' );
	}

	/**
	 * @testdox Capture-expired events mark the order failed.
	 */
	public function test_capture_expired_event_marks_order_failed(): void {
		$order = $this->create_woopayments_order();

		$this->sut->apply(
			$order,
			new PaymentLifecycleEvent(
				PaymentLifecycleEvent::STATUS_CAPTURE_EXPIRED,
				'ch_expired',
				array( '_charge_id' => 'ch_expired' ),
				array(),
				'Payment authorization expired.'
			)
		);

		$order = wc_get_order( $order->get_id() );

		$this->assertInstanceOf( WC_Order::class, $order );
		$this->assertSame( 'failed', $order->get_status() );
		$this->assertSame( 'ch_expired', $order->get_meta( '_charge_id', true ) );
		$this->assertOrderHasNote( $order, 'Payment authorization expired.' );
	}

	/**
	 * @testdox Started events add a note without changing order status.
	 */
	public function test_started_event_adds_note_without_status_change(): void {
		$order = $this->create_woopayments_order();

		$this->sut->apply(
			$order,
			new PaymentLifecycleEvent(
				PaymentLifecycleEvent::STATUS_STARTED,
				'pi_started',
				array( '_intent_id' => 'pi_started' ),
				array(),
				'Payment started.'
			)
		);

		$order = wc_get_order( $order->get_id() );

		$this->assertInstanceOf( WC_Order::class, $order );
		$this->assertSame( 'pending', $order->get_status() );
		$this->assertSame( 'pi_started', $order->get_meta( '_intent_id', true ) );
		$this->assertOrderHasNote( $order, 'Payment started.' );
	}

	/**
	 * @testdox Duplicate lifecycle notes are not added twice.
	 */
	public function test_duplicate_note_is_not_added_twice(): void {
		$order = $this->create_woopayments_order();
		$event = new PaymentLifecycleEvent(
			PaymentLifecycleEvent::STATUS_STARTED,
			'pi_started',
			array( '_intent_id' => 'pi_started' ),
			array(),
			'Payment started.'
		);

		$this->sut->apply( $order, $event );
		$this->sut->apply( wc_get_order( $order->get_id() ), $event );

		$order = wc_get_order( $order->get_id() );

		$this->assertInstanceOf( WC_Order::class, $order );
		$this->assertSame( 1, $this->countOrderNotesMatching( $order, 'Payment started.' ) );
	}

	/**
	 * @testdox Locked orders are not mutated for the same payment reference.
	 */
	public function test_locked_order_is_not_mutated_for_same_reference(): void {
		$order = $this->create_woopayments_order();
		$this->order_payment_store->lock_order_payment( $order, 'pi_locked' );

		$this->sut->apply(
			$order,
			new PaymentLifecycleEvent(
				PaymentLifecycleEvent::STATUS_COMPLETED,
				'pi_locked',
				array( '_intent_id' => 'pi_locked' ),
				array(),
				'Payment complete.'
			)
		);

		$order = wc_get_order( $order->get_id() );

		$this->assertInstanceOf( WC_Order::class, $order );
		$this->assertSame( 'pending', $order->get_status() );
		$this->assertSame( '', $order->get_meta( '_intent_id', true ) );
		$this->assertSame( 0, $this->countOrderNotesMatching( $order, 'Payment complete.' ) );
		$this->order_payment_store->unlock_order_payment( $order );
	}

	/**
	 * @testdox Locked orders are not mutated or unlocked while another operation is active.
	 */
	public function test_locked_order_is_not_mutated_or_unlocked_for_active_operation(): void {
		$order = $this->create_woopayments_order();
		$this->assertTrue( $this->order_payment_store->claim_order_payment_lock( $order, 'native_charge_operation' ) );

		$this->sut->apply(
			$order,
			new PaymentLifecycleEvent(
				PaymentLifecycleEvent::STATUS_COMPLETED,
				'pi_webhook',
				array( '_intent_id' => 'pi_webhook' ),
				array(),
				'Payment complete.'
			)
		);

		$order = wc_get_order( $order->get_id() );

		$this->assertInstanceOf( WC_Order::class, $order );
		$this->assertSame( 'pending', $order->get_status() );
		$this->assertSame( '', $order->get_meta( '_intent_id', true ) );
		$this->assertSame( 0, $this->countOrderNotesMatching( $order, 'Payment complete.' ) );
		$this->assertTrue( $this->order_payment_store->is_order_payment_locked( $order, 'native_charge_operation' ) );
		$this->order_payment_store->unlock_order_payment( $order );
	}

	/**
	 * Create a WooPayments order for lifecycle tests.
	 *
	 * @return WC_Order
	 */
	private function create_woopayments_order(): WC_Order {
		$order = wc_create_order();
		$order->set_payment_method( OrderPaymentStore::GATEWAY_ID );
		$order->set_total( '10.00' );
		$order->save();

		return $order;
	}

	/**
	 * Assert that an order has a note with the expected exact content.
	 *
	 * @param WC_Order $order    Order object.
	 * @param string   $expected Expected note content.
	 */
	private function assertOrderHasNote( WC_Order $order, string $expected ): void {
		$this->assertGreaterThan( 0, $this->countOrderNotesMatching( $order, $expected ), "Missing order note: {$expected}" );
	}

	/**
	 * Count order notes with exact content.
	 *
	 * @param WC_Order $order    Order object.
	 * @param string   $expected Expected note content.
	 * @return int
	 */
	private function countOrderNotesMatching( WC_Order $order, string $expected ): int {
		$count = 0;
		$notes = wc_get_order_notes(
			array(
				'order_id' => $order->get_id(),
				'type'     => 'any',
			)
		);

		foreach ( $notes as $note ) {
			if ( $expected === $note->content ) {
				++$count;
			}
		}

		return $count;
	}
}
