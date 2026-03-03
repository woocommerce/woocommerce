<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\Admin\Agentic;

use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\StoreApi\Formatters\MoneyFormatter;
use Automattic\WooCommerce\StoreApi\Routes\V1\Agentic\Enums\OrderMetaKey;
use Automattic\WooCommerce\Internal\Agentic\Enums\Specs\OrderStatus as ACPOrderStatus;
use Automattic\WooCommerce\Internal\Agentic\Enums\Specs\RefundType;
use WC_Logger_Interface;
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
	 * Money formatter instance.
	 *
	 * @var MoneyFormatter
	 */
	private $money_formatter;

	/**
	 * Dependency initialization.
	 *
	 * @internal
	 */
	final public function init() {
		$this->money_formatter = new MoneyFormatter();
	}

	/**
	 * Build the webhook payload for an order event.
	 *
	 * @param string   $event Event type ('order_create' or 'order_update').
	 * @param WC_Order $order Order object.
	 * @return array Webhook payload.
	 */
	public function build_payload( string $event, WC_Order $order ): array {
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
	private function build_order_data( WC_Order $order ): array {
		return array(
			'type'                => 'order',
			'checkout_session_id' => $order->get_meta( OrderMetaKey::AGENTIC_CHECKOUT_SESSION_ID ),
			'permalink_url'       => $order->get_checkout_order_received_url(),
			'status'              => $this->map_order_status( $order->get_status() ),
			'refunds'             => $this->build_refunds_data( $order ),
		);
	}

	/**
	 * Map WooCommerce order status to ACP status.
	 *
	 * ACP statuses: created, manual_review, confirmed, canceled, shipped, fulfilled
	 *
	 * @param string $wc_status WooCommerce order status.
	 * @return string ACP status.
	 */
	private function map_order_status( string $wc_status ): string {
		$status_map = array(
			// WooCommerce status => ACP status.
			OrderStatus::PENDING    => ACPOrderStatus::CREATED,
			OrderStatus::PROCESSING => ACPOrderStatus::CONFIRMED,
			OrderStatus::ON_HOLD    => ACPOrderStatus::MANUAL_REVIEW,
			OrderStatus::COMPLETED  => ACPOrderStatus::FULFILLED,
			OrderStatus::CANCELLED  => ACPOrderStatus::CANCELED,
			OrderStatus::REFUNDED   => ACPOrderStatus::FULFILLED, // Refunded orders are still fulfilled.
			OrderStatus::FAILED     => ACPOrderStatus::CANCELED,
		);

		/**
		 * Filter the WooCommerce to ACP order status mapping.
		 *
		 * Allows extensions to map custom WooCommerce order statuses to ACP order statuses.
		 * The mapped status must be one of: created, manual_review, confirmed, canceled, shipped, fulfilled.
		 *
		 * @see Automattic\WooCommerce\Internal\Agentic\Enums\Specs\OrderStatus
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
			wc_get_logger()->warning(
				sprintf(
					'Invalid ACP order status "%s" returned by woocommerce_agentic_webhook_order_status_map filter for WooCommerce status "%s". Using "created" as fallback.',
					$mapped_status,
					$wc_status
				),
				array( 'source' => 'agentic-webhooks' )
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
	private function build_refunds_data( WC_Order $order ): array {
		return array_map(
			array( $this, 'build_single_refund_data' ),
			$order->get_refunds()
		);
	}

	/**
	 * Build data for a single refund.
	 *
	 * @param WC_Order_Refund $refund Refund object.
	 * @return array Refund data.
	 */
	private function build_single_refund_data( WC_Order_Refund $refund ): array {
		$refund_type = $this->determine_refund_type( $refund );
		$amount      = abs( (float) $refund->get_total() ); // Get absolute value as refunds are negative.

		// Convert amount to minor units using MoneyFormatter (respects store currency decimals).
		$amount_in_minor_units = (int) $this->money_formatter->format( $amount );

		return array(
			'type'   => $refund_type,
			'amount' => $amount_in_minor_units,
		);
	}

	/**
	 * Determine the refund type.
	 *
	 * @param WC_Order_Refund $refund Refund object.
	 * @return string Refund type ('store_credit' or 'original_payment').
	 */
	private function determine_refund_type( WC_Order_Refund $refund ): string {
		// Default to original payment method.
		$refund_type = RefundType::ORIGINAL_PAYMENT;

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
}
