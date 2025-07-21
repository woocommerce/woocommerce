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
	 */
	public function register_routes() {
        // TODO: Remove me before merging the feature branch.
        // GET /v3/paypal-webhooks/test-webhook
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

        // POST /v3/paypal-webhooks
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base,
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'handle_paypal_webhook' ),
                'permission_callback' => array( $this, 'get_permission' ),
            )
        );
	}

    // TODO: Remove me before merging the feature branch.
    public function test_webhook( WP_REST_Request $request ) {
        $data = $request->get_json_params();
        error_log( 'PayPal test webhook received: ' . print_r( $data, true ) );
        return new WP_REST_Response( 'Test webhook processed', 200 );
    }

    private function get_permission() {
        // TODO: Should we check if the webhook is coming from wpcom?
        return true;
    }

    public function process_webhook( WP_REST_Request $request ) {
        include_once WC_ABSPATH . 'includes/gateways/paypal/includes/class-wc-gateway-paypal-webhook-handler.php';
        $webhook_handler = new WC_Gateway_Paypal_Webhook_Handler();
        $webhook_handler->process_webhook( $request );
    }
}
