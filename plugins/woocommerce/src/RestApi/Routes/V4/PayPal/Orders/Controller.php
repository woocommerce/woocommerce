<?php
/**
 *
 * REST API PayPal buttons controller
 *
 * @package Automattic\WooCommerce\RestApi
 * @since   10.3.0
 */

declare(strict_types=1);

namespace Automattic\WooCommerce\RestApi\Routes\V4\PayPal\Orders;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\RestApi\Routes\V4\AbstractController;

if ( ! class_exists( 'WC_Gateway_Paypal_Constants' ) ) {
	require_once WC_ABSPATH . 'includes/gateways/paypal/includes/class-wc-gateway-paypal-constants.php';
}

if ( ! class_exists( 'WC_Gateway_Paypal_Request' ) ) {
	require_once WC_ABSPATH . 'includes/gateways/paypal/includes/class-wc-gateway-paypal-request.php';
}

/**
 * REST API PayPal Standard orders controller class.
 *
 * @package Automattic\WooCommerce\RestApi
 * @extends AbstractController
 */
class Controller extends AbstractController {
	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'paypal';

	/**
	 * Initialize the controller.
	 *
	 * @param PaypalOrderSchema $order_schema PayPal order schema class.
	 * @internal
	 */
	final public function init( PaypalOrderSchema $order_schema ) {
		$this->item_schema = $order_schema;
	}

	/**
	 * Get the schema for the current resource. This use consumed by the AbstractController to generate the item schema
	 * after running various hooks on the response.
	 */
	protected function get_schema(): array {
		return $this->item_schema->get_item_schema();
	}

	/**
	 * Register the routes for the PayPal buttons functionality handler.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				'schema' => array( $this, 'get_public_item_schema' ),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_item' ),
					'permission_callback' => array( $this, 'create_item_permissions_check' ),
				),
			),
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)',
			array(
				'schema' => array( $this, 'get_public_item_schema' ),
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'cancel_item' ),
					'permission_callback' => array( $this, 'cancel_item_permissions_check' ),
				),
			),
		);
	}

	/**
	 * Validate the create order request.
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return bool True if the create order request is valid, false otherwise.
	 */
	public function create_item_permissions_check( \WP_REST_Request $request ) {
		if ( $request->get_header( 'Nonce' ) ) {
			$nonce = $request->get_header( 'Nonce' );
			return wp_verify_nonce( $nonce, 'wc_gateway_paypal_standard_create_order' );
		}
		return false;
	}

	/**
	 * Validate the cancel order request.
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return bool True if the cancel order request is valid, false otherwise.
	 */
	public function cancel_item_permissions_check( \WP_REST_Request $request ) {
		if ( $request->get_header( 'Nonce' ) ) {
			$nonce = $request->get_header( 'Nonce' );
			return wp_verify_nonce( $nonce, 'wc_gateway_paypal_standard_cancel_order' );
		}
		return false;
	}

