<?php
/**
 * Checkout route.
 *
 * @package WooCommerce/Blocks
 */

namespace Automattic\WooCommerce\Blocks\RestApi\StoreApi\Routes;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Blocks\RestApi\StoreApi\Utilities\CartController;
use Automattic\WooCommerce\Blocks\Payments\PaymentResult;
use Automattic\WooCommerce\Blocks\Payments\PaymentContext;

/**
 * Checkout class.
 */
class Checkout extends AbstractRoute {
	/**
	 * Get the namespace for this route.
	 *
	 * @return string
	 */
	public function get_namespace() {
		return 'wc/store';
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
	 * Get method arguments for this REST route.
	 *
	 * @return array An array of endpoints.
	 */
	public function get_args() {
		return [
			[
				'methods'  => \WP_REST_Server::CREATABLE,
				'callback' => [ $this, 'get_response' ],
				'args'     => [
					'order_id'       => [
						'description' => __( 'The order ID being processed.', 'woo-gutenberg-products-block' ),
						'type'        => 'number',
					],
					'order_key'      => [
						'description' => __( 'The order key; used to validate the order is valid.', 'woo-gutenberg-products-block' ),
						'type'        => 'string',
					],
					'payment_method' => [
						'description' => __( 'The ID of the payment method being used to process the payment.', 'woo-gutenberg-products-block' ),
						'type'        => 'string',
					],
					'payment_data'   => [
						'description' => __( 'Data needed to take payment via the chosen payment method. This is passed through to the gateway when processing the payment for the order.', 'woo-gutenberg-products-block' ),
						'type'        => 'array',
						'items'       => [
							'type'       => 'object',
							'properties' => [
								'key'   => [
									'type' => 'string',
								],
								'value' => [
									'type' => 'string',
								],
							],
						],
					],
				],
			],
			'schema' => [ $this->schema, 'get_public_item_schema' ],
		];
	}

	/**
	 * Process a given order and generate a response for the endpoint.
	 *
	 * @throws RouteException On error.
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	protected function get_route_post_response( \WP_REST_Request $request ) {
		$order = $this->get_request_order_object( $request );

		$this->update_order_before_payment( $order, $request );

		if ( ! $order->needs_payment() ) {
			$payment_result = $this->process_without_payment( $order, $request );
		} else {
			$payment_result = $this->process_payment( $order, $request );
		}

		$response = $this->prepare_item_for_response(
			[
				'order_id'       => $order->get_id(),
				'payment_result' => $payment_result,
			],
			$request
		);

		switch ( $payment_result->status ) {
			case 'success':
				$response->set_status( 200 );
				break;
			case 'pending':
				$response->set_status( 202 );
				break;
			case 'failure':
				$response->set_status( 400 );
				break;
			case 'error':
				$response->set_status( 500 );
				break;
		}

		return $response;
	}

	/**
	 * For orders which do not require payment, just update status.
	 *
	 * @param \WC_Order        $order Order object.
	 * @param \WP_REST_Request $request Request object.
	 * @return PaymentResult
	 */
	protected function process_without_payment( \WC_Order $order, \WP_REST_Request $request ) {
		$order->payment_complete();

		return new PaymentResult( 'success' );
	}

	/**
	 * Fires an action hook instructing active payment gateways to process the payment for an order and provide a result.
	 *
	 * @throws RouteException On error.
	 * @param \WC_Order        $order Order object.
	 * @param \WP_REST_Request $request Request object.
	 * @return PaymentResult
	 */
	protected function process_payment( \WC_Order $order, \WP_REST_Request $request ) {
		$context = new PaymentContext();
		$result  = new PaymentResult();

		$context->set_order( $order );
		$context->set_payment_method( $this->get_request_payment_method_id( $request ) );
		$context->set_payment_data( $this->get_request_payment_data( $request ) );

		try {
			/**
			 * Process payment with context.
			 *
			 * @hook woocommerce_rest_checkout_process_payment_with_context
			 *
			 * @throws \Exception If there is an error taking payment, an Exception object can be thrown
			 *                                     with an error message.
			 *
			 * @param PaymentContext $context Holds context for the payment, including order ID and payment method.
			 * @param PaymentResult  $result Result object for the transaction.
			 */
			do_action( 'woocommerce_rest_checkout_process_payment_with_context', $context, $result );

			if ( ! $result instanceof PaymentResult ) {
				throw new RouteException( 'woocommerce_rest_checkout_invalid_payment_result', __( 'Invalid payment result received from payment method.', 'woo-gutenberg-products-block' ), 500 );
			}

			return $result;
		} catch ( \Exception $e ) {
			throw new RouteException( 'woocommerce_rest_checkout_process_payment_error', $e->getMessage(), 400 );
		}
	}

	/**
	 * Updates the order object before processing payment.
	 *
	 * @param \WC_Order        $order Order object.
	 * @param \WP_REST_Request $request Request object.
	 */
	protected function update_order_before_payment( \WC_Order $order, \WP_REST_Request $request ) {
		$this->update_customer_data_from_order( $order );

		$order->set_payment_method( $this->get_request_payment_method( $request ) );
		$order->set_status( 'pending' );
		$order->save();
	}

	/**
	 * Copies order data to customer data, so values persist for future checkouts.
	 *
	 * @param \WC_Order $order Order object.
	 */
	protected function update_customer_data_from_order( \WC_Order $order ) {
		if ( $order->get_customer_id() ) {
			$customer = new \WC_Customer( $order->get_customer_id() );
			$customer->set_props(
				[
					'billing_first_name' => $order->get_billing_first_name(),
					'billing_last_name'  => $order->get_billing_last_name(),
					'billing_company'    => $order->get_billing_company(),
					'billing_address_1'  => $order->get_billing_address_1(),
					'billing_address_2'  => $order->get_billing_address_2(),
					'billing_city'       => $order->get_billing_city(),
					'billing_state'      => $order->get_billing_state(),
					'billing_postcode'   => $order->get_billing_postcode(),
					'billing_country'    => $order->get_billing_country(),
					'billing_email'      => $order->get_billing_email(),
					'billing_phone'      => $order->get_billing_phone(),
					'shipping_address_1' => $order->get_shipping_address_1(),
					'shipping_address_2' => $order->get_shipping_address_2(),
					'shipping_city'      => $order->get_shipping_city(),
					'shipping_state'     => $order->get_shipping_state(),
					'shipping_postcode'  => $order->get_shipping_postcode(),
					'shipping_country'   => $order->get_shipping_country(),
				]
			);
			$customer->save();
		};
	}

	/**
	 * Gets the order object for the request, or throws an exception if invalid.
	 *
	 * @throws RouteException On error.
	 * @param \WP_REST_Request $request Request object.
	 * @return \WC_Order
	 */
	protected function get_request_order_object( \WP_REST_Request $request ) {
		$order_id = absint( $request['order_id'] );

		if ( ! $order_id ) {
			throw new RouteException( 'woocommerce_rest_checkout_missing_order_id', __( 'An order ID is required.', 'woo-gutenberg-products-block' ), 404 );
		}

		$order              = wc_get_order( $order_id );
		$order_key          = wp_unslash( $request['order_key'] );
		$order_key_is_valid = $order && hash_equals( $order->get_order_key(), $order_key );

		if ( ! $order_key_is_valid ) {
			throw new RouteException( 'woocommerce_rest_checkout_invalid_order', __( 'Invalid order. Please provide a valid order ID and key.', 'woo-gutenberg-products-block' ), 400 );
		}

		$statuses_for_payment = array_unique( apply_filters( 'woocommerce_valid_order_statuses_for_payment', [ 'checkout-draft', 'pending', 'failed' ] ) );

		if ( ! $order->has_status( $statuses_for_payment ) ) {
			throw new RouteException(
				'woocommerce_rest_checkout_invalid_order',
				sprintf(
					// Translators: %1$s list of order stati. %2$s Current order status.
					__( 'Only orders with status %1$s can be paid for. This order is %2$s.', 'woo-gutenberg-products-block' ),
					'`' . implode( '`, `', $statuses_for_payment ) . '`',
					$order->get_status()
				),
				400
			);
		}

		return $order;
	}

	/**
	 * Gets the chosen payment method ID from the request.
	 *
	 * @throws RouteException On error.
	 * @param \WP_REST_Request $request Request object.
	 * @return string
	 */
	protected function get_request_payment_method_id( \WP_REST_Request $request ) {
		$payment_method = wc_clean( wp_unslash( $request['payment_method'] ) );
		$valid_methods  = WC()->payment_gateways->get_payment_gateway_ids();

		if ( ! in_array( $payment_method, $valid_methods, true ) ) {
			throw new RouteException(
				'woocommerce_rest_checkout_invalid_payment_method',
				sprintf(
					// Translators: %s list of gateway ids.
					__( 'Invalid payment method provided. Please provide one of the following: %s', 'woo-gutenberg-products-block' ),
					'`' . implode( '`, `', $valid_methods ) . '`'
				),
				400
			);
		}

		return $payment_method;
	}

	/**
	 * Gets the chosen payment method from the request.
	 *
	 * @throws RouteException On error.
	 * @param \WP_REST_Request $request Request object.
	 * @return \WC_Payment_Gateway
	 */
	protected function get_request_payment_method( \WP_REST_Request $request ) {
		$payment_method        = $this->get_request_payment_method_id( $request );
		$gateways              = WC()->payment_gateways->payment_gateways();
		$payment_method_object = isset( $gateways[ $payment_method ] ) ? $gateways[ $payment_method ] : false;

		// The abstract gateway is available method uses the cart global, so instead, check enabled directly.
		if ( ! $payment_method_object || ! wc_string_to_bool( $payment_method_object->enabled ) ) {
			throw new RouteException(
				'woocommerce_rest_checkout_payment_method_disabled',
				__( 'This payment gateway is not available.', 'woo-gutenberg-products-block' ),
				400
			);
		}

		return $payment_method_object;
	}

	/**
	 * Gets and formats payment request data.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return array
	 */
	protected function get_request_payment_data( \WP_REST_Request $request ) {
		$payment_data = [];

		if ( ! empty( $request['payment_data'] ) ) {
			foreach ( $request['payment_data'] as $data ) {
				$payment_data[ sanitize_key( $data['key'] ) ] = wc_clean( $data['value'] );
			}
		}

		return $payment_data;
	}
}
