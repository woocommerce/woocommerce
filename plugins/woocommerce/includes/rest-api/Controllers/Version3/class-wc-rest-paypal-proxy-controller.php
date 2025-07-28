<?php
// phpcs:disable WordPress.PHP.DevelopmentFunctions.error_log_error_log
// phpcs:disable Generic.Commenting.Todo.TaskFound

/**
 * TODO: IMPORTANT: This controller is for testing only.
 * TODO: REMOVE ME before merging the feature branch.
 *
 * REST API PayPal proxy controller
 *
 * Handles requests to the /paypal-proxy endpoint.
 *
 * @package WooCommerce\RestApi
 * @since   2.6.0
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

/**
 * REST API PayPal proxy controller class.
 *
 * @package WooCommerce\RestApi
 * @extends WC_REST_Controller
 */
class WC_REST_Paypal_Proxy_Controller extends WC_REST_Controller {

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
	protected $rest_base = 'paypal-proxy';

	/**
	 * Register the routes for the PayPal proxy.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/test-request',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'test_request' ),
					'permission_callback' => '__return_true',
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/create-order',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'create_paypal_order' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/capture-payment',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'capture_payment' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * For testing the request endpoint.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response The response object.
	 */
	public function test_request( WP_REST_Request $request ) {
		$data = $request->get_json_params();
		error_log( '(Proxy) PayPal test request received: ' . wc_print_r( $data, true ) );
		return new WP_REST_Response( 'Test request processed', 200 );
	}

	/**
	 * Create a PayPal order using Orders v2 API.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response The response object.
	 */
	public function create_paypal_order( WP_REST_Request $request ) {
		$request_data = $request->get_json_params();
		if ( empty( $request_data['order'] ) || ! isset( $request_data['testmode'] ) ) {
			error_log( '(Proxy) PayPal create order request missing params. ' . wc_print_r( $request_data, true ) );
			return new WP_REST_Response(
				array( 'error' => 'PayPal create order request missing params.' ),
				400
			);
		}
		$order    = $request_data['order'];
		$testmode = $request_data['testmode'];
		error_log( '(Proxy) PayPal create order request received: ' . wc_print_r( $request_data, true ) );

		$access_token = $this->get_paypal_access_token();
		if ( ! $access_token ) {
			error_log( '(Proxy) Failed to get PayPal access token.' );
			return new WP_REST_Response(
				array( 'error' => 'Failed to get PayPal access token.' ),
				500
			);
		}

		$args = array(
			'method'    => 'POST',
			'headers'   => array(
				'Content-Type'      => 'application/json',
				'Authorization'     => 'Bearer ' . $access_token,
				'PayPal-Request-Id' => uniqid(), // A unique ID for idempotency (recommended by PayPal).
			),
			'body'      => wp_json_encode( $order ),
			'timeout'   => 45, // TODO: Set a timeout.
			'sslverify' => false, // TODO: Set sslverify.
		);

		error_log( '(Proxy) PayPal order creation request: ' . wc_print_r( $args, true ) );

		$response      = wp_remote_post( $this->get_paypal_create_order_request_url( $testmode ), $args );
		$http_code     = wp_remote_retrieve_response_code( $response );
		$body          = wp_remote_retrieve_body( $response );
		$response_data = json_decode( $body, true );
		error_log( '(Proxy) PayPal order creation response: ' . wc_print_r( $response_data, true ) );

		if ( in_array( $http_code, array( 200, 201 ), true ) ) {
			return new WP_REST_Response(
				$response_data,
				200
			);
		} else {
			error_log( '(Proxy) Create order request failed: ' . wc_print_r( $response_data, true ) );
			return new WP_REST_Response(
				array(
					'error' => 'Order creation failed.',
					'data'  => $response_data,
				),
				500
			);
		}
	}

	/**
	 * Get the PayPal create order request URL.
	 *
	 * @param bool $testmode Whether to use the sandbox environment.
	 * @return string The request URL.
	 */
	private function get_paypal_create_order_request_url( $testmode = true ) {
		return $testmode ? 'https://api-m.sandbox.paypal.com/v2/checkout/orders' : 'https://api-m.paypal.com/v2/checkout/orders';
	}

