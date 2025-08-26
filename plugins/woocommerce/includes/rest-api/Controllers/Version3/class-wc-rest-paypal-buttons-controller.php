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
			'/' . $this->rest_base . '/create-paypal-order',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'create_paypal_order' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'order_id' => array(
						'type'        => 'integer',
						'description' => __( 'Order ID.', 'woocommerce' ),
					),
				),
			),
		);

		// POST /v3/paypal-buttons/authorize-or-capture-payment.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/capture-payment',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'authorize_or_capture_payment' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'order_id'        => array(
						'type'        => 'integer',
						'description' => __( 'Order ID.', 'woocommerce' ),
					),
					'action'          => array(
						'type'        => 'string',
						'description' => __( 'Action to perform.', 'woocommerce' ),
					),
					'paypal_order_id' => array(
						'type'        => 'string',
						'description' => __( 'PayPal order ID.', 'woocommerce' ),
					),
				),
			),
		);

		// POST /v3/paypal-buttons/shipping-callback.
		// TODO: Move to PayPal webhooks controller.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/shipping-callback',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'process_shipping_callback' ),
				'permission_callback' => '__return_true',
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
	 * Create a PayPal order.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response The response object.
	 */
	public function create_paypal_order( WP_REST_Request $request ) {
		wc_load_cart();
		WC()->cart->get_cart();

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

		return new WP_REST_Response( $response, 200 );
	}

	/**
	 * Authorize or capture payment.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response The response object.
	 */
	public function authorize_or_capture_payment( WP_REST_Request $request ) {
		$data     = $request->get_json_params();
		$order_id = $data['order_id'];
		$action   = $data['action'];
		$order    = wc_get_order( $order_id );

		$paypal_order_id = $order->get_meta( '_paypal_order_id' );
		if ( ! $paypal_order_id || $paypal_order_id !== $data['paypal_order_id'] ) {
			return new WP_REST_Response( 'Invalid order ID', 400 );
		}

		include_once WC_ABSPATH . 'includes/gateways/paypal/class-wc-gateway-paypal.php';
		include_once WC_ABSPATH . 'includes/gateways/paypal/includes/class-wc-gateway-paypal-request.php';
		$gateway        = WC_Gateway_Paypal::get_instance();
		$paypal_request = new WC_Gateway_Paypal_Request( $gateway );

		$order->update_meta_data( '_paypal_status', 'approved' );
		$order->add_order_note(
			sprintf(
				/* translators: %1$s: PayPal order ID */
				__( 'PayPal payment approved. PayPal Order ID: %1$s', 'woocommerce' ),
				$paypal_order_id
			)
		);

		$order->set_payment_method( $gateway->id );
		$order->set_payment_method_title( $gateway->get_title() );
		$order->update_status( 'pending' );
		$order->save();

		$response = $paypal_request->authorize_or_capture_payment( $order, $action );
		if ( ! $response ) {
			return new WP_REST_Response( 'Payment failed', 500 );
		}

		$order->set_transaction_id( $response['id'] );
		$order->update_meta_data( '_paypal_status', strtolower( $response['status'] ) );
		$order->payment_complete();
		$order->add_order_note(
			sprintf(
				/* translators: %1$s: Transaction ID */
				__( 'PayPal payment captured. Transaction ID: %1$s.', 'woocommerce' ),
				$response['id']
			)
		);
		$order->save();

		return new WP_REST_Response( array( 'return_url' => $order->get_checkout_order_received_url() ), 200 );
	}

	public function process_shipping_callback( WP_REST_Request $request ) {
		$data = $request->get_json_params();

		$paypal_order_id = $data['id'];
		error_log( 'paypal_order_id: ' . $paypal_order_id );
		$shipping_address = $data['shipping_address'];
		error_log( 'shipping_address: ' . print_r( $shipping_address, true ) );
		$order = $this->get_order_by_paypal_order_id( $paypal_order_id );

		// TODO: https://developer.paypal.com/docs/multiparty/checkout/standard/customize/shipping-module/#merchant-decline-response
		if ( ! $order ) {
			error_log( 'Order not found' );
			return new WP_REST_Response( 'Order not found', 404 );
		}

		$shipping_options = $this->get_shipping_options( $order, $shipping_address );
		if ( empty( $shipping_options ) ) {
			$error_response = array(
				'name'    => 'UNPROCESSABLE_ENTITY',
				'details' => array(
					array( 'issue' => 'ADDRESS_ERROR' ),
				),
			);
			return new WP_REST_Response( $error_response, 422 );
		}

		// TODO: Update shipping amount.

		$response = array(
			'id'             => $data['id'],
			'purchase_units' => array(
				array(
					'reference_id'     => $data['purchase_units'][0]['reference_id'],
					'amount'           => $data['purchase_units'][0]['amount'],
					'shipping_options' => $shipping_options,
				),
			),
		);

		return new WP_REST_Response( $response, 200 );
	}

	// TODO: Support non-HPOS.
	// TODO: Move to Helper class.
	public function get_order_by_paypal_order_id( $paypal_order_id ) {
		$args = array(
			'limit'      => 1,
			'meta_query' => array(
				array(
					'key'   => '_paypal_order_id',
					'value' => $paypal_order_id,
				),
			),
		);

		$orders = wc_get_orders( $args );

		if ( ! empty( $orders ) ) {
			$order = $orders[0];
		}

		return $order;
	}

	/**
	 * Get the shipping options for the order.
	 *
	 * @param WC_Order $order The order object.
	 * @param array    $shipping_address The shipping address.
	 * @return array The shipping options.
	 */
	public function get_shipping_options( $order, $shipping_address ) {
		wc_load_cart();
		WC()->cart->get_cart();

		$chosen_shipping_methods = WC()->session->get( 'chosen_shipping_methods', array() );
		$chosen_shipping_method  = $chosen_shipping_methods[0] ?? false;

		$country  = $shipping_address['country_code'] ?? '';
		$postcode = $shipping_address['postal_code'] ?? '';
		$state    = $shipping_address['admin_area_1'] ?? '';
		$city     = $shipping_address['admin_area_2'] ?? '';

		WC()->customer->set_location( $country, $state, $postcode, $city );
		WC()->customer->set_shipping_location( $country, $state, $postcode, $city );
		WC()->customer->set_calculated_shipping( true );
		WC()->customer->save();

		WC()->shipping()->calculate_shipping( WC()->cart->get_shipping_packages() );
		$packages = WC()->shipping()->get_packages();
		$options  = array();
		foreach ( $packages as $package ) {
			$rates = $package['rates'] ?? array();
			foreach ( $rates as $rate ) {
				if ( ! $rate instanceof \WC_Shipping_Rate ) {
					continue;
				}
				$options[] = array(
					'id'       => $rate->get_id(),
					'type'     => 'SHIPPING',
					'amount'   => array(
						'currency_code' => $order->get_currency(),
						'value'         => $rate->get_cost(),
					),
					'label'    => $rate->get_label(),
					'selected' => $rate->get_id() === $chosen_shipping_method,
				);
			}
		}

		if ( ! $chosen_shipping_method && ! empty( $options ) ) {
			$options[0]['selected'] = true;
		}

		return $options;
	}
}
