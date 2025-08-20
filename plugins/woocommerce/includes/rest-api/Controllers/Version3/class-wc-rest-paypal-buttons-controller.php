<?php
/**
 *
 * REST API PayPal buttons controller
 *
 * Handles requests to the /paypal-buttons endpoint.
 *
 * @package WooCommerce\RestApi
 * @since   2.6.0
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;
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
	 * Register the routes for the PayPal buttons.
	 *
	 * @return void
	 */
	public function register_routes() {
		// phpcs:disable Generic.Commenting.Todo.TaskFound
		// TODO: Remove me before merging the feature branch.
		// GET /v3/paypal-buttons/test-button.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/test',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'test' ),
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
				'callback'            => array( $this, 'process_request' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'order_id' => array(
						'type'        => 'integer',
						'description' => __( 'Order ID.', 'woocommerce' ),
					),
				),
			),
		);
	}

	/**
	 * Test request.
	 *
	 * phpcs:disable Generic.Commenting.Todo.TaskFound
	 * TODO: Remove me before merging the feature branch.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response The response object.
	 */
	public function test( WP_REST_Request $request ) {
		$data = $request->get_json_params();
		return new WP_REST_Response( 'Test request processed', 200 );
	}

	/**
	 * Process the webhook.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response The response object.
	 */
	public function process_request( WP_REST_Request $request ) {
		$order_id = $request->get_param( 'order_id' );
		$order    = wc_get_order( $order_id );

		if ( ! $order ) {
			return new WP_REST_Response( 'Order not found', 404 );
		}

		include_once WC_ABSPATH . 'includes/gateways/paypal/class-wc-gateway-paypal.php';
		include_once WC_ABSPATH . 'includes/gateways/paypal/includes/class-wc-gateway-paypal-request.php';
		$gateway        = WC_Gateway_Paypal::get_instance();
		$paypal_request = new WC_Gateway_Paypal_Request( $gateway );
		$response       = $paypal_request->create_paypal_order( $order );
		error_log( 'response: ' . wc_print_r( $response, true ) );

		return new WP_REST_Response( $response, 200 );
	}
}