	/**
	 * Capture the PayPal payment using Orders v2 API.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response The response object.
	 */
	public function capture_payment( WP_REST_Request $request ) {
		$request_data = $request->get_json_params();
		error_log( '(Proxy) PayPal capture payment request received: ' . wc_print_r( $request_data, true ) );

		$access_token = $this->get_paypal_access_token();
		if ( ! $access_token ) {
			error_log( '(Proxy) Failed to get PayPal access token. Cannot capture payment.' );
			return new WP_REST_Response(
				array(
					'status'  => 'error',
					'message' => 'Failed to get PayPal access token.',
				),
				500
			);
		}

		if ( empty( $request_data['capture_url'] ) || empty( $request_data['paypal_order_id'] ) ) {
			error_log( '(Proxy) Capture URL or PayPal order ID missing. Cannot capture payment.' );
			return new WP_REST_Response(
				array(
					'status'  => 'error',
					'message' => 'Capture URL or PayPal order ID missing.',
				),
				400
			);
		}

		$capture_url     = $request_data['capture_url'];
		$paypal_order_id = $request_data['paypal_order_id'];

		$args = array(
			'method'  => 'POST',
			'headers' => array(
				'Content-Type'  => 'application/json',
				'Authorization' => 'Bearer ' . $access_token,
			),
			'body'    => wp_json_encode( array( 'id' => $paypal_order_id ) ),
		);

		$response      = wp_remote_post( $capture_url, $args );
		$http_code     = wp_remote_retrieve_response_code( $response );
		$body          = wp_remote_retrieve_body( $response );
		$response_data = json_decode( $body, true );
		error_log( '(Proxy) PayPal capture payment response: ' . wc_print_r( $response_data, true ) );

		if ( in_array( $http_code, array( 200, 201 ), true ) ) {
			return new WP_REST_Response(
				$response_data,
				200
			);
		} else {
			error_log( '(Proxy) Failed to capture PayPal order. ' . wc_print_r( $response_data, true ) );
			return new WP_REST_Response(
				$response_data,
				500
			);
		}
	}

	/**
	 * Get the PayPal API access token.
	 *
	 * @return string|null The access token.
	 */
	private function get_paypal_access_token() {
		$paypal_client_id     = get_option( 'wc_paypal_api_client_id' );
		$paypal_client_secret = get_option( 'wc_paypal_api_client_secret' );

		if ( ! $paypal_client_id || ! $paypal_client_secret ) {
			error_log( '(Proxy) PayPal client ID or secret not found. Cannot get access token.' );
			return null;
		}

		$args = array(
			'method'    => 'POST',
			'headers'   => array(
				'Content-Type'  => 'application/x-www-form-urlencoded',
				'Authorization' => 'Basic ' . base64_encode( $paypal_client_id . ':' . $paypal_client_secret ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
			),
			'body'      => 'grant_type=client_credentials',
			'timeout'   => 45, // TODO: Set a timeout.
			'sslverify' => false, // TODO: Set sslverify.
		);
		error_log( '(Proxy) PayPal access token request: ' . wc_print_r( $args, true ) );

		$response  = wp_remote_post( 'https://api-m.sandbox.paypal.com/v1/oauth2/token', $args );
		$http_code = wp_remote_retrieve_response_code( $response );
		$body      = wp_remote_retrieve_body( $response );
		$data      = json_decode( $body, true );

		// Check if the request was successful (HTTP 200 OK) and access token exists.
		if ( 200 === $http_code && isset( $data['access_token'] ) ) {
			error_log( '(Proxy) PayPal access token request successful.' );
			return $data['access_token'];
		} else {
			error_log( '(Proxy) Failed to get PayPal access token. ' . wc_print_r( $data, true ) );
			return null;
		}
	}
}

/**
 * TODO: IMPORTANT: This controller is for testing only.
 * TODO: REMOVE ME before merging the feature branch.
 */
