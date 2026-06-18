<?php
/**
 * OrderPaymentLifecycleService class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Payments;

use WC_Order;

/**
 * Applies neutral payment lifecycle effects to WooCommerce orders.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native payments runtime.
 */
class OrderPaymentLifecycleService {

	/**
	 * Order payment store.
	 *
	 * @var OrderPaymentStore
	 */
	private OrderPaymentStore $order_payment_store;

	/**
	 * Initialize the class instance.
	 *
	 * @internal
	 *
	 * @param OrderPaymentStore $order_payment_store Order payment store.
	 */
	final public function init( OrderPaymentStore $order_payment_store ): void {
		$this->order_payment_store = $order_payment_store;
	}

	/**
	 * Apply a payment lifecycle event to an order.
	 *
	 * @since 11.0.0
	 *
	 * @param WC_Order              $order Order object.
	 * @param PaymentLifecycleEvent $event Lifecycle event.
	 */
	public function apply( WC_Order $order, PaymentLifecycleEvent $event ): void {
		$payment_reference = $event->get_payment_reference();
		$locked_by_service = false;

		if ( null !== $payment_reference ) {
			if ( ! $this->order_payment_store->claim_order_payment_lock( $order, $payment_reference ) ) {
				return;
			}

			$locked_by_service = true;
		}

		try {
			$this->apply_unlocked( $order, $event );
		} finally {
			if ( $locked_by_service ) {
				$this->order_payment_store->unlock_order_payment( $order );
			}
		}
	}

	/**
	 * Apply a payment lifecycle event while the caller already owns the order payment lock.
	 *
	 * @since 11.0.0
	 *
	 * @param WC_Order              $order Order object.
	 * @param PaymentLifecycleEvent $event Lifecycle event.
	 */
	public function apply_unlocked( WC_Order $order, PaymentLifecycleEvent $event ): void {
		$this->apply_meta_changes( $order, $event );

		$note            = $event->get_note();
		$should_add_note = null !== $note && '' !== $note && ! $this->should_skip_lifecycle_note( $order, $event, $note ) && ! $this->has_note_marker( $order, $event, $note );
		if ( $should_add_note ) {
			$order->update_meta_data( $this->get_note_marker_key( $event, $note ), 'yes' );
		}

		$order->save_meta_data();

		$status_transition_saved_order = $this->apply_status_transition( $order, $event );
		if ( ! $status_transition_saved_order ) {
			$order->save();
		}

		if ( $should_add_note ) {
			$order->add_order_note( $note );
		}
	}

	/**
	 * Apply meta updates and deletes.
	 *
	 * @param WC_Order              $order Order object.
	 * @param PaymentLifecycleEvent $event Lifecycle event.
	 */
	private function apply_meta_changes( WC_Order $order, PaymentLifecycleEvent $event ): void {
		foreach ( $event->get_meta_to_update() as $key => $value ) {
			$order->update_meta_data( $key, $value );
		}

		foreach ( $event->get_meta_to_delete() as $key ) {
			$order->delete_meta_data( $key );
		}
	}

	/**
	 * Apply the WooCommerce order status transition for a lifecycle event.
	 *
	 * @param WC_Order              $order Order object.
	 * @param PaymentLifecycleEvent $event Lifecycle event.
	 * @return bool True when the transition saved the order.
	 */
	private function apply_status_transition( WC_Order $order, PaymentLifecycleEvent $event ): bool {
		switch ( $event->get_status() ) {
			case PaymentLifecycleEvent::STATUS_COMPLETED:
				return (bool) $order->payment_complete( (string) $event->get_payment_reference() );

			case PaymentLifecycleEvent::STATUS_AUTHORIZED:
				if ( null !== $event->get_payment_reference() && '' !== (string) $event->get_payment_reference() ) {
					$order->set_transaction_id( (string) $event->get_payment_reference() );
				}
				$order->update_status( 'on-hold' );
				return true;

			case PaymentLifecycleEvent::STATUS_FAILED:
			case PaymentLifecycleEvent::STATUS_CAPTURE_EXPIRED:
				$order->update_status( 'failed' );
				return true;

			case PaymentLifecycleEvent::STATUS_CANCELED:
				$order->update_status( 'cancelled' );
				return true;

			case PaymentLifecycleEvent::STATUS_STARTED:
				return false;
		}

		return false;
	}

	/**
	 * Tell whether the lifecycle note marker is already present.
	 *
	 * @param WC_Order              $order Order object.
	 * @param PaymentLifecycleEvent $event Lifecycle event.
	 * @param string                $note  Note content.
	 * @return bool
	 */
	private function has_note_marker( WC_Order $order, PaymentLifecycleEvent $event, string $note ): bool {
		return 'yes' === $order->get_meta( $this->get_note_marker_key( $event, $note ), true );
	}

	/**
	 * Tell whether a lifecycle note should be skipped for an already-applied event.
	 *
	 * @param WC_Order              $order Order object.
	 * @param PaymentLifecycleEvent $event Lifecycle event.
	 * @param string                $note  Note content.
	 * @return bool
	 */
	private function should_skip_lifecycle_note( WC_Order $order, PaymentLifecycleEvent $event, string $note ): bool {
		$payment_reference = (string) $event->get_payment_reference();

		if ( 0 === strpos( $note, '<strong>Fee details:</strong>' ) && $this->has_fee_details_note( $order ) ) {
			return true;
		}

		return PaymentLifecycleEvent::STATUS_COMPLETED === $event->get_status()
			&& 'Payment complete.' === $note
			&& '' !== $payment_reference
			&& $payment_reference === (string) $order->get_transaction_id()
			&& $order->has_status( array( 'processing', 'completed' ) );
	}

	/**
	 * Tell whether the order already has a fee-details order note.
	 *
	 * @param WC_Order $order Order object.
	 * @return bool
	 */
	private function has_fee_details_note( WC_Order $order ): bool {
		$notes = wc_get_order_notes(
			array(
				'order_id' => $order->get_id(),
				'type'     => 'any',
			)
		);

		foreach ( $notes as $note ) {
			if ( 0 === strpos( (string) $note->content, '<strong>Fee details:</strong>' ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get the internal idempotency marker key for a lifecycle note.
	 *
	 * @param PaymentLifecycleEvent $event Lifecycle event.
	 * @param string                $note  Note content.
	 * @return string
	 */
	private function get_note_marker_key( PaymentLifecycleEvent $event, string $note ): string {
		return '_wc_native_payments_note_' . md5( (string) $event->get_payment_reference() . '|' . $event->get_status() . '|' . $note );
	}
}
