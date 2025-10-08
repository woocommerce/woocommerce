<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\Admin\Agentic;

use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\StoreApi\Routes\V1\Agentic\Enums\Specs\OrderStatus as ACPOrderStatus;
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
		// Try to get the order received page URL (customer-facing).
		$order_received_url = $order->get_checkout_order_received_url();

		if ( ! empty( $order_received_url ) ) {
			return $order_received_url;
		}

		// Fallback to admin edit URL if customer URL not available.
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
		// Remove 'wc-' prefix if present.
		$wc_status = str_replace( 'wc-', '', $wc_status );

		$status_map = array(
			// WooCommerce status => ACP status.
			'pending'        => ACPOrderStatus::CREATED,
			'processing'     => ACPOrderStatus::CONFIRMED,
			'on-hold'        => ACPOrderStatus::MANUAL_REVIEW,
			'completed'      => ACPOrderStatus::FULFILLED,
			'cancelled'      => ACPOrderStatus::CANCELED,
			'canceled'       => ACPOrderStatus::CANCELED, // Support both spellings.
			'refunded'       => ACPOrderStatus::FULFILLED, // Refunded orders are still fulfilled.
			'failed'         => ACPOrderStatus::CANCELED,
			'checkout-draft' => ACPOrderStatus::CREATED,
		);

		/**
		 * Filter the WooCommerce to ACP order status mapping.
		 *
		 * Allows extensions to map custom WooCommerce order statuses to ACP order statuses.
		 * The mapped status must be one of: created, manual_review, confirmed, canceled, shipped, fulfilled.
		 *
		 * @since 10.3.0
		 *
		 * @param array  $status_map Associative array of WooCommerce status => ACP status.
		 * @param string $wc_status  The WooCommerce order status being mapped.
		 */
		$status_map = apply_filters( 'woocommerce_agentic_webhook_order_status_map', $status_map, $wc_status );

		// Get mapped status or default to 'created'.
		$mapped_status = isset( $status_map[ $wc_status ] ) ? $status_map[ $wc_status ] : ACPOrderStatus::CREATED;

		// Validate the mapped status is a valid ACP status.
		if ( ! ACPOrderStatus::is_valid( $mapped_status ) ) {
			// Log a warning for invalid status but continue with fallback.
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log(
				sprintf(
					'Invalid ACP order status "%s" returned by woocommerce_agentic_webhook_order_status_map filter for WooCommerce status "%s". Using "created" as fallback.',
					$mapped_status,
					$wc_status
				)
			);
			return ACPOrderStatus::CREATED;
		}

		return $mapped_status;
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
		$amount      = abs( (float) $refund->get_total() ); // Get absolute value as refunds are negative.

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
		// Default to original payment method.
		$refund_type = 'original_payment';

		/**
		 * Filter the refund type for Agentic webhooks.
		 *
		 * This allows extensions to specify when a refund is store credit.
		 * By default, all refunds are assumed to be original payment method.
		 *
		 * @since 10.4.0
		 * @param string          $refund_type The refund type ('store_credit' or 'original_payment').
		 * @param WC_Order_Refund $refund      The refund object.
		 */
		return apply_filters( 'woocommerce_agentic_webhook_refund_type', $refund_type, $refund );
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
}
