<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\Admin\Agentic;

use Automattic\WooCommerce\Enums\OrderStatus;
use WC_Order;
use WC_Order_Refund;

/**
 * AgenticWebhookPayloadBuilder class
 *
 * Builds webhook payloads for the Agentic Commerce Protocol following
 * the specification for order lifecycle events.
 *
 * @since 10.3.0
 */
class AgenticWebhookPayloadBuilder {
	/**
	 * Build the webhook payload for an order event.
	 *
	 * @param string   $event Event type ('order_create' or 'order_update').
	 * @param WC_Order $order Order object.
	 * @return array Webhook payload.
	 */
	public function build_payload( $event, $order ) {
		return array(
			'type' => $event,
			'data' => $this->build_order_data( $order ),
		);
	}

	/**
	 * Build the order data for the webhook payload.
	 *
	 * @param WC_Order $order Order object.
	 * @return array Order data.
	 */
	private function build_order_data( $order ) {
		return array(
			'type'                => 'order',
			'checkout_session_id' => $this->get_checkout_session_id( $order ),
			'permalink_url'       => $this->get_order_permalink( $order ),
			'status'              => $this->map_order_status( $order->get_status() ),
			'refunds'             => $this->build_refunds_data( $order ),
		);
	}

	/**
	 * Get the checkout session ID from the order.
	 *
	 * @param WC_Order $order Order object.
	 * @return string Checkout session ID.
	 */
	private function get_checkout_session_id( $order ) {
		$session_id = $order->get_meta( '_agentic_checkout_session_id' );
		return ! empty( $session_id ) ? $session_id : 'checkout_session_' . $order->get_id();
	}

	/**
	 * Get the order permalink URL.
	 *
	 * @param WC_Order $order Order object.
	 * @return string Order permalink URL.
	 */
	private function get_order_permalink( $order ) {
		// Try to get the order received page URL (customer-facing)
		$order_received_url = $order->get_checkout_order_received_url();

		if ( ! empty( $order_received_url ) ) {
			return $order_received_url;
		}

		// Fallback to admin edit URL if customer URL not available
		return admin_url( 'post.php?post=' . $order->get_id() . '&action=edit' );
	}

	/**
	 * Map WooCommerce order status to ACP status.
	 *
	 * ACP statuses: created, manual_review, confirmed, canceled, shipped, fulfilled
	 *
	 * @param string $wc_status WooCommerce order status.
	 * @return string ACP status.
	 */
	private function map_order_status( $wc_status ) {
		// Remove 'wc-' prefix if present
		$wc_status = str_replace( 'wc-', '', $wc_status );

		$status_map = array(
			// WooCommerce status => ACP status
			'pending'        => 'created',
			'processing'     => 'confirmed',
			'on-hold'        => 'manual_review',
			'completed'      => 'fulfilled',
			'cancelled'      => 'canceled',
			'canceled'       => 'canceled', // Support both spellings
			'refunded'       => 'fulfilled', // Refunded orders are still fulfilled
			'failed'         => 'canceled',
			'checkout-draft' => 'created',
		);

		// Check if status exists in map
		if ( isset( $status_map[ $wc_status ] ) ) {
			return $status_map[ $wc_status ];
		}

		// Default to 'created' for unknown statuses
		return 'created';
	}

	/**
	 * Build refunds data for the order.
	 *
	 * @param WC_Order $order Order object.
	 * @return array Array of refunds.
	 */
	private function build_refunds_data( $order ) {
		$refunds_data = array();
		$refunds      = $order->get_refunds();

		if ( empty( $refunds ) ) {
			return $refunds_data;
		}

		foreach ( $refunds as $refund ) {
			$refunds_data[] = $this->build_single_refund_data( $refund );
		}

		return $refunds_data;
	}

	/**
	 * Build data for a single refund.
	 *
	 * @param WC_Order_Refund $refund Refund object.
	 * @return array Refund data.
	 */
	private function build_single_refund_data( $refund ) {
		$refund_type = $this->determine_refund_type( $refund );
		$amount      = abs( (float) $refund->get_total() ); // Get absolute value as refunds are negative

		return array(
			'type'   => $refund_type,
			'amount' => $this->format_amount( $amount ),
		);
	}

	/**
	 * Determine the refund type.
	 *
	 * @param WC_Order_Refund $refund Refund object.
	 * @return string Refund type ('store_credit' or 'original_payment').
	 */
	private function determine_refund_type( $refund ) {
		// Check if refund has a specific type meta
		$refund_type = $refund->get_meta( '_refund_type' );

		if ( 'store_credit' === $refund_type ) {
			return 'store_credit';
		}

		// Check refund reason for store credit indicators
		$reason = strtolower( $refund->get_reason() );
		if ( strpos( $reason, 'store credit' ) !== false || strpos( $reason, 'credit' ) !== false ) {
			return 'store_credit';
		}

		// Default to original payment method
		return 'original_payment';
	}

	/**
	 * Format amount for the payload.
	 *
	 * The spec shows amounts as strings in the example, but the schema defines them as integers.
	 * We'll use string format to match the examples and preserve decimal precision.
	 *
	 * @param float $amount Amount to format.
	 * @return string Formatted amount.
	 */
	private function format_amount( $amount ) {
		return number_format( $amount, 2, '.', '' );
	}

	/**
	 * Add additional order metadata that might be useful.
	 * This is for extensibility - the spec allows additional properties.
	 *
	 * @param WC_Order $order Order object.
	 * @return array Additional metadata.
	 */
	private function get_additional_metadata( $order ) {
		return array(
			'order_id'       => $order->get_id(),
			'order_key'      => $order->get_order_key(),
			'currency'       => $order->get_currency(),
			'total'          => $this->format_amount( $order->get_total() ),
			'customer_email' => $order->get_billing_email(),
			'created_via'    => $order->get_created_via(),
			'date_created'   => $order->get_date_created() ? $order->get_date_created()->format( 'c' ) : null,
			'date_modified'  => $order->get_date_modified() ? $order->get_date_modified()->format( 'c' ) : null,
		);
	}
}
