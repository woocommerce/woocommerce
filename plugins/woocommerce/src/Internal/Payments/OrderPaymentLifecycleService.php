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
			if ( $this->order_payment_store->is_order_payment_locked( $order, $payment_reference ) ) {
				return;
			}

			$this->order_payment_store->lock_order_payment( $order, $payment_reference );
			$locked_by_service = true;
		}

		try {
			$this->apply_meta_changes( $order, $event );

			$note            = $event->get_note();
			$should_add_note = null !== $note && '' !== $note && ! $this->has_note_marker( $order, $event, $note );
			if ( $should_add_note ) {
				$order->update_meta_data( $this->get_note_marker_key( $event, $note ), 'yes' );
			}

			$status_transition_saved_order = $this->apply_status_transition( $order, $event );
			if ( ! $status_transition_saved_order ) {
				$order->save();
			}

			if ( $should_add_note ) {
				$order->add_order_note( $note );
			}
		} finally {
			if ( $locked_by_service ) {
				$this->order_payment_store->unlock_order_payment( $order );
			}
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
				$order->payment_complete( (string) $event->get_payment_reference() );
				return true;

			case PaymentLifecycleEvent::STATUS_AUTHORIZED:
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
