<?php
/**
 *
 * REST API PayPal webhooks controller
 *
 * Handles requests to the /paypal-webhooks endpoint.
 *
 * @package WooCommerce\RestApi
 * @since   2.6.0
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

/**
 * REST API PayPal webhook handler controller class.
 *
 * @package WooCommerce\RestApi
 * @extends WC_REST_Controller
 */
class WC_REST_Paypal_Webhooks_Controller extends WC_REST_Controller {

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
	protected $rest_base = 'paypal-webhooks';

	/**
	 * Register the routes for the PayPal webhook handler.
	 *
	 * @return void
	 */
	public function register_routes() {
		// phpcs:disable Generic.Commenting.Todo.TaskFound
		// TODO: Remove me before merging the feature branch.
		// GET /v3/paypal-webhooks/test-webhook.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/test-webhook',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'test_webhook' ),
					'permission_callback' => '__return_true',
				),
			)
		);

		// POST /v3/paypal-webhooks.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
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
	 * phpcs:disable Generic.Commenting.Todo.TaskFound
	 * TODO: Remove me before merging the feature branch.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response The response object.
	 */
	public function test_webhook( WP_REST_Request $request ) {
		$data = $request->get_json_params();
		return new WP_REST_Response( 'Test webhook processed', 200 );
	}

	/**
	 * Process the webhook.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return void
	 */
	public function process_webhook( WP_REST_Request $request ) {
		include_once WC_ABSPATH . 'includes/gateways/paypal/includes/class-wc-gateway-paypal-webhook-handler.php';
		$webhook_handler = new WC_Gateway_Paypal_Webhook_Handler();

		try {
			$webhook_handler->process_webhook( $request );
			return new WP_REST_Response( array( 'message' => 'Webhook processed successfully' ), 200 );
		} catch ( Exception $e ) {
			return new WP_REST_Response( array( 'error' => $e->getMessage() ), 500 );
		}
	}
}
