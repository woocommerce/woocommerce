<?php
namespace Automattic\WooCommerce\Blocks\StoreApi\Routes;

use Automattic\WooCommerce\Blocks\StoreApi\Utilities\InvalidStockLevelsInCartException;
use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Blocks\Domain\Services\CreateAccount;
use Automattic\WooCommerce\Blocks\StoreApi\Schemas\AbstractSchema;
use Automattic\WooCommerce\Blocks\StoreApi\Schemas\CartSchema;
use Automattic\WooCommerce\Blocks\StoreApi\Utilities\CartController;
use Automattic\WooCommerce\Blocks\StoreApi\Utilities\OrderController;
use Automattic\WooCommerce\Checkout\Helpers\ReserveStock;
use Automattic\WooCommerce\Checkout\Helpers\ReserveStockException;
use Automattic\WooCommerce\Blocks\Payments\PaymentResult;
use Automattic\WooCommerce\Blocks\Payments\PaymentContext;

/**
 * Checkout class.
 *
 * @internal This API is used internally by Blocks--it is still in flux and may be subject to revisions.
 */
class Checkout extends AbstractCartRoute {
	/**
	 * Holds the current order being processed.
	 *
	 * @var \WC_Order
	 */
	private $order = null;

	/**
	 * Order controller class instance.
	 *
	 * @var OrderController
	 */
	protected $order_controller;

	/**
	 * Constructor accepts two types of schema; one for the item being returned, and one for the cart as a whole. These
	 * may be the same depending on the route.
	 *
	 * @param CartSchema      $cart_schema Schema class for the cart.
	 * @param AbstractSchema  $item_schema Schema class for this route's items if it differs from the cart schema.
	 * @param CartController  $cart_controller Cart controller class.
	 * @param OrderController $order_controller Order controller class.
	 */
	public function __construct( CartSchema $cart_schema, AbstractSchema $item_schema = null, CartController $cart_controller, OrderController $order_controller ) {
		$this->schema           = is_null( $item_schema ) ? $cart_schema : $item_schema;
		$this->cart_schema      = $cart_schema;
		$this->cart_controller  = $cart_controller;
		$this->order_controller = $order_controller;
	}

	/**
	 * Get the path of this REST route.
	 *
	 * @return string
	 */
	public function get_path() {
		return '/checkout';
	}

	/**
	 * Checks if a nonce is required for the route.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return bool
	 */
	protected function requires_nonce( \WP_REST_Request $request ) {
		return true;
	}

