<?php
/**
 *
 * REST API PayPal buttons controller
 *
 * @package WooCommerce\RestApi
 * @since   10.3.0
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Gateways\PayPal\Constants as PayPalConstants;
use Automattic\WooCommerce\Gateways\PayPal\Request as PayPalRequest;

// Require the deprecated classes for backward compatibility.
// This will be removed in 11.0.0.
if ( ! class_exists( 'WC_Gateway_Paypal_Constants' ) ) {
	require_once WC_ABSPATH . 'includes/gateways/paypal/includes/class-wc-gateway-paypal-constants.php';
}

if ( ! class_exists( 'WC_Gateway_Paypal_Request' ) ) {
	require_once WC_ABSPATH . 'includes/gateways/paypal/includes/class-wc-gateway-paypal-request.php';
}

/**
 * REST API PayPal buttons controller class.
 *
 * @package WooCommerce\RestApi
 * @extends WC_REST_Controller
 */
class WC_REST_Paypal_Buttons_Controller extends WC_REST_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v3';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'paypal-buttons';

	/**
	 * Register the routes for the PayPal buttons functionality handler.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/create-order',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'create_order' ),
				'permission_callback' => array( $this, 'validate_create_order_request' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/cancel-payment',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'cancel_payment' ),
				'permission_callback' => array( $this, 'validate_cancel_payment_request' ),
			)
		);
	}

	/**
	 * Validate the create order request.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return bool True if the create order request is valid, false otherwise.
	 */
	public function validate_create_order_request( WP_REST_Request $request ) {
		if ( $request->get_header( 'Nonce' ) ) {
			$nonce = $request->get_header( 'Nonce' );
			return wp_verify_nonce( $nonce, 'wc_gateway_paypal_standard_create_order' );
		}
		return false;
	}

	/**
	 * Validate the cancel payment request.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return bool True if the cancel payment request is valid, false otherwise.
	 */
	public function validate_cancel_payment_request( WP_REST_Request $request ) {
		if ( $request->get_header( 'Nonce' ) ) {
			$nonce = $request->get_header( 'Nonce' );
			return wp_verify_nonce( $nonce, 'wc_gateway_paypal_standard_cancel_payment' );
		}
		return false;
	}

	/**
	 * Create a PayPal order.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response The response object.
	 */
	public function create_order( WP_REST_Request $request ) {
		$data = $request->get_json_params();

		if ( empty( $data['order_id'] ) || empty( $data['order_key'] ) ) {
			return new WP_REST_Response( array( 'error' => 'Invalid request' ), 400 );
		}

		$payment_source = isset( $data['payment_source'] ) ? sanitize_text_field( $data['payment_source'] ) : '';
		if ( empty( $payment_source ) || ! in_array( $payment_source, PayPalConstants::SUPPORTED_PAYMENT_SOURCES, true ) ) {
			return new WP_REST_Response( array( 'error' => 'Missing/Invalid payment source: ' . esc_html( $payment_source ) ), 400 );
		}

		$order_id = $data['order_id'];
		$order    = wc_get_order( $order_id );

		if ( ! $order || ! ( $order instanceof \WC_Order ) ) {
			return new WP_REST_Response( array( 'error' => 'Order not found' ), 404 );
		}

		$order_key = $data['order_key'];
		if ( ! $order_key || ! hash_equals( $order->get_order_key(), $order_key ) ) {
			return new WP_REST_Response( array( 'error' => 'Order not found' ), 404 );
		}

		if ( ! in_array( $order->get_status(), array( OrderStatus::CHECKOUT_DRAFT, OrderStatus::PENDING ), true ) ) {
			return new WP_REST_Response( array( 'error' => 'Invalid order status' ), 409 );
		}

		$gateway = WC_Gateway_Paypal::get_instance();

		// For Buttons requests, we need to explicitly set the payment method to PayPal.
		$order->set_payment_method( $gateway->id );
		$order->save();

		$paypal_request = new PayPalRequest( $gateway );
		$paypal_order   = $paypal_request->create_paypal_order(
			$order,
			$payment_source,
			array(
				'is_js_sdk_flow'            => true,
				'app_switch_request_origin' => $data['app_switch_request_origin'] ?? '',
			)
		);

		if ( ! $paypal_order || empty( $paypal_order['id'] ) ) {
			return new WP_REST_Response( array( 'error' => 'Failed to create PayPal order' ), 400 );
		}

		$order->update_meta_data( PayPalConstants::PAYPAL_ORDER_META_ORDER_ID, $paypal_order['id'] );
		$order->update_status( OrderStatus::PENDING );
		$order->save();

		return new WP_REST_Response(
			array(
				'paypal_order_id' => $paypal_order['id'] ?? null,
				'order_id'        => $order_id,
				'return_url'      => esc_url_raw( add_query_arg( 'utm_nooverride', '1', $gateway->get_return_url( $order ) ) ),
			),
			200
		);
	}

	/**
	 * Cancel a PayPal payment. This is used to move the woocommerce order back to a draft status.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response The response object.
	 */
	public function cancel_payment( WP_REST_Request $request ) {
		$data = $request->get_json_params();

		$order_id        = isset( $data['order_id'] ) ? absint( $data['order_id'] ) : 0;
		$paypal_order_id = isset( $data['paypal_order_id'] ) ? wc_clean( $data['paypal_order_id'] ) : '';
		if ( ! $order_id || '' === $paypal_order_id ) {
			return new WP_REST_Response( array( 'error' => 'Invalid request' ), 400 );
		}

		$order = wc_get_order( $order_id );
		if ( ! $order || ! ( $order instanceof \WC_Order ) ) {
			return new WP_REST_Response( array( 'error' => 'Order not found' ), 404 );
		}

		// Verify order by checking the PayPal order ID.
		$paypal_order_id_from_meta = $order->get_meta( '_paypal_order_id' );
		if ( $paypal_order_id_from_meta !== $paypal_order_id ) {
			return new WP_REST_Response( array( 'error' => 'Invalid PayPal order' ), 404 );
		}

		// If order is already in draft status, do nothing and return success.
		if ( $order->has_status( OrderStatus::CHECKOUT_DRAFT ) ) {
			return new WP_REST_Response( array( 'success' => true ), 200 );
		}

		// If order is not pending, return an error.
		if ( ! $order->has_status( OrderStatus::PENDING ) ) {
			return new WP_REST_Response( array( 'error' => 'Order is not pending' ), 409 );
		}

		$order->update_status( OrderStatus::CHECKOUT_DRAFT );
		$order->save();

		return new WP_REST_Response( array( 'success' => true ), 200 );
	}
}
