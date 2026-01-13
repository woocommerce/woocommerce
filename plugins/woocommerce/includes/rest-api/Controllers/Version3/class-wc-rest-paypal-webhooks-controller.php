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

use Automattic\WooCommerce\Gateways\PayPal\WebhookHandler as PayPalWebhookHandler;

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
		// POST /v3/paypal-webhooks.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'process_webhook' ),
				'permission_callback' => array( $this, 'validate_webhook' ),
			)
		);
	}

	/**
	 * Validate the webhook.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return bool True if the webhook is valid, false otherwise.
	 */
	public function validate_webhook( WP_REST_Request $request ) {
		try {
			if (
					class_exists( 'Automattic\Jetpack\Connection\REST_Authentication' ) &&
					method_exists( 'Automattic\Jetpack\Connection\REST_Authentication', 'is_signed_with_blog_token' )
				) {
					return \Automattic\Jetpack\Connection\REST_Authentication::is_signed_with_blog_token();
			}
			return false;
		} catch ( \Throwable $e ) {
			WC_Gateway_Paypal::log( 'REST authentication method not available. Webhook data: ' . wc_print_r( $request->get_json_params(), true ), 'error' );
			return false;
		}
	}

	/**
	 * Process the webhook.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response The response object.
	 */
	public function process_webhook( WP_REST_Request $request ) {
		$webhook_handler = new PayPalWebhookHandler();

		try {
			$webhook_handler->process_webhook( $request );
			return new WP_REST_Response( array( 'message' => 'Webhook processed successfully' ), 200 );
		} catch ( Exception $e ) {
			return new WP_REST_Response( array( 'error' => $e->getMessage() ), 500 );
		}
	}
}
