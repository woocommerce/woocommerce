<?php
/**
 * Fulfillment Order Notes.
 *
 * Adds order notes for fulfillment lifecycle events.
 *
 * @package WooCommerce\Internal\Fulfillments
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Fulfillments;

use Automattic\WooCommerce\Internal\Orders\OrderNoteGroup;

/**
 * FulfillmentOrderNotes class.
 *
 * Hooks into fulfillment lifecycle actions and adds filterable order notes
 * for fulfillment state changes.
 *
 * @since 10.7.0
 */
class FulfillmentOrderNotes {

	/**
	 * Stores the previous status of a fulfillment before update.
	 *
	 * @var array<int, string>
	 */
	private array $previous_statuses = array();

	/**
	 * Register hooks for fulfillment order notes.
	 */
	public function register(): void {
		add_action( 'woocommerce_fulfillment_after_create', array( $this, 'add_fulfillment_created_note' ), 10, 1 );
		add_filter( 'woocommerce_fulfillment_before_update', array( $this, 'capture_previous_status' ), 10, 1 );
		add_action( 'woocommerce_fulfillment_after_update', array( $this, 'add_fulfillment_updated_note' ), 10, 1 );
		add_action( 'woocommerce_fulfillment_after_delete', array( $this, 'add_fulfillment_deleted_note' ), 10, 1 );
	}

	/**
	 * Add an order note when a fulfillment is created.
	 *
	 * @param Fulfillment $fulfillment The fulfillment object.
	 */
	public function add_fulfillment_created_note( Fulfillment $fulfillment ): void {
		$order = $fulfillment->get_order();
		if ( ! $order instanceof \WC_Order ) {
			return;
		}

		$items_text    = $this->format_items( $fulfillment, $order );
		$tracking_text = $this->format_tracking( $fulfillment );
		$status        = $fulfillment->get_status() ?? 'unfulfilled';

		$message = sprintf(
			/* translators: 1: fulfillment ID, 2: fulfillment status, 3: item list */
			__( 'Fulfillment #%1$d created (status: %2$s). Items: %3$s.', 'woocommerce' ),
			$fulfillment->get_id(),
			$status,
			$items_text
		);

		if ( ! empty( $tracking_text ) ) {
			$message .= ' ' . sprintf(
				/* translators: %s: tracking number */
				__( 'Tracking: %s.', 'woocommerce' ),
				$tracking_text
			);
		}

		/**
		 * Filters the order note message when a fulfillment is created.
		 *
		 * Return null to cancel the note.
		 *
		 * @since 10.7.0
		 *
		 * @param string|null  $message     The note message.
		 * @param Fulfillment  $fulfillment The fulfillment object.
		 * @param \WC_Order    $order       The order object.
		 */
		$message = apply_filters( 'woocommerce_fulfillment_created_order_note', $message, $fulfillment, $order );
		if ( null === $message ) {
			return;
		}

		$order->add_order_note( $message, 0, false, array( 'note_group' => OrderNoteGroup::FULFILLMENT ) );
	}

	/**
	 * Capture the previous status of a fulfillment before update.
	 *
	 * This is hooked into `woocommerce_fulfillment_before_update` to record
	 * the old status so we can detect status changes in the after_update hook.
	 *
	 * @param Fulfillment $fulfillment The fulfillment object.
	 * @return Fulfillment The unmodified fulfillment object.
	 */
	public function capture_previous_status( Fulfillment $fulfillment ): Fulfillment {
		if ( $fulfillment->get_id() > 0 ) {
			$old_fulfillment                                   = new Fulfillment( (string) $fulfillment->get_id() );
			$this->previous_statuses[ $fulfillment->get_id() ] = $old_fulfillment->get_status() ?? 'unfulfilled';
		}
		return $fulfillment;
	}

	/**
	 * Add an order note when a fulfillment is updated.
	 *
	 * If the status changed, a status change note is added.
	 * Otherwise, a general update note is added.
	 *
	 * @param Fulfillment $fulfillment The fulfillment object.
	 */
	public function add_fulfillment_updated_note( Fulfillment $fulfillment ): void {
		$order = $fulfillment->get_order();
		if ( ! $order instanceof \WC_Order ) {
			return;
		}

		$fulfillment_id = $fulfillment->get_id();
		$old_status     = $this->previous_statuses[ $fulfillment_id ] ?? null;
		$new_status     = $fulfillment->get_status() ?? 'unfulfilled';

		// If status changed, add a status change note.
		if ( null !== $old_status && $old_status !== $new_status ) {
			$this->add_fulfillment_status_changed_note( $fulfillment, $order, $old_status, $new_status );
			unset( $this->previous_statuses[ $fulfillment_id ] );
			return;
		}

		unset( $this->previous_statuses[ $fulfillment_id ] );

		$items_text    = $this->format_items( $fulfillment, $order );
		$tracking_text = $this->format_tracking( $fulfillment );

		$message = sprintf(
			/* translators: 1: fulfillment ID, 2: item list */
			__( 'Fulfillment #%1$d updated. Items: %2$s.', 'woocommerce' ),
			$fulfillment->get_id(),
			$items_text
		);

		if ( ! empty( $tracking_text ) ) {
			$message .= ' ' . sprintf(
				/* translators: %s: tracking number */
				__( 'Tracking: %s.', 'woocommerce' ),
				$tracking_text
			);
		}

		/**
		 * Filters the order note message when a fulfillment is updated.
		 *
		 * Return null to cancel the note.
		 *
		 * @since 10.7.0
		 *
		 * @param string|null  $message     The note message.
		 * @param Fulfillment  $fulfillment The fulfillment object.
		 * @param \WC_Order    $order       The order object.
		 */
		$message = apply_filters( 'woocommerce_fulfillment_updated_order_note', $message, $fulfillment, $order );
		if ( null === $message ) {
			return;
		}

		$order->add_order_note( $message, 0, false, array( 'note_group' => OrderNoteGroup::FULFILLMENT ) );
	}