	/**
	 * Get method arguments for this REST route.
	 *
	 * @return array An array of endpoints.
	 */
	public function get_args() {
		return [
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_response' ],
				'permission_callback' => '__return_true',
				'args'                => [
					'context' => $this->get_context_param( [ 'default' => 'view' ] ),
				],
			],
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'get_response' ],
				'permission_callback' => '__return_true',
				'args'                => array_merge(
					[
						'payment_data' => [
							'description' => __( 'Data to pass through to the payment method when processing payment.', 'woocommerce' ),
							'type'        => 'array',
							'items'       => [
								'type'       => 'object',
								'properties' => [
									'key'   => [
										'type' => 'string',
									],
									'value' => [
										'type' => [ 'string', 'boolean' ],
									],
								],
							],
						],
					],
					$this->schema->get_endpoint_args_for_item_schema( \WP_REST_Server::CREATABLE )
				),
			],
			[
				'methods'             => \WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'get_response' ),
				'permission_callback' => '__return_true',
				'args'                => $this->schema->get_endpoint_args_for_item_schema( \WP_REST_Server::EDITABLE ),
			],
			'schema'      => [ $this->schema, 'get_public_item_schema' ],
			'allow_batch' => [ 'v1' => true ],
		];
	}

	/**
	 * Prepare a single item for response. Handles setting the status based on the payment result.
	 *
	 * @param mixed            $item Item to format to schema.
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response $response Response data.
	 */
	public function prepare_item_for_response( $item, \WP_REST_Request $request ) {
		$response     = parent::prepare_item_for_response( $item, $request );
		$status_codes = [
			'success' => 200,
			'pending' => 202,
			'failure' => 400,
			'error'   => 500,
		];

		if ( isset( $item->payment_result ) && $item->payment_result instanceof PaymentResult ) {
			$response->set_status( $status_codes[ $item->payment_result->status ] ?? 200 );
		}

		return $response;
	}

	/**
	 * Convert the cart into a new draft order, or update an existing draft order, and return an updated cart response.
	 *
	 * @throws RouteException On error.
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	protected function get_route_response( \WP_REST_Request $request ) {
		$this->create_or_update_draft_order();

		return $this->prepare_item_for_response(
			(object) [
				'order'          => $this->order,
				'payment_result' => new PaymentResult(),
			],
			$request
		);
	}

	/**
	 * Update the current order.
	 *
	 * @internal Customer data is updated first so OrderController::update_addresses_from_cart uses up to date data.
	 *
	 * @throws RouteException On error.
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	protected function get_route_update_response( \WP_REST_Request $request ) {
		$this->update_customer_from_request( $request );
		$this->create_or_update_draft_order();
		$this->update_order_from_request( $request );

		return $this->prepare_item_for_response(
			(object) [
				'order'          => $this->order,
				'payment_result' => new PaymentResult(),
			],
			$request
		);
	}

	/**
	 * Update and process an order.
	 *
	 * 1. Obtain Draft Order
	 * 2. Process Request
	 * 3. Process Customer
	 * 4. Validate Order
	 * 5. Process Payment
	 *
	 * @throws RouteException On error.
	 * @throws InvalidStockLevelsInCartException On error.
	 *
	 * @param \WP_REST_Request $request Request object.
	 *
	 * @return \WP_REST_Response
	 */
	protected function get_route_post_response( \WP_REST_Request $request ) {
		/**
		 * Validate items etc are allowed in the order before the order is processed. This will fix violations and tell
		 * the customer.
		 */
		$this->cart_controller->validate_cart_items();
		$this->cart_controller->validate_cart_coupons();

		/**
		 * Obtain Draft Order and process request data.
		 *
		 * Note: Customer data is persisted from the request first so that OrderController::update_addresses_from_cart
		 * uses the up to date customer address.
		 */
		$this->update_customer_from_request( $request );
		$this->create_or_update_draft_order();
		$this->update_order_from_request( $request );

		/**
		 * Process customer data.
		 *
		 * Update order with customer details, and sign up a user account as necessary.
		 */
		$this->process_customer( $request );

		/**
		 * Validate order.
		 *
		 * This logic ensures the order is valid before payment is attempted.
		 */
		$this->order_controller->validate_order_before_payment( $this->order );

		/**
		 * WooCommerce Blocks Checkout Order Processed (experimental).
		 *
		 * This hook informs extensions that $order has completed processing and is ready for payment.
		 *
		 * This is similar to existing core hook woocommerce_checkout_order_processed. We're using a new action:
		 * - To keep the interface focused (only pass $order, not passing request data).
		 * - This also explicitly indicates these orders are from checkout block/StoreAPI.
		 *
		 * @see https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3238
		 * @internal This Hook is experimental and may change or be removed.
		 *
		 * @param \WC_Order $order Order object.
		 */
		do_action( '__experimental_woocommerce_blocks_checkout_order_processed', $this->order );

		/**
		 * Process the payment and return the results.
		 */
		$payment_result = new PaymentResult();

		if ( $this->order->needs_payment() ) {
			$this->process_payment( $request, $payment_result );
		} else {
			$this->process_without_payment( $request, $payment_result );
		}

		return $this->prepare_item_for_response(
			(object) [
				'order'          => wc_get_order( $this->order ),
				'payment_result' => $payment_result,
			],
			$request
		);
	}

	/**
	 * Get route response when something went wrong.
	 *
	 * @param string $error_code String based error code.
	 * @param string $error_message User facing error message.
	 * @param int    $http_status_code HTTP status. Defaults to 500.
	 * @param array  $additional_data  Extra data (key value pairs) to expose in the error response.
	 * @return \WP_Error WP Error object.
	 */
	protected function get_route_error_response( $error_code, $error_message, $http_status_code = 500, $additional_data = [] ) {
		$error_from_message = new \WP_Error(
			$error_code,
			$error_message
		);
		// 409 is when there was a conflict, so we return the cart so the client can resolve it.
		if ( 409 === $http_status_code ) {
			return $this->add_data_to_error_object( $error_from_message, $additional_data, $http_status_code, true );
		}
		return $this->add_data_to_error_object( $error_from_message, $additional_data, $http_status_code );
	}

	/**
	 * Get route response when something went wrong.
	 *
	 * @param \WP_Error $error_object User facing error message.
	 * @param int       $http_status_code HTTP status. Defaults to 500.
	 * @param array     $additional_data  Extra data (key value pairs) to expose in the error response.
	 * @return \WP_Error WP Error object.
	 */
	protected function get_route_error_response_from_object( $error_object, $http_status_code = 500, $additional_data = [] ) {
		// 409 is when there was a conflict, so we return the cart so the client can resolve it.
		if ( 409 === $http_status_code ) {
			return $this->add_data_to_error_object( $error_object, $additional_data, $http_status_code, true );
		}
		return $this->add_data_to_error_object( $error_object, $additional_data, $http_status_code );
	}

	/**
	 * Adds additional data to the \WP_Error object.
	 *
	 * @param \WP_Error $error The error object to add the cart to.
	 * @param array     $data The data to add to the error object.
	 * @param int       $http_status_code The HTTP status code this error should return.
	 * @param bool      $include_cart Whether the cart should be included in the error data.
	 * @returns \WP_Error The \WP_Error with the cart added.
	 */
	private function add_data_to_error_object( $error, $data, $http_status_code, bool $include_cart = false ) {
		$data = array_merge( $data, [ 'status' => $http_status_code ] );
		if ( $include_cart ) {
			$data = array_merge( $data, [ 'cart' => wc()->api->get_endpoint_data( '/wc/store/cart' ) ] );
		}
		$error->add_data( $data );
		return $error;
	}

	/**
	 * Gets draft order data from the customer session.
	 *
	 * @return array
	 */
	private function get_draft_order_id() {
		return wc()->session->get( 'store_api_draft_order', 0 );
	}

	/**
	 * Updates draft order data in the customer session.
	 *
	 * @param integer $order_id Draft order ID.
	 */
	private function set_draft_order_id( $order_id ) {
		wc()->session->set( 'store_api_draft_order', $order_id );
	}

	/**
	 * Whether the passed argument is a draft order or an order that is
	 * pending/failed and the cart hasn't changed.
	 *
	 * @param \WC_Order $order_object Order object to check.
	 * @return boolean Whether the order is valid as a draft order.
	 */
	private function is_valid_draft_order( $order_object ) {
		if ( ! $order_object instanceof \WC_Order ) {
			return false;
		}

		// Draft orders are okay.
		if ( $order_object->has_status( 'checkout-draft' ) ) {
			return true;
		}

		// Pending and failed orders can be retried if the cart hasn't changed.
		if ( $order_object->needs_payment() && $order_object->has_cart_hash( wc()->cart->get_cart_hash() ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Create or update a draft order based on the cart.
	 *
	 * @throws RouteException On error.
	 */
	private function create_or_update_draft_order() {
		$this->order = $this->get_draft_order_id() ? wc_get_order( $this->get_draft_order_id() ) : null;

		if ( ! $this->is_valid_draft_order( $this->order ) ) {
			$this->order = $this->order_controller->create_order_from_cart();
		} else {
			$this->order_controller->update_order_from_cart( $this->order );
		}

		/**
		 * WooCommerce Blocks Checkout Update Order Meta (experimental).
		 *
		 * This hook gives extensions the chance to add or update meta data on the $order.
		 *
		 * This is similar to existing core hook woocommerce_checkout_update_order_meta.
		 * We're using a new action:
		 * - To keep the interface focused (only pass $order, not passing request data).
		 * - This also explicitly indicates these orders are from checkout block/StoreAPI.
		 *
		 * @see https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3686
		 * @internal This Hook is experimental and may change or be removed.
		 *
		 * @param \WC_Order $order Order object.
		 */
		do_action( '__experimental_woocommerce_blocks_checkout_update_order_meta', $this->order );

		// Confirm order is valid before proceeding further.
		if ( ! $this->order instanceof \WC_Order ) {
			throw new RouteException(
				'woocommerce_rest_checkout_missing_order',
				__( 'Unable to create order', 'woocommerce' ),
				500
			);
		}

		// Store order ID to session.
		$this->set_draft_order_id( $this->order->get_id() );

		// Try to reserve stock for 10 mins, if available.
		try {
			$reserve_stock = new ReserveStock();
			$reserve_stock->reserve_stock_for_order( $this->order, 10 );
		} catch ( ReserveStockException $e ) {
			$error_data = $e->getErrorData();
			throw new RouteException(
				$e->getErrorCode(),
				$e->getMessage(),
				$e->getCode()
			);
		}
	}

	/**
	 * Updates the current customer session using data from the request (e.g. address data).
	 *
	 * Address session data is synced to the order itself later on by OrderController::update_order_from_cart()
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 */
	private function update_customer_from_request( \WP_REST_Request $request ) {
		$customer = wc()->customer;

		if ( isset( $request['billing_address'] ) ) {
			foreach ( $request['billing_address'] as $key => $value ) {
				if ( is_callable( [ $customer, "set_billing_$key" ] ) ) {
					$customer->{"set_billing_$key"}( $value );
				}
			}
		}

		if ( isset( $request['shipping_address'] ) ) {
			foreach ( $request['shipping_address'] as $key => $value ) {
				if ( is_callable( [ $customer, "set_shipping_$key" ] ) ) {
					$customer->{"set_shipping_$key"}( $value );
				} elseif ( 'phone' === $key ) {
					$customer->update_meta_data( 'shipping_phone', $value );
				}
			}
		}

		$customer->save();
	}

	/**
	 * Update the current order using the posted values from the request.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 */
	private function update_order_from_request( \WP_REST_Request $request ) {
		$this->order->set_customer_note( $request['customer_note'] ?? '' );
		$this->order->set_payment_method( $this->get_request_payment_method( $request ) );

		/**
		 * WooCommerce Blocks Checkout Update Order From Request (experimental).
		 *
		 * This hook gives extensions the chance to update orders based on the data in the request. This can be used in
		 * conjunction with the ExtendRestAPI class to post custom data and then process it.
		 *
		 * @internal This Hook is experimental and may change or be removed.
		 *
		 * @param \WC_Order $order Order object.
		 * @param \WP_REST_Request $request Full details about the request.
		 */
		do_action( '__experimental_woocommerce_blocks_checkout_update_order_from_request', $this->order, $request );

		$this->order->save();
	}

	/**
	 * For orders which do not require payment, just update status.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @param PaymentResult    $payment_result Payment result object.
	 */
	private function process_without_payment( \WP_REST_Request $request, PaymentResult $payment_result ) {
		// Transition the order to pending, and then completed. This ensures transactional emails fire for pending_to_complete events.
		$this->order->update_status( 'pending' );
		$this->order->payment_complete();

		// Mark the payment as successful.
		$payment_result->set_status( 'success' );
		$payment_result->set_redirect_url( $this->order->get_checkout_order_received_url() );
	}

	/**
	 * Fires an action hook instructing active payment gateways to process the payment for an order and provide a result.
	 *
	 * @throws RouteException On error.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @param PaymentResult    $payment_result Payment result object.
	 */
	private function process_payment( \WP_REST_Request $request, PaymentResult $payment_result ) {
		try {
			// Transition the order to pending before making payment.
			$this->order->update_status( 'pending' );

			// Prepare the payment context object to pass through payment hooks.
			$context = new PaymentContext();
			$context->set_payment_method( $this->get_request_payment_method_id( $request ) );
			$context->set_payment_data( $this->get_request_payment_data( $request ) );
			$context->set_order( $this->order );

			/**
			 * Process payment with context.
			 *
			 * @hook woocommerce_rest_checkout_process_payment_with_context
			 *
			 * @throws \Exception If there is an error taking payment, an \Exception object can be thrown with an error message.
			 *
			 * @param PaymentContext $context        Holds context for the payment, including order ID and payment method.
			 * @param PaymentResult  $payment_result Result object for the transaction.
			 */
			do_action_ref_array( 'woocommerce_rest_checkout_process_payment_with_context', [ $context, &$payment_result ] );

			if ( ! $payment_result instanceof PaymentResult ) {
				throw new RouteException( 'woocommerce_rest_checkout_invalid_payment_result', __( 'Invalid payment result received from payment method.', 'woocommerce' ), 500 );
			}
		} catch ( \Exception $e ) {
			throw new RouteException( 'woocommerce_rest_checkout_process_payment_error', $e->getMessage(), 400 );
		}
	}

	/**
	 * Gets the chosen payment method ID from the request.
	 *
	 * @throws RouteException On error.
	 * @param \WP_REST_Request $request Request object.
	 * @return string
	 */
	private function get_request_payment_method_id( \WP_REST_Request $request ) {
		$payment_method_id = wc_clean( wp_unslash( $request['payment_method'] ?? '' ) );

		if ( empty( $payment_method_id ) ) {
			throw new RouteException(
				'woocommerce_rest_checkout_missing_payment_method',
				__( 'No payment method provided.', 'woocommerce' ),
				400
			);
		}

		return $payment_method_id;
	}

	/**
	 * Gets the chosen payment method from the request.
	 *
	 * @throws RouteException On error.
	 * @param \WP_REST_Request $request Request object.
	 * @return \WC_Payment_Gateway
	 */
	private function get_request_payment_method( \WP_REST_Request $request ) {
		$payment_method_id  = $this->get_request_payment_method_id( $request );
		$available_gateways = WC()->payment_gateways->get_available_payment_gateways();

		if ( ! isset( $available_gateways[ $payment_method_id ] ) ) {
			throw new RouteException(
				'woocommerce_rest_checkout_payment_method_disabled',
				__( 'This payment gateway is not available.', 'woocommerce' ),
				400
			);
		}

		return $available_gateways[ $payment_method_id ];
	}

	/**
	 * Gets and formats payment request data.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return array
	 */
	private function get_request_payment_data( \WP_REST_Request $request ) {
		static $payment_data = [];
		if ( ! empty( $payment_data ) ) {
			return $payment_data;
		}
		if ( ! empty( $request['payment_data'] ) ) {
			foreach ( $request['payment_data'] as $data ) {
				$payment_data[ sanitize_key( $data['key'] ) ] = wc_clean( $data['value'] );
			}
		}

		return $payment_data;
	}

	/**
	 * Order processing relating to customer account.
	 *
	 * Creates a customer account as needed (based on request & store settings) and  updates the order with the new customer ID.
	 * Updates the order with user details (e.g. address).
	 *
	 * @throws RouteException API error object with error details.
	 * @param \WP_REST_Request $request Request object.
	 */
	private function process_customer( \WP_REST_Request $request ) {
		try {
			$create_account = Package::container()->get( CreateAccount::class );
			$create_account->from_order_request( $request );
			$this->order->set_customer_id( get_current_user_id() );
			$this->order->save();
		} catch ( \Exception $error ) {
			switch ( $error->getMessage() ) {
				case 'registration-error-invalid-email':
					throw new RouteException(
						'registration-error-invalid-email',
						__( 'Please provide a valid email address.', 'woocommerce' ),
						400
					);
				case 'registration-error-email-exists':
					throw new RouteException(
						'registration-error-email-exists',
						__( 'An account is already registered with your email address. Please log in before proceeding.', 'woocommerce' ),
						400
					);
			}
		}

		// Persist customer address data to account.
		$this->order_controller->sync_customer_data_with_order( $this->order );
	}
}
