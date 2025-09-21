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

require_once WC_ABSPATH . 'includes/gateways/paypal/includes/class-wc-gateway-paypal-request.php';

use Automattic\WooCommerce\Enums\OrderStatus;


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
				'permission_callback' => array( $this, 'validate_create_order' ),
			)
		);
	}

	/**
	 * Validate the create order request.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return bool True if the create order request is valid, false otherwise.
	 */
	public function validate_create_order( WP_REST_Request $request ) {
		if ( $request->get_header( 'Nonce' ) ) {
			$nonce = $request->get_header( 'Nonce' );
			return wp_verify_nonce( $nonce, 'create_order' );
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

		if ( empty( $data['order_id'] ) ) {
			return new WP_REST_Response( array( 'error' => 'Invalid request' ), 400 );
		}

		$order_id = $data['order_id'];
		$order    = wc_get_order( $order_id );

		if ( ! $order ) {
			return new WP_REST_Response( array( 'error' => 'Order not found' ), 404 );
		}

		$gateway = WC_Gateway_Paypal::get_instance();

		// Check if the order already has a PayPal order ID.
		$existing_paypal_order_id = $order->get_meta( '_paypal_order_id' );

		if ( $existing_paypal_order_id ) {
			return new WP_REST_Response(
				array(
					'paypal_order_id' => $existing_paypal_order_id,
					'order_id'        => $order_id,
					'return_url'      => esc_url_raw( add_query_arg( 'utm_nooverride', '1', $gateway->get_return_url( $order ) ) ),
				),
				200
			);
		}

		$paypal_request = new WC_Gateway_Paypal_Request( $gateway );
		$paypal_order   = $paypal_request->create_paypal_order( $order );

		if ( ! $paypal_order || empty( $paypal_order['id'] ) || empty( $paypal_order['redirect_url'] ) ) {
			return new WP_REST_Response( array( 'error' => 'Failed to create PayPal order' ), 400 );
		}

		$order->update_meta_data( '_paypal_order_id', $paypal_order['id'] );
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
}
