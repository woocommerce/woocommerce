<?php
/**
 * Class WC_Gateway_Paypal_Webhook_Handler file.
 *
 * @package WooCommerce\Gateways
 */

declare(strict_types=1);

use Automattic\WooCommerce\Enums\OrderStatus;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/class-wc-gateway-paypal-request.php';

/**
 * Handles webhook events.
 */
class WC_Gateway_Paypal_Webhook_Handler {

	/**
	 * Process the webhook event.
	 *
	 * @param WP_REST_Request $request The request object.
	 */
	public function process_webhook( WP_REST_Request $request ) {
		$data = $request->get_json_params();
		if ( ! is_array( $data ) || empty( $data['event_type'] ) || empty( $data['resource'] ) ) {
			WC_Gateway_Paypal::log( 'Invalid PayPal webhook payload: ' . wc_print_r( $data, true ) );
			return;
		}

		WC_Gateway_Paypal::log( 'Webhook received: ' . wc_print_r( $data, true ) );

		switch ( $data['event_type'] ) {
			case 'CHECKOUT.ORDER.APPROVED':
				$this->process_checkout_order_approved( $data );
				break;
			case 'PAYMENT.CAPTURE.COMPLETED':
				$this->process_payment_capture_completed( $data );
				break;
			case 'PAYMENT.AUTHORIZATION.CREATED':
				$this->process_payment_authorization_created( $data );
				break;
			default:
				WC_Gateway_Paypal::log( 'Unhandled PayPal webhook event: ' . wc_print_r( $data, true ) );
				break;
		}
	}

	/**
	 * Process the CHECKOUT.ORDER.APPROVED webhook event.
	 *
	 * @param array $event The webhook event data.
	 */
	private function process_checkout_order_approved( $event ) {
		$custom_id = $event['resource']['purchase_units'][0]['custom_id'] ?? '';
		$order     = $this->get_wc_order( $custom_id );
		if ( ! $order ) {
			WC_Gateway_Paypal::log( 'Invalid order. Custom ID: ' . wc_print_r( $custom_id, true ) );
			return;
		}

		// Skip if the payment is already processed.
		if ( WC_Gateway_Paypal_Constants::STATUS_COMPLETED === $order->get_meta( '_paypal_status', true ) ) {
			return;
		}

		$status          = $event['resource']['status'] ?? null;
		$paypal_order_id = $event['resource']['id'] ?? null;
		if ( 'APPROVED' === $status ) {
			WC_Gateway_Paypal::log( 'PayPal payment approved. Order ID: ' . $order->get_id() );
			$order->update_meta_data( '_paypal_status', $status );
			$order->add_order_note(
				sprintf(
					/* translators: %1$s: PayPal order ID */
					__( 'PayPal payment approved. PayPal Order ID: %1$s', 'woocommerce' ),
					$paypal_order_id
				)
			);
			$order->save();

			// Authorize or capture the payment after approval.
			$paypal_intent = $event['resource']['intent'] ?? null;
			$links         = $event['resource']['links'] ?? null;
			$action        = WC_Gateway_Paypal_Constants::INTENT_CAPTURE === $paypal_intent ? WC_Gateway_Paypal_Constants::PAYMENT_ACTION_CAPTURE : WC_Gateway_Paypal_Constants::PAYMENT_ACTION_AUTHORIZE;
			$this->authorize_or_capture_payment( $order, $links, $action );
		} else {
			// This is unexpected for a CHECKOUT.ORDER.APPROVED event.
			WC_Gateway_Paypal::log( 'PayPal payment approval failed. Order ID: ' . $order->get_id() . ' Status: ' . $status );
			$order->add_order_note(
				sprintf(
					/* translators: %1$s: PayPal order ID, %2$s: Status */
					__( 'PayPal payment approval failed. PayPal Order ID: %1$s. Status: %2$s', 'woocommerce' ),
					$paypal_order_id,
					$status
				)
			);
		}
	}

