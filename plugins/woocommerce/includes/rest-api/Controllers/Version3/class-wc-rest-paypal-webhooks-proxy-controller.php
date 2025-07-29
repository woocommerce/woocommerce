<?php
// phpcs:disable WordPress.PHP.DevelopmentFunctions.error_log_error_log
// phpcs:disable Generic.Commenting.Todo.TaskFound

/**
 * TODO: IMPORTANT: This controller is for testing only.
 * TODO: REMOVE ME before merging the feature branch.
 *
 * REST API PayPal webhooks proxy controller
 *
 * Handles requests to the /paypal-webhooks-proxy endpoint.
 *
 * @package WooCommerce\RestApi
 * @since   2.6.0
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

/**
 * REST API PayPal webhooks proxy controller class.
 *
 * @package WooCommerce\RestApi
 * @extends WC_REST_Controller
 */
class WC_REST_Paypal_Webhooks_Proxy_Controller extends WC_REST_Controller {

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
	protected $rest_base = 'paypal-webhooks-proxy';

	/**
	 * Register the routes for the PayPal proxy.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/test-webhook',
			array(
				array(
					'methods'             => array( WP_REST_Server::READABLE, WP_REST_Server::CREATABLE ),
					'callback'            => array( $this, 'test_webhook' ),
					'permission_callback' => '__return_true',
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/webhook',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'process_webhook' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Test the webhook.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response The response object.
	 */
	public function test_webhook( WP_REST_Request $request ) {
		$data = $request->get_json_params();
		error_log( 'PayPal test webhook received: ' . wc_print_r( $data, true ) );
		return new WP_REST_Response( 'Test webhook processed', 200 );
	}

	/**
	 * Handle webhook events from PayPal.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response The response object.
	 */
	public function process_webhook( WP_REST_Request $request ) {
		// Validate the webhook signature first.
		$validation_result = $this->validate_webhook_signature_with_postback( $request );
		if ( is_wp_error( $validation_result ) ) {
			error_log( 'PayPal webhook signature validation failed: ' . $validation_result->get_error_message() );
			return new WP_REST_Response( 'Webhook signature validation failed', 401 );
		}

		$data = $request->get_json_params();
		error_log( '(Proxy) PayPal webhook received: ' . wc_print_r( $data, true ) );

		switch ( $data['event_type'] ) {
			case 'CHECKOUT.ORDER.APPROVED':
				$custom_client_data = $data['resource']['purchase_units'][0]['custom_id'];
				$client_endpoint    = $this->get_client_webhook_endpoint( $custom_client_data );

				if ( ! $client_endpoint ) {
					error_log( 'No client webhook endpoint found' );
					return new WP_REST_Response( 'No client webhook endpoint found', 400 );
				}

				$response = $this->forward_webhook_to_client( $client_endpoint, $data );
				if ( is_wp_error( $response ) || $response->get_status() !== 200 ) {
					error_log( 'Webhook forwarding failed: ' . wc_print_r( $response, true ) );
					return new WP_REST_Response( 'Webhook forwarding failed', 500 );
				}
				break;
			case 'PAYMENT.CAPTURE.COMPLETED':
				$custom_client_data = $data['resource']['custom_id'];
				$client_endpoint    = $this->get_client_webhook_endpoint( $custom_client_data );

				if ( ! $client_endpoint ) {
					error_log( 'No client webhook endpoint found' );
					return new WP_REST_Response( 'No client webhook endpoint found', 400 );
				}

				$response = $this->forward_webhook_to_client( $client_endpoint, $data );
				if ( is_wp_error( $response ) || $response->get_status() !== 200 ) {
					error_log( 'Webhook forwarding failed: ' . wc_print_r( $response, true ) );
					return new WP_REST_Response( 'Webhook forwarding failed', 500 );
				}

				break;
			default:
				error_log( 'Unhandled PayPal webhook event: ' . wc_print_r( $data, true ) );
				break;
		}

		return new WP_REST_Response( 'Webhook processed', 200 );
	}

