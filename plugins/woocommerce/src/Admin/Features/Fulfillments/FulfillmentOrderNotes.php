<?php
/**
 * Fulfillment Order Notes.
 *
 * Adds order notes for fulfillment lifecycle events.
 *
 * @package WooCommerce\Admin\Features\Fulfillments
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Admin\Features\Fulfillments;

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
	 * Register hooks for fulfillment order notes.
	 */
	public function register(): void {
		add_action( 'woocommerce_fulfillment_after_create', array( $this, 'add_fulfillment_created_note' ), 10, 1 );
		add_action( 'woocommerce_fulfillment_after_update', array( $this, 'add_fulfillment_updated_note' ), 10, 3 );
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
		$status_label  = $this->get_fulfillment_status_label( $status );

		$message = sprintf(
			/* translators: 1: fulfillment ID, 2: fulfillment status label, 3: item list */
			__( 'Fulfillment #%1$d created (status: %2$s). Items: %3$s.', 'woocommerce' ),
			$fulfillment->get_id(),
			$status_label,
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
		$message = $this->normalize_note_message( $message );

		if ( null === $message ) {
			return;
		}

		$order->add_order_note( $message, 0, false, array( 'note_group' => OrderNoteGroup::FULFILLMENT ) );
	}

	/**
	 * Add an order note when a fulfillment is updated.
	 *
	 * Only adds a note when tracked properties change (status, items,
	 * tracking number, tracking URL, shipping provider). If the status
	 * changed, a dedicated status change note is added instead.
	 *
	 * @param Fulfillment $fulfillment     The fulfillment object (post-update).
	 * @param array       $changes         Changes as returned by Fulfillment::get_changes() before
	 *                                     save. Core data props at top level, meta under 'meta_data'.
	 * @param string      $previous_status The fulfillment status before the update.
	 */
	public function add_fulfillment_updated_note( Fulfillment $fulfillment, array $changes = array(), string $previous_status = 'unfulfilled' ): void {
		if ( empty( $changes ) ) {
			return;
		}

		$order = $fulfillment->get_order();
		if ( ! $order instanceof \WC_Order ) {
			return;
		}

		// If status changed, add a dedicated status change note.
		if ( array_key_exists( 'status', $changes ) ) {
			$new_status = $changes['status'] ?? 'unfulfilled';
			$this->add_fulfillment_status_changed_note( $fulfillment, $order, $previous_status, $new_status );
			return;
		}

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
		$message = $this->normalize_note_message( $message );

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
		$message = $this->normalize_note_message( $message );

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
		$old_status_label = $this->get_order_fulfillment_status_label( $old_status );
		$new_status_label = $this->get_order_fulfillment_status_label( $new_status );

		$message = sprintf(
			/* translators: 1: old fulfillment status label, 2: new fulfillment status label */
			__( 'Order fulfillment status changed from %1$s to %2$s.', 'woocommerce' ),
			$old_status_label,
			$new_status_label
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
		$message = $this->normalize_note_message( $message );

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
		$old_status_label = $this->get_fulfillment_status_label( $old_status );
		$new_status_label = $this->get_fulfillment_status_label( $new_status );

		$message = sprintf(
			/* translators: 1: fulfillment ID, 2: old status label, 3: new status label */
			__( 'Fulfillment #%1$d status changed from %2$s to %3$s.', 'woocommerce' ),
			$fulfillment->get_id(),
			$old_status_label,
			$new_status_label
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
		$message = $this->normalize_note_message( $message );

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
			$item_id = isset( $item['item_id'] ) ? (int) $item['item_id'] : 0;
			$qty     = isset( $item['qty'] ) ? (int) $item['qty'] : 0;
			$name    = '';

			foreach ( $order_items as $order_item ) {
				if ( (int) $order_item->get_id() === $item_id ) {
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
	 * Format the tracking information from a fulfillment.
	 *
	 * Includes the tracking number, shipping provider, and tracking URL when available.
	 *
	 * @param Fulfillment $fulfillment The fulfillment object.
	 * @return string The formatted tracking information, or empty string if no tracking number is present.
	 */
	private function format_tracking( Fulfillment $fulfillment ): string {
		$tracking_number   = $fulfillment->get_tracking_number();
		$shipping_provider = $fulfillment->get_shipment_provider();
		$tracking_url      = $fulfillment->get_tracking_url();

		if ( null === $tracking_number ) {
			return '';
		}

		$parts = array( $tracking_number );

		if ( null !== $shipping_provider ) {
			$parts[] = sprintf(
				/* translators: %s: shipping provider name */
				__( 'Provider: %s', 'woocommerce' ),
				$shipping_provider
			);
		}

		if ( null !== $tracking_url ) {
			$parts[] = sprintf(
				/* translators: %s: tracking URL */
				__( 'URL: %s', 'woocommerce' ),
				$tracking_url
			);
		}

		return implode( ', ', $parts );
	}

	/**
	 * Get the display label for a fulfillment status key.
	 *
	 * @param string $status The fulfillment status key.
	 * @return string The status label, or the key itself if no label is found.
	 */
	private function get_fulfillment_status_label( string $status ): string {
		$statuses = FulfillmentUtils::get_fulfillment_statuses();
		return $statuses[ $status ]['label'] ?? $status;
	}

	/**
	 * Get the display label for an order fulfillment status key.
	 *
	 * @param string $status The order fulfillment status key.
	 * @return string The status label, or the key itself if no label is found.
	 */
	private function get_order_fulfillment_status_label( string $status ): string {
		$statuses = FulfillmentUtils::get_order_fulfillment_statuses();
		return $statuses[ $status ]['label'] ?? $status;
	}

	/**
	 * Sanitize an order note message.
	 *
	 * Ensures the message is a string and strips any disallowed HTML tags.
	 *
	 * @param mixed $message The original message.
	 * @return string|null The sanitized message, or null if the message is not valid.
	 */
	private function normalize_note_message( $message ): ?string {
		if ( ! $message || ! is_string( $message ) ) {
			return null;
		}

		$message = wp_kses_post( $message );
		$message = trim( $message );

		if ( '' === $message ) {
			return null;
		}

		return $message;
	}
}