	/**
	 * Add an order note when a fulfillment is deleted.
	 *
	 * @param Fulfillment $fulfillment The fulfillment object.
	 */
	public function add_fulfillment_deleted_note( Fulfillment $fulfillment ): void {
		$order = $fulfillment->get_order();
		if ( ! $order instanceof \WC_Order ) {
			return;
		}

		$message = sprintf(
			/* translators: %d: fulfillment ID */
			__( 'Fulfillment #%d deleted.', 'woocommerce' ),
			$fulfillment->get_id()
		);

		/**
		 * Filters the order note message when a fulfillment is deleted.
		 *
		 * Return null to cancel the note.
		 *
		 * @since 10.7.0
		 *
		 * @param string|null  $message     The note message.
		 * @param Fulfillment  $fulfillment The fulfillment object.
		 * @param \WC_Order    $order       The order object.
		 */
		$message = apply_filters( 'woocommerce_fulfillment_deleted_order_note', $message, $fulfillment, $order );
		if ( null === $message ) {
			return;
		}

		$order->add_order_note( $message, 0, false, array( 'note_group' => OrderNoteGroup::FULFILLMENT ) );
	}

	/**
	 * Add an order note when the order fulfillment status changes.
	 *
	 * Called from FulfillmentsManager when the `_fulfillment_status` meta changes.
	 *
	 * @param \WC_Order $order      The order object.
	 * @param string    $old_status The previous fulfillment status.
	 * @param string    $new_status The new fulfillment status.
	 */
	public function add_order_fulfillment_status_changed_note( \WC_Order $order, string $old_status, string $new_status ): void {
		$message = sprintf(
			/* translators: 1: old fulfillment status, 2: new fulfillment status */
			__( 'Order fulfillment status changed from %1$s to %2$s.', 'woocommerce' ),
			$old_status,
			$new_status
		);

		/**
		 * Filters the order note message when the order fulfillment status changes.
		 *
		 * Return null to cancel the note.
		 *
		 * @since 10.7.0
		 *
		 * @param string|null $message    The note message.
		 * @param \WC_Order   $order      The order object.
		 * @param string      $old_status The previous fulfillment status.
		 * @param string      $new_status The new fulfillment status.
		 */
		$message = apply_filters( 'woocommerce_fulfillment_order_status_changed_order_note', $message, $order, $old_status, $new_status );
		if ( null === $message ) {
			return;
		}

		$order->add_order_note( $message, 0, false, array( 'note_group' => OrderNoteGroup::FULFILLMENT ) );
	}

	/**
	 * Add a status change note for a fulfillment.
	 *
	 * @param Fulfillment $fulfillment The fulfillment object.
	 * @param \WC_Order   $order       The order object.
	 * @param string      $old_status  The previous status.
	 * @param string      $new_status  The new status.
	 */
	private function add_fulfillment_status_changed_note( Fulfillment $fulfillment, \WC_Order $order, string $old_status, string $new_status ): void {
		$message = sprintf(
			/* translators: 1: fulfillment ID, 2: old status, 3: new status */
			__( 'Fulfillment #%1$d status changed from %2$s to %3$s.', 'woocommerce' ),
			$fulfillment->get_id(),
			$old_status,
			$new_status
		);

		/**
		 * Filters the order note message when a fulfillment status changes.
		 *
		 * Return null to cancel the note.
		 *
		 * @since 10.7.0
		 *
		 * @param string|null  $message     The note message.
		 * @param Fulfillment  $fulfillment The fulfillment object.
		 * @param \WC_Order    $order       The order object.
		 * @param string       $old_status  The previous status.
		 * @param string       $new_status  The new status.
		 */
		$message = apply_filters( 'woocommerce_fulfillment_status_changed_order_note', $message, $fulfillment, $order, $old_status, $new_status );
		if ( null === $message ) {
			return;
		}

		$order->add_order_note( $message, 0, false, array( 'note_group' => OrderNoteGroup::FULFILLMENT ) );
	}

	/**
	 * Format fulfillment items as a comma-separated string.
	 *
	 * @param Fulfillment $fulfillment The fulfillment object.
	 * @param \WC_Order   $order       The order object.
	 * @return string Formatted items string.
	 */
	private function format_items( Fulfillment $fulfillment, \WC_Order $order ): string {
		$items       = $fulfillment->get_items();
		$order_items = $order->get_items();
		$parts       = array();

		foreach ( $items as $item ) {
			$item_id = $item['item_id'] ?? 0;
			$qty     = $item['qty'] ?? 0;
			$name    = '';

			foreach ( $order_items as $order_item ) {
				if ( $order_item->get_id() === $item_id ) {
					$name = $order_item->get_name();
					break;
				}
			}

			if ( empty( $name ) ) {
				$name = sprintf(
					/* translators: %d: item ID */
					__( 'Item #%d', 'woocommerce' ),
					$item_id
				);
			}

			$parts[] = sprintf( '%s x%s', $name, $qty );
		}

		return implode( ', ', $parts );
	}

	/**
	 * Format the tracking number from a fulfillment.
	 *
	 * @param Fulfillment $fulfillment The fulfillment object.
	 * @return string The tracking number, or empty string if not present.
	 */
	private function format_tracking( Fulfillment $fulfillment ): string {
		$tracking_number = $fulfillment->get_meta( '_tracking_number', true );
		return is_string( $tracking_number ) ? $tracking_number : '';
	}
}
