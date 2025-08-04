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
		// TODO: Validate the webhook signature.
		// https://developer.paypal.com/api/rest/webhooks/rest/#link-messageverification.

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
			case 'PAYMENT.AUTHORIZATION.CREATED':
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
