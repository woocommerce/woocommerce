<?php
/**
 * Class WC_Gateway_Paypal_Webhook_Handler file.
 *
 * @package WooCommerce\Gateways
 */

declare(strict_types=1);

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
		// phpcs:disable Generic.Commenting.Todo.TaskFound
		// TODO: Validate the webhook signature.

		$data = $request->get_json_params();
		WC_Gateway_Paypal::log( 'Webhook received: ' . wc_print_r( $data, true ) );

		switch ( $data['event_type'] ) {
			case 'CHECKOUT.ORDER.APPROVED':
				$this->process_checkout_order_approved( $data );
				break;
			case 'PAYMENT.CAPTURE.COMPLETED':
				$this->process_payment_capture_completed( $data );
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
		$custom_id = $event['resource']['purchase_units'][0]['custom_id'];
		$order     = $this->get_wc_order( $custom_id );
		if ( ! $order ) {
			WC_Gateway_Paypal::log( 'Invalid order. Custom ID: ' . wc_print_r( $custom_id, true ) );
			return;
		}

		$status          = $event['resource']['status'] ?? null;
		$paypal_order_id = $event['resource']['id'] ?? null;
		if ( 'APPROVED' === $status ) {
			WC_Gateway_Paypal::log( 'PayPal payment approved. Order ID: ' . $order->get_id() );
			$order->add_order_note(
				sprintf(
					/* translators: %1$s: PayPal order ID */
					__( 'PayPal payment approved. PayPal Order ID: %1$s', 'woocommerce' ),
					$paypal_order_id
				)
			);

			// Capture the payment after approval.
			$this->capture_payment( $order, $event['resource']['links'] );
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
		$custom_id = $event['resource']['custom_id'];
		$order     = $this->get_wc_order( $custom_id );
		if ( ! $order ) {
			WC_Gateway_Paypal::log( 'Invalid order. Custom ID: ' . wc_print_r( $custom_id, true ) );
			return;
		}

		$order->set_transaction_id( $event['resource']['id'] );
		$order->payment_complete();
		$order->add_order_note( 'PayPal payment captured. ID: ' . $event['resource']['id'] );
		$order->save();
	}

	/**
	 * Get the WC order from the custom ID.
	 *
	 * @param string $custom_id The custom ID string from the PayPal order.
	 * @return WC_Order|null
	 */
	private function get_wc_order( $custom_id ) {
		$data     = json_decode( $custom_id );
		$order_id = $data->order_id ?? null;
		if ( ! $order_id ) {
			return null;
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return null;
		}

		// Validate the order key.
		$order_key = $data->order_key ?? null;
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
	 */
	private function capture_payment( $order, $links ) {
		$capture_url = null;
		foreach ( $links as $link ) {
			if ( 'capture' === $link['rel'] && 'POST' === $link['method'] && filter_var( $link['href'], FILTER_VALIDATE_URL ) ) {
				$capture_url = esc_url_raw( $link['href'] );
				break;
			}
		}

		$payment_gateways = WC()->payment_gateways()->payment_gateways();
		if ( ! isset( $payment_gateways['paypal'] ) ) {
			WC_Gateway_Paypal::log( 'PayPal gateway is not available.' );
			return;
		}
		$gateway        = $payment_gateways['paypal'];
		$paypal_request = new WC_Gateway_Paypal_Request( $gateway );
		$paypal_request->capture_payment( $order, $capture_url );
	}
}