	/**
	 * Create a PayPal order.
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response|\WP_Error The response object (or error).
	 */
	public function create_item( \WP_REST_Request $request ) {
		$data = $request->get_json_params();

		if ( empty( $data['order_id'] ) || empty( $data['order_key'] ) ) {
			return $this->get_route_error_response(
				$this->get_error_prefix() . 'invalid_request',
				__( 'Invalid request.', 'woocommerce' ),
				400
			);
		}

		$payment_source = isset( $data['payment_source'] ) ? sanitize_text_field( $data['payment_source'] ) : '';
		if ( empty( $payment_source ) || ! in_array( $payment_source, \WC_Gateway_Paypal_Constants::SUPPORTED_PAYMENT_SOURCES, true ) ) {
			return $this->get_route_error_response(
				$this->get_error_prefix() . 'invalid_payment_source',
				__( 'Missing/Invalid payment source.', 'woocommerce' ),
				400
			);
		}

		$order_id = absint( $data['order_id'] );
		$order    = wc_get_order( $order_id );

		if ( ! $order ) {
			return $this->get_route_error_response(
				$this->get_error_prefix() . 'invalid_id',
				__( 'Order not found.', 'woocommerce' ),
				404
			);
		}

		$order_key = $data['order_key'];
		if ( ! $order_key || ! hash_equals( $order->get_order_key(), $order_key ) ) {
			return $this->get_route_error_response(
				$this->get_error_prefix() . 'invalid_order_key',
				__( 'Order not found.', 'woocommerce' ),
				404
			);
		}

		if ( ! in_array( $order->get_status(), array( OrderStatus::CHECKOUT_DRAFT, OrderStatus::PENDING ), true ) ) {
			return $this->get_route_error_response(
				$this->get_error_prefix() . 'invalid_order_status',
				__( 'Invalid order status.', 'woocommerce' ),
				409
			);
		}

		$gateway = \WC_Gateway_Paypal::get_instance();

		// For Buttons requests, we need to explicitly set the payment method to PayPal.
		$order->set_payment_method( $gateway->id );
		$order->save();

		$paypal_request = new \WC_Gateway_Paypal_Request( $gateway );
		$paypal_order   = $paypal_request->create_paypal_order(
			$order,
			$payment_source,
			array(
				'is_js_sdk_flow'            => true,
				'app_switch_request_origin' => $data['app_switch_request_origin'] ?? '',
			)
		);

		if ( ! $paypal_order || empty( $paypal_order['id'] ) ) {
			return $this->get_route_error_response(
				$this->get_error_prefix() . 'create_paypal_order_failed',
				__( 'Failed to create PayPal order.', 'woocommerce' ),
				400
			);
		}

		$order->update_meta_data( '_paypal_order_id', $paypal_order['id'] );
		$order->update_status( OrderStatus::PENDING );
		$order->save();

		$request->set_param( 'context', 'edit' );

		$order_data = [
			'paypal_order_id' => $paypal_order['id'] ?? null,
			'order_id'        => $order_id,
			'return_url'      => esc_url_raw( add_query_arg( 'utm_nooverride', '1', $gateway->get_return_url( $order ) ) ),
		];

		$response = $this->prepare_item_for_response( $order_data, $request );
		$response->set_status( \WP_Http::CREATED );
		$response->header( 'Location', rest_url( sprintf( '/%s/%s/%d', $this->namespace, $this->rest_base, $order_data['paypal_order_id'] ) ) );

		return $response;
	}

	/**
	 * Cancel a PayPal order. This is used to move the woocommerce order back to a draft status.
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response|\WP_Error The response object (or error).
	 */
	public function cancel_item( \WP_REST_Request $request ) {
		$data = $request->get_json_params();

		$paypal_order_id = isset( $request['id'] ) ? wc_clean( $request['id'] ) : '';
		$order_id        = isset( $data['order_id'] ) ? absint( $data['order_id'] ) : 0;
		if ( ! $order_id || '' === $paypal_order_id ) {
			return $this->get_route_error_response(
				$this->get_error_prefix() . 'invalid_request',
				__( 'Invalid request.', 'woocommerce' ),
				400
			);
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return $this->get_route_error_response(
				$this->get_error_prefix() . 'invalid_id',
				__( 'Order not found.', 'woocommerce' ),
				404
			);
		}

		// Verify order by checking the PayPal order ID.
		$paypal_order_id_from_meta = $order->get_meta( '_paypal_order_id' );
		if ( $paypal_order_id_from_meta !== $paypal_order_id ) {
			return $this->get_route_error_response(
				$this->get_error_prefix() . 'invalid_paypal_order',
				__( 'Order not found.', 'woocommerce' ),
				404
			);
		}

		$order_data = [
			'paypal_order_id' => $paypal_order_id,
			'order_id'        => $order_id,
			'return_url'      => esc_url_raw( add_query_arg( 'utm_nooverride', '1', ( new \WC_Gateway_Paypal() )->get_return_url( $order ) ) ),
		];

		// If order is already in draft status, do nothing and return success.
		if ( $order->has_status( OrderStatus::CHECKOUT_DRAFT ) ) {
			return $this->prepare_item_for_response( $order_data, $request );
		}

		// If order is not pending, return an error.
		if ( ! $order->has_status( OrderStatus::PENDING ) ) {
			return $this->get_route_error_response(
				$this->get_error_prefix() . 'invalid_order_status',
				__( 'Order is pending.', 'woocommerce' ),
				409
			);
		}

		$order->update_status( OrderStatus::CHECKOUT_DRAFT );
		$order->save();

		$request->set_param( 'context', 'edit' );

		return $this->prepare_item_for_response( $order_data, $request );
	}

	/**
	 * Prepare a single PayPal order data for response.
	 *
	 * @param array $order_data PayPal order data.
	 * @param \WP_REST_Request  $request Request object.
	 * @return array
	 */
	protected function get_item_response( $order_data, \WP_REST_Request $request ): array {
		return $this->item_schema->get_item_response( $order_data, $request, $this->get_fields_for_response( $request ) );
	}
}
