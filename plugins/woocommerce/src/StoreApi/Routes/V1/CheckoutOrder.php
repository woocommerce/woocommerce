<?php
namespace Automattic\WooCommerce\StoreApi\Routes\V1;

use Automattic\WooCommerce\StoreApi\Payments\PaymentResult;
use Automattic\WooCommerce\StoreApi\Exceptions\InvalidStockLevelsInCartException;
use Automattic\WooCommerce\StoreApi\Exceptions\RouteException;
use Automattic\WooCommerce\StoreApi\Utilities\OrderAuthorizationTrait;
use Automattic\WooCommerce\StoreApi\Utilities\CheckoutTrait;

/**
 * CheckoutOrder class.
 */
class CheckoutOrder extends AbstractCartRoute {
	use OrderAuthorizationTrait;
	use CheckoutTrait;

	/**
	 * The route identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'checkout-order';

	/**
	 * The routes schema.
	 *
	 * @var string
	 */
	const SCHEMA_TYPE = 'checkout-order';

	/**
	 * Holds the current order being processed.
	 *
	 * @var \WC_Order
	 */
	private $order = null;

	/**
	 * Get the path of this REST route.
	 *
	 * @return string
	 */
	public function get_path() {
		return self::get_path_regex();
	}

	/**
	 * Get the path of this rest route.
	 *
	 * @return string
	 */
	public static function get_path_regex() {
		return '/checkout/(?P<id>[\d]+)';
	}

	/**
	 * Get method arguments for this REST route.
	 *
	 * @return array An array of endpoints.
	 */
	public function get_args() {
		return [
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'get_response' ],
				'permission_callback' => [ $this, 'is_authorized' ],
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
			'schema'      => [ $this->schema, 'get_public_item_schema' ],
			'allow_batch' => [ 'v1' => true ],
		];
	}

	/**
	 * Process an order.
	 *
	 * 1. Process Request
	 * 2. Process Customer
	 * 3. Validate Order
	 * 4. Process Payment
	 *
	 * @throws RouteException On error.
	 * @throws InvalidStockLevelsInCartException On error.
	 *
	 * @param \WP_REST_Request $request Request object.
	 *
	 * @return \WP_REST_Response
	 */
	protected function get_route_post_response( \WP_REST_Request $request ) {
		$order_id    = absint( $request['id'] );
		$this->order = wc_get_order( $order_id );

		if ( ! $this->order || ! $this->order->needs_payment() ) {
			return new \WP_Error(
				'invalid_order_update_status',
				__( 'This order cannot be paid for.', 'woocommerce' )
			);
		}

		/**
		 * Process request data.
		 *
		 * Note: Customer data is persisted from the request first so that OrderController::update_addresses_from_cart
		 * uses the up to date customer address.
		 */
		$this->update_billing_address( $request );
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
		$this->order_controller->validate_existing_order_before_payment( $this->order );

		/**
		 * Fires before an order is processed by the Checkout Block/Store API.
		 *
		 * This hook informs extensions that $order has completed processing and is ready for payment.
		 *
		 * This is similar to existing core hook woocommerce_checkout_order_processed. We're using a new action:
		 * - To keep the interface focused (only pass $order, not passing request data).
		 * - This also explicitly indicates these orders are from checkout block/StoreAPI.
		 *
		 * @since 7.2.0
		 *
		 * @see https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3238
		 * @example See docs/examples/checkout-order-processed.md

		 * @param \WC_Order $order Order object.
		 */
		do_action( 'woocommerce_store_api_checkout_order_processed', $this->order );

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
	 * Since this endpoint only operates on existing orders, we don't need to do updates based on
	 * the cart data.
	 *
	 * @param \WP_REST_Request $request Request object.
	 */
	protected function cart_updated( \WP_REST_Request $request ) {}

	/**
	 * Updates the current customer session using data from the request (e.g. address data).
	 *
	 * Address session data is synced to the order itself later on by OrderController::update_order_from_cart()
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 */
	private function update_billing_address( \WP_REST_Request $request ) {
		$customer = wc()->customer;
		$billing  = $request['billing_address'];
		$shipping = $request['shipping_address'];

		// Billing address is a required field.
		foreach ( $billing as $key => $value ) {
			if ( is_callable( [ $customer, "set_billing_$key" ] ) ) {
				$customer->{"set_billing_$key"}( $value );
			}
		}

		// If shipping address (optional field) was not provided, set it to the given billing address (required field).
		$shipping_address_values = $shipping ?? $billing;

		foreach ( $shipping_address_values as $key => $value ) {
			if ( is_callable( [ $customer, "set_shipping_$key" ] ) ) {
				$customer->{"set_shipping_$key"}( $value );
			} elseif ( 'phone' === $key ) {
				$customer->update_meta_data( 'shipping_phone', $value );
			}
		}

		/**
		 * Fires when the Checkout Block/Store API updates a customer from the API request data.
		 *
		 * @since 8.2.0
		 *
		 * @param \WC_Customer $customer Customer object.
		 * @param \WP_REST_Request $request Full details about the request.
		 */
		do_action( 'woocommerce_store_api_checkout_update_customer_from_request', $customer, $request );

		$customer->save();

		$this->order->set_billing_address( $billing );
		$this->order->set_shipping_address( $shipping );
		$this->order->save();
		$this->order->calculate_totals();
	}

	/**
	 * Gets the chosen payment method from the request.
	 *
	 * @throws RouteException On error.
	 * @param \WP_REST_Request $request Request object.
	 * @return \WC_Payment_Gateway|null
	 */
	private function get_request_payment_method( \WP_REST_Request $request ) {
		$available_gateways      = WC()->payment_gateways->get_available_payment_gateways();
		$request_payment_method  = wc_clean( wp_unslash( $request['payment_method'] ?? '' ) );
		$requires_payment_method = $this->order->needs_payment();

		if ( empty( $request_payment_method ) ) {
			if ( $requires_payment_method ) {
				throw new RouteException(
					'woocommerce_rest_checkout_missing_payment_method',
					__( 'No payment method provided.', 'woocommerce' ),
					400
				);
			}
			return null;
		}

		if ( ! isset( $available_gateways[ $request_payment_method ] ) ) {
			throw new RouteException(
				'woocommerce_rest_checkout_payment_method_disabled',
				sprintf(
					// Translators: %s Payment method ID.
					__( 'The %s payment gateway is not available.', 'woocommerce' ),
					esc_html( $request_payment_method )
				),
				400
			);
		}

		return $available_gateways[ $request_payment_method ];
	}

	/**
	 * Updates the order with user details (e.g. address).
	 *
	 * @throws RouteException API error object with error details.
	 * @param \WP_REST_Request $request Request object.
	 */
	private function process_customer( \WP_REST_Request $request ) {
		$this->order_controller->sync_customer_data_with_order( $this->order );
	}
}
