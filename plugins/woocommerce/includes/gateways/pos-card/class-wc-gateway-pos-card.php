<?php
/**
 * Class WC_Gateway_POS_Card file.
 *
 * @package WooCommerce\Gateways
 */

use Automattic\WooCommerce\StoreApi\POSSessionHandler;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * POS Card Payment Gateway.
 *
 * A payment gateway for Point of Sale card-present transactions. This gateway
 * handles a two-step flow:
 * 1. First checkout call creates the order (no payment_intent_id) - order stays pending
 * 2. Second checkout call captures payment (with payment_intent_id) - order completed
 *
 * Payment capture is delegated to WooPayments via its capture_terminal_payment endpoint.
 *
 * @class       WC_Gateway_POS_Card
 * @extends     WC_Payment_Gateway
 * @package     WooCommerce\Classes\Payment
 */
class WC_Gateway_POS_Card extends WC_Payment_Gateway {

	/**
	 * Unique ID for this gateway.
	 *
	 * @var string
	 */
	const ID = 'pos_card';

	/**
	 * Constructor for the gateway.
	 */
	public function __construct() {
		$this->id                 = self::ID;
		$this->icon               = '';
		$this->has_fields         = false;
		$this->method_title       = __( 'POS Card', 'woocommerce' );
		$this->method_description = __( 'Accept card payments at Point of Sale via Stripe Terminal. This gateway is only available for POS checkout sessions.', 'woocommerce' );

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		$this->title       = $this->get_option( 'title', __( 'Card', 'woocommerce' ) );
		$this->description = $this->get_option( 'description', '' );

		// This gateway doesn't need to be enabled in settings - it's auto-available for POS.
		$this->enabled = 'yes';

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
	}

	/**
	 * Initialise Gateway Settings Form Fields.
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'title'       => array(
				'title'       => __( 'Title', 'woocommerce' ),
				'type'        => 'safe_text',
				'description' => __( 'Payment method title shown during POS checkout.', 'woocommerce' ),
				'default'     => __( 'Card', 'woocommerce' ),
				'desc_tip'    => true,
			),
			'description' => array(
				'title'       => __( 'Description', 'woocommerce' ),
				'type'        => 'textarea',
				'description' => __( 'Payment method description shown during POS checkout.', 'woocommerce' ),
				'default'     => '',
				'desc_tip'    => true,
			),
		);
	}

	/**
	 * Check if the gateway is available for use.
	 *
	 * This gateway is ONLY available for POS sessions.
	 *
	 * @return bool
	 */
	public function is_available() {
		return wc_is_pos_session();
	}

	/**
	 * Process the payment and return the result.
	 *
	 * This method handles a two-step payment flow:
	 * - Step 1 (no payment_intent_id): Create order, return success without capturing
	 * - Step 2 (with payment_intent_id): Capture payment via WooPayments
	 *
	 * @param int $order_id Order ID.
	 * @return array
	 */
	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce handled by Store API.
		$payment_intent_id = isset( $_POST['payment_intent_id'] )
			? wc_clean( wp_unslash( $_POST['payment_intent_id'] ) ) // phpcs:ignore WordPress.Security.NonceVerification.Missing
			: null;

		if ( ! $payment_intent_id ) {
			// Step 1: Order created, waiting for card collection.
			// Return success without processing payment - order stays pending, cart intact.
			return array(
				'result'   => 'success',
				'redirect' => '',
			);
		}

		// Step 2: Capture the payment via WooPayments.
		$capture_result = $this->capture_payment_intent( $order, $payment_intent_id );

		if ( is_wp_error( $capture_result ) ) {
			// Pass through the full error from WooPayments for app compatibility.
			return array(
				'result'  => 'failure',
				'message' => $capture_result->get_error_message(),
				'code'    => $capture_result->get_error_code(),
				'data'    => $capture_result->get_error_data(),
			);
		}

		// WooPayments handles payment_complete() and order status.
		// Start a fresh transaction for the next POS customer.
		$this->start_new_pos_transaction();

		return array(
			'result'   => 'success',
			'redirect' => $this->get_return_url( $order ),
		);
	}

	/**
	 * Capture a payment intent via WooPayments.
	 *
	 * Delegates to WooPayments' capture_terminal_payment endpoint which handles
	 * all the Stripe interaction, order status updates, and fulfillment triggers.
	 *
	 * @param WC_Order $order     The order to capture payment for.
	 * @param string   $intent_id The Stripe payment intent ID.
	 * @return array|WP_Error Response data on success, WP_Error on failure.
	 */
	private function capture_payment_intent( $order, $intent_id ) {
		$request = new WP_REST_Request(
			'POST',
			"/wc/v3/payments/orders/{$order->get_id()}/capture_terminal_payment"
		);
		$request->set_body_params( array( 'payment_intent_id' => $intent_id ) );

		$response = rest_do_request( $request );
		$status   = $response->get_status();
		$data     = $response->get_data();

		// Check for HTTP error status codes (4xx, 5xx).
		if ( $status >= 400 ) {
			// Pass through full error from WooPayments for app compatibility.
			return new WP_Error(
				$data['code'] ?? 'capture_failed',
				$data['message'] ?? __( 'Payment capture failed', 'woocommerce' ),
				$data['data'] ?? array( 'status' => $status )
			);
		}

		return $data;
	}

	/**
	 * Start a new POS transaction by clearing the cart.
	 *
	 * This ensures that cart data from one transaction doesn't carry over
	 * to the next. In POS, each transaction serves a different customer.
	 *
	 * The next POS request will get a fresh session with a new transaction ID
	 * because POSSessionHandler checks if the cart is empty when initializing.
	 */
	private function start_new_pos_transaction() {
		WC()->cart->empty_cart();
	}
}