	/**
	 * Validate the PayPal webhook signature.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return bool|WP_Error True if valid, WP_Error if invalid.
	 */
	private function validate_webhook_signature_with_postback( WP_REST_Request $request ) {
		// Extract required headers for signature validation.
		$headers = $request->get_headers();
		$data    = $request->get_json_params();
		
		$auth_algo        = $headers['paypal_auth_algo'][0] ?? '';
		$cert_url         = $headers['paypal_cert_url'][0] ?? '';
		$transmission_id  = $headers['paypal_transmission_id'][0] ?? '';
		$transmission_sig = $headers['paypal_transmission_sig'][0] ?? '';
		$transmission_time = $headers['paypal_transmission_time'][0] ?? '';

		// Validate that all required headers are present.
		if ( empty( $auth_algo ) || empty( $cert_url ) || empty( $transmission_id ) || 
			 empty( $transmission_sig ) || empty( $transmission_time ) ) {
			return new WP_Error( 'missing_headers', 'Required PayPal webhook headers are missing' );
		}

		// Get webhook ID from PayPal settings.
		$webhook_id = get_option( 'wc_paypal_webhook_id' );
		if ( empty( $webhook_id ) ) {
			return new WP_Error( 'missing_webhook_id', 'PayPal webhook ID is not configured' );
		}

		// Prepare the verification request payload.
		$verification_data = array(
			'auth_algo'         => $auth_algo,
			'cert_url'          => $cert_url,
			'transmission_id'   => $transmission_id,
			'transmission_sig'  => $transmission_sig,
			'transmission_time' => $transmission_time,
			'webhook_id'        => $webhook_id,
			'webhook_event'     => $request->get_json_params(),
		);


		$testmode = strpos( $data['resource']['links'][0]['href'], 'sandbox' ) !== false;

		// Make API call to PayPal to verify the signature.
		$paypal_api_url = $this->get_paypal_api_base_url( $testmode ) . '/v1/notifications/verify-webhook-signature';
		$proxy_controller = new WC_REST_Paypal_Proxy_Controller();
		$access_token     = $proxy_controller->get_paypal_access_token( $testmode );

		if ( empty( $access_token ) ) {
			return new WP_Error( 'missing_access_token', 'PayPal access token is not available' );
		}

		$response = wp_remote_post(
			$paypal_api_url,
			array(
				'headers' => array(
					'Content-Type'  => 'application/json',
					'Authorization' => 'Bearer ' . $access_token,
				),
				'body'    => wp_json_encode( $verification_data ),
				'timeout' => 30,
			)
		);

		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'verification_request_failed', 'Failed to make verification request to PayPal: ' . $response->get_error_message() );
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );

		if ( 200 !== $response_code ) {
			return new WP_Error( 'verification_failed', 'PayPal signature verification failed with status: ' . $response_code );
		}

		$verification_result = json_decode( $response_body, true );
		
		if ( ! isset( $verification_result['verification_status'] ) || 
			 'SUCCESS' !== $verification_result['verification_status'] ) {
			return new WP_Error( 'signature_invalid', 'PayPal signature verification returned failure status' );
		}

		return true;
	}

	/**
	 * Get the PayPal API base URL based on environment.
	 *
	 * @return string The API base URL.
	 */
	private function get_paypal_api_base_url( $testmode ) {		
		return $testmode 
			? 'https://api-m.sandbox.paypal.com'
			: 'https://api-m.paypal.com';
	}

	/**
	 * Get the client webhook endpoint from the custom data.
	 *
	 * @param string $custom_client_data The custom data.
	 * @return string|null The client webhook endpoint.
	 */
	private function get_client_webhook_endpoint( $custom_client_data ) {
		$data     = json_decode( $custom_client_data );
		$endpoint = $data->endpoint ?? null;

		if ( ! $endpoint || ! wp_http_validate_url( $endpoint ) ) {
			error_log( 'Invalid client webhook endpoint: ' . $endpoint );
			return null;
		}

		return $endpoint;
	}

	/**
	 * Forward the webhook to the client.
	 *
	 * @param string $client_endpoint The client webhook endpoint.
	 * @param array  $data The webhook data.
	 * @return WP_REST_Response The response object.
	 */
	private function forward_webhook_to_client( $client_endpoint, $data ) {
		error_log( 'Forwarding webhook to client: ' . $client_endpoint );

		// Forward the webhook to the client.
		$request = WP_REST_Request::from_url( $client_endpoint );
		$request->set_method( 'POST' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( $data ) );
		return rest_do_request( $request );
	}
}

/**
 * TODO: IMPORTANT: This controller is for testing only.
 * TODO: REMOVE ME before merging the feature branch.
 */