	/**
	 * Process the PAYMENT.CAPTURE.COMPLETED webhook event.
	 *
	 * @param array $event The webhook event data.
	 */
	private function process_payment_capture_completed( $event ) {
		$custom_id = $event['resource']['custom_id'] ?? '';
		$order     = $this->get_wc_order( $custom_id );
		if ( ! $order ) {
			WC_Gateway_Paypal::log( 'Invalid order. Custom ID: ' . wc_print_r( $custom_id, true ) );
			return;
		}

		// Skip if the payment is already processed.
		if ( WC_Gateway_Paypal_Constants::STATUS_COMPLETED === $order->get_meta( '_paypal_status', true ) ) {
			return;
		}

		$transaction_id = $event['resource']['id'] ?? null;
		$status         = $event['resource']['status'] ?? null;
		$order->set_transaction_id( $transaction_id );
		$order->update_meta_data( '_paypal_status', $status );
		$order->payment_complete();
		$order->add_order_note(
			sprintf(
				/* translators: %1$s: Transaction ID */
				__( 'PayPal payment captured. Transaction ID: %1$s.', 'woocommerce' ),
				$transaction_id
			)
		);
		$order->save();
	}

	/**
	 * Process the PAYMENT.AUTHORIZATION.CREATED webhook event.
	 *
	 * @param array $event The webhook event data.
	 */
	private function process_payment_authorization_created( $event ) {
		$custom_id = $event['resource']['custom_id'] ?? '';
		$order     = $this->get_wc_order( $custom_id );
		if ( ! $order ) {
			WC_Gateway_Paypal::log( 'Invalid order. Custom ID: ' . wc_print_r( $custom_id, true ) );
			return;
		}

		// Skip if the payment is already processed.
		if ( WC_Gateway_Paypal_Constants::STATUS_COMPLETED === $order->get_meta( '_paypal_status', true ) ) {
			return;
		}

		$transaction_id = $event['resource']['id'] ?? null;
		$order->set_transaction_id( $transaction_id );
		$order->add_order_note(
			sprintf(
				/* translators: %1$s: Transaction ID */
				__( 'PayPal payment authorized. Transaction ID: %1$s. Change payment status to processing or complete to capture funds.', 'woocommerce' ),
				$transaction_id
			)
		);
		$order->update_status( OrderStatus::ON_HOLD );
		$order->save();
	}

	/**
	 * Get the WC order from the custom ID.
	 *
	 * @param string $custom_id The custom ID string from the PayPal order.
	 * @return WC_Order|null
	 */
	private function get_wc_order( $custom_id ) {
		$data = json_decode( $custom_id, true );
		if ( ! is_array( $data ) ) {
			return null;
		}

		$order_id = $data['order_id'] ?? null;
		if ( ! $order_id ) {
			return null;
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return null;
		}

		// Validate the order key.
		$order_key = $data['order_key'] ?? null;
		if ( $order_key !== $order->get_order_key() ) {
			return null;
		}

		return $order;
	}

	/**
	 * Capture the payment.
	 *
	 * @param WC_Order $order The order object.
	 * @param array    $links The links from the webhook event.
	 * @param string   $action The action to perform (capture or authorize).
	 * @return void
	 */
	private function authorize_or_capture_payment( $order, $links, $action ) {
		$action_url = $this->get_action_url( $links, $action );

		$payment_gateways = WC()->payment_gateways()->payment_gateways();
		if ( ! isset( $payment_gateways['paypal'] ) ) {
			WC_Gateway_Paypal::log( 'PayPal gateway is not available.' );
			return;
		}
		$gateway        = $payment_gateways['paypal'];
		$paypal_request = new WC_Gateway_Paypal_Request( $gateway );
		$paypal_request->authorize_or_capture_payment( $order, $action_url, $action );
	}

	/**
	 * Get the action URL from the links.
	 *
	 * @param array  $links The links from the webhook event.
	 * @param string $action The action to perform (capture or authorize).
	 * @return string|null
	 */
	private function get_action_url( $links, $action ) {
		$action_url = null;
		foreach ( $links as $link ) {
			if ( $action === $link['rel'] && 'POST' === $link['method'] && filter_var( $link['href'], FILTER_VALIDATE_URL ) ) {
				$action_url = esc_url_raw( $link['href'] );
				break;
			}
		}
		return $action_url;
	}
}
