<?php
declare( strict_types = 1);
namespace Automattic\WooCommerce\StoreApi\Utilities;

use Automattic\Jetpack\Constants;
use Automattic\WooCommerce\StoreApi\Exceptions\RouteException;
use Automattic\WooCommerce\StoreApi\Payments\PaymentContext;
use Automattic\WooCommerce\StoreApi\Payments\PaymentResult;
use Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFieldsSchema\DocumentObject;
use Automattic\WooCommerce\Admin\Features\Features;
use Automattic\WooCommerce\Enums\OrderStatus;
use WC_Customer;

/**
 * CheckoutTrait
 *
 * Shared functionality for checkout route.
 */
trait CheckoutTrait {
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
	 * Returns the order being processed, throwing if it hasn't been materialised yet.
	 *
	 * Use the returned `WC_Order` (rather than `$this->order`) for type-safe access in
	 * the rest of the calling method.
	 *
	 * @throws RouteException If `$this->order` is null.
	 * @return \WC_Order
	 */
	private function get_order_or_throw(): \WC_Order {
		if ( ! $this->order instanceof \WC_Order ) {
			throw new RouteException(
				'woocommerce_rest_checkout_missing_order',
				esc_html__( 'Unable to create order', 'woocommerce' ),
				500
			);
		}
		return $this->order;
	}

	/**
	 * For orders which do not require payment, just update status.
	 *
	 * There is deliberately no recovery here of the kind process_payment() does. Nothing was
	 * charged, so a failure costs the shopper only a retry, and reporting success on an order
	 * whose completion actually failed would be worse than reporting the failure.
	 *
	 * @throws RouteException If the order is missing.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @param PaymentResult    $payment_result Payment result object.
	 */
	private function process_without_payment( \WP_REST_Request $request, PaymentResult $payment_result ) {
		$order = $this->get_order_or_throw();

		$order->payment_complete();

		// Mark the payment as successful.
		$payment_result->set_status( 'success' );
		$payment_result->set_redirect_url( $order->get_checkout_order_received_url() );
	}

	/**
	 * Fires an action hook instructing active payment gateways to process the payment for an order and provide a result.
	 *
	 * @throws RouteException If the order is missing, or on payment error.
	 * @throws \Throwable If a gateway raised an Error rather than an Exception, and no payment was taken.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @param PaymentResult    $payment_result Payment result object.
	 */
	private function process_payment( \WP_REST_Request $request, PaymentResult $payment_result ) {
		$order = $this->get_order_or_throw();

		$session = WC()->session;
		$session->set( 'order_awaiting_payment', $order->get_id() );
		// Persist before invoking gateways because redirects or stalled requests may prevent the session from being saved on shutdown.
		if ( is_callable( array( $session, 'save_data' ) ) ) {
			$session->save_data();
		}

		/*
		 * Recovery writes the success status and redirect onto this instance rather than whatever
		 * the hook below leaves in $payment_result. The hook takes the result by reference and can
		 * replace it, including with a value that is not a PaymentResult at all, and the caller
		 * keeps this instance either way: it is the one serialised into the response.
		 */
		$result_for_recovery = $payment_result;

		try {
			// Prepare the payment context object to pass through payment hooks.
			$context = new PaymentContext();
			$context->set_payment_method( $this->get_request_payment_method_id( $request ) );
			$context->set_payment_data( $this->get_request_payment_data( $request ) );
			$context->set_order( $order );

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
		} catch ( \Throwable $e ) {
			/*
			 * The gateway may have taken payment before this failure happened, for example when a
			 * post-payment integration throws while the order status is transitioning. Reporting a
			 * failure would send the shopper back to place the order again, and every retry takes
			 * payment for another order. Record what went wrong and report the success the order
			 * actually represents.
			 */
			// Re-read once here: a gateway that advanced the order may have done so on its own
			// instance, leaving the one held above reporting a stale status.
			$order = $this->refresh_order( $order );

			if ( $this->order_moved_past_payment( $order ) ) {
				$this->recover_order_that_took_payment( $order, $e, $result_for_recovery );
				return;
			}

			// Errors are not part of the gateway contract, so let them surface as they did before.
			if ( ! $e instanceof \Exception ) {
				throw $e;
			}

			$additional_data = [];

			// phpcs:disable WooCommerce.Commenting.CommentHooks.MissingSinceComment
			/**
			 * Allows to check if WP_DEBUG mode is enabled before returning previous Exception.
			 *
			 * @param bool The WP_DEBUG mode.
			 */
			if ( apply_filters( 'woocommerce_return_previous_exceptions', Constants::is_true( 'WP_DEBUG' ) ) && $e->getPrevious() ) {
				$additional_data = [
					'previous' => get_class( $e->getPrevious() ),
				];
			}

			throw new RouteException( 'woocommerce_rest_checkout_process_payment_error', esc_html( $e->getMessage() ), 400, array_map( 'esc_attr', $additional_data ) );
		}
	}

	/**
	 * Re-reads the order so its status reflects whatever the gateway persisted, falling back
	 * to the in-memory order when it can no longer be read.
	 *
	 * @param \WC_Order $order Order object.
	 * @return \WC_Order
	 */
	private function refresh_order( \WC_Order $order ): \WC_Order {
		$refreshed_order = wc_get_order( $order->get_id() );

		return $refreshed_order instanceof \WC_Order ? $refreshed_order : $order;
	}

	/**
	 * Whether the order has moved beyond the point of awaiting payment.
	 *
	 * An order reaches a gateway either awaiting payment or as a draft, so any other status
	 * means something moved it on. Statuses that represent an order going nowhere are listed
	 * too, since they are not a payment either.
	 *
	 * The name is deliberately about the status rather than the money: an order parked on-hold
	 * for manual review counts here even though nothing has been captured yet. What matters to
	 * the caller is that the order is no longer waiting to be paid, so sending the shopper back
	 * to place it again would be wrong.
	 *
	 * This reads the status the gateway persisted, not the payment processor, which is the same
	 * signal the rest of the checkout uses. It is a plain status check on purpose: needs_payment()
	 * would fold in the order total and two filters, so a fully discounted order or a site that
	 * filters the payable statuses could flip the answer.
	 *
	 * @param \WC_Order $order Order object.
	 * @return bool
	 */
	private function order_moved_past_payment( \WC_Order $order ): bool {
		return ! $order->has_status(
			array(
				OrderStatus::CHECKOUT_DRAFT,
				OrderStatus::PENDING,
				OrderStatus::FAILED,
				OrderStatus::CANCELLED,
				OrderStatus::REFUNDED,
				OrderStatus::TRASH,
			)
		);
	}

	/**
	 * Records a failure that happened after payment was taken and reports the checkout as
	 * successful, so the shopper is sent to the order confirmation rather than back to retry.
	 *
	 * @param \WC_Order     $order          Order object.
	 * @param \Throwable    $error          The failure raised after payment was taken.
	 * @param PaymentResult $payment_result Payment result object.
	 */
	private function recover_order_that_took_payment( \WC_Order $order, \Throwable $error, PaymentResult $payment_result ): void {
		/*
		 * The failure is carried in the message rather than the context: the file log handler
		 * renders context with wp_json_encode(), and neither WC_Order nor Throwable exposes public
		 * properties, so passing the objects alone would write an empty {}. This is the only log of
		 * the error, since the checkout now reports success and the route's failure step never runs.
		 */
		wc_get_logger()->error(
			sprintf(
				'Checkout for order #%1$d failed after payment was taken: %2$s: %3$s in %4$s:%5$d',
				$order->get_id(),
				get_class( $error ),
				$error->getMessage(),
				$error->getFile(),
				$error->getLine()
			),
			array( 'source' => 'store-api' )
		);

		$order->add_order_note(
			sprintf(
				/* translators: %s: the error that was raised after payment was taken. */
				__( 'Checkout could not be completed after payment was taken: %s', 'woocommerce' ),
				wp_strip_all_tags( $error->getMessage() )
			)
		);

		$payment_result->set_status( 'success' );

		/*
		 * A redirect the gateway set before it threw is kept. It is the only statement of where the
		 * gateway wanted the shopper, and an order can reach here still needing the shopper to act,
		 * for example one parked on-hold awaiting a 3DS challenge: overwriting that would walk them
		 * past the authentication step. The order confirmation is the fallback for the common case,
		 * where the gateway threw before setting anything.
		 */
		if ( '' === $payment_result->get_redirect_url() ) {
			$payment_result->set_redirect_url( $order->get_checkout_order_received_url() );
		}

		/*
		 * The gateway did not reach the point where it empties the cart, so do it here. Without
		 * this the shopper lands on the confirmation page with the order still in their cart, and
		 * can place it a second time.
		 *
		 * Only when the cart is still the one this order was built from. Pay-for-order goes through
		 * the same trait with a cart that has nothing to do with the order, and a shopper checking
		 * out in a second tab has moved the cart on. Same guard core uses in wc_clear_cart_after_payment()
		 * and in the offline gateways.
		 */
		if ( WC()->cart && $order->has_cart_hash( WC()->cart->get_cart_hash() ) ) {
			WC()->cart->empty_cart();
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
		$payment_method = $this->get_request_payment_method( $request );
		return is_null( $payment_method ) ? '' : $payment_method->id;
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
	 * Update the current order using the posted values from the request.
	 *
	 * Called only with a real, persisted order — either the place-order POST flow or
	 * the rare failed-payment PATCH retry flow where `get_draft_order()` resolved to
	 * an existing `pending`/`failed` order from the customer's session. Fresh-session
	 * PATCHes never call this method; they go through the no-order draft path.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @param bool             $persist Whether to persist the changes right away (defaults to true).
	 * @throws RouteException If the order is missing, or if the order requires a payment method on POST and none was supplied.
	 */
	private function update_order_from_request( \WP_REST_Request $request, bool $persist = true ) {
		$order = $this->get_order_or_throw();

		$order->set_customer_note( wc_sanitize_textarea( $request['customer_note'] ) ?? '' );
		$payment_method = $this->get_request_payment_method( $request );
		if ( null !== $payment_method ) {
			WC()->session->set( 'chosen_payment_method', $payment_method->id );
			$order->set_payment_method( $payment_method->id );
			$order->set_payment_method_title( $payment_method->title );
		} else {
			$order_needs_payment = $order->needs_payment();
			if ( $order_needs_payment && 'POST' === $request->get_method() ) {
				throw new RouteException(
					'woocommerce_rest_checkout_missing_payment_method',
					esc_html__( 'No payment method provided.', 'woocommerce' ),
					400
				);
			}
			if ( ! $order_needs_payment ) {
				$order->set_payment_method( '' );
			}
		}
		wc_log_order_step(
			'[Store API #5::update_order_from_request] Set customer note and payment method',
			array(
				'order_id' => $order->get_id(),
				'payment'  => $order->get_payment_method_title(),
			)
		);
		$this->persist_additional_fields_for_order( $request );
		wc_log_order_step(
			'[Store API #5::update_order_from_request] Persisted additional fields',
			array(
				'order_id' => $order->get_id(),
				'payment'  => $order->get_payment_method_title(),
			)
		);

		wc_do_deprecated_action(
			'__experimental_woocommerce_blocks_checkout_update_order_from_request',
			array(
				$order,
				$request,
			),
			'6.3.0',
			'woocommerce_store_api_checkout_update_order_from_request',
			'This action was deprecated in WooCommerce Blocks version 6.3.0. Please use woocommerce_store_api_checkout_update_order_from_request instead.'
		);

		wc_do_deprecated_action(
			'woocommerce_blocks_checkout_update_order_from_request',
			array(
				$order,
				$request,
			),
			'7.2.0',
			'woocommerce_store_api_checkout_update_order_from_request',
			'This action was deprecated in WooCommerce Blocks version 7.2.0. Please use woocommerce_store_api_checkout_update_order_from_request instead.'
		);

		/**
		 * Fires when the Checkout Block/Store API updates an order's from the API request data.
		 *
		 * This hook gives extensions the chance to update orders based on the data in the request. This can be used in
		 * conjunction with the ExtendSchema class to post custom data and then process it.
		 *
		 * @since 7.2.0
		 *
		 * @param \WC_Order $order Order object.
		 * @param \WP_REST_Request $request Full details about the request.
		 */
		do_action( 'woocommerce_store_api_checkout_update_order_from_request', $order, $request );

		if ( $persist ) {
			$order->save();
		}
	}

	/**
	 * Gets the chosen payment method title from the request.
	 *
	 * @throws RouteException On error.
	 * @param \WP_REST_Request $request Request object.
	 * @return string
	 */
	private function get_request_payment_method_title( \WP_REST_Request $request ) {
		$payment_method = $this->get_request_payment_method( $request );
		return is_null( $payment_method ) ? '' : $payment_method->get_title();
	}

	/**
	 * Persist additional fields for the order after validating them.
	 *
	 * @throws RouteException If the order is missing.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 */
	private function persist_additional_fields_for_order( \WP_REST_Request $request ) {
		// Local alias so the closure and downstream calls keep a non-null `WC_Order`.
		$order = $this->get_order_or_throw();

		$this->resolve_and_persist_additional_fields(
			$request,
			function ( string $key, $value ) use ( $order ) {
				$this->additional_fields_controller->persist_field_for_order( $key, $value, $order, 'other', false );
			}
		);

		if ( 0 !== $order->get_customer_id() && get_current_user_id() === $order->get_customer_id() ) {
			$this->additional_fields_controller->sync_customer_additional_fields_with_order( $order, wc()->customer );
		}
	}

	/**
	 * Persist additional fields for the customer session.
	 *
	 * Counterpart to `persist_additional_fields_for_order` for routes that operate
	 * without a persisted order (e.g. the deferred-draft PATCH path).
	 *
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 */
	private function persist_additional_fields_for_customer( \WP_REST_Request $request ): void {
		$customer = wc()->customer;

		$this->resolve_and_persist_additional_fields(
			$request,
			function ( string $key, $value ) use ( $customer ) {
				$this->additional_fields_controller->persist_field_for_customer( $key, $value, $customer, 'other' );
			}
		);

		$customer->save();
	}

	/**
	 * Resolve the additional checkout fields from the request and persist each one
	 * via the supplied callback. Fields hidden by conditional logic that were still
	 * posted are cleared (passed with an empty value).
	 *
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @param callable         $persist Callback invoked as `$persist( string $key, mixed $value )` for each field.
	 */
	private function resolve_and_persist_additional_fields( \WP_REST_Request $request, callable $persist ): void {
		if ( Features::is_enabled( 'experimental-blocks' ) ) {
			$document_object = $this->get_document_object_from_rest_request( $request );
			$document_object->set_context( 'order' );
			$additional_fields = array_merge(
				$this->additional_fields_controller->get_contextual_fields_for_location( 'order', $document_object ),
				$this->additional_fields_controller->get_contextual_fields_for_location( 'contact', $document_object )
			);
		} else {
			$additional_fields = array_merge(
				$this->additional_fields_controller->get_fields_for_location( 'order' ),
				$this->additional_fields_controller->get_fields_for_location( 'contact' )
			);
		}

		$field_values = isset( $request['additional_fields'] ) ? (array) $request['additional_fields'] : array();

		foreach ( $additional_fields as $key => $field ) {
			if ( isset( $field_values[ $key ] ) ) {
				$persist( $key, $field_values[ $key ] );
			}
		}

		$hidden_posted_field_values = array_diff_key( $field_values, $additional_fields );
		foreach ( $hidden_posted_field_values as $key => $value ) {
			if ( $this->additional_fields_controller->is_field( $key ) ) {
				$persist( $key, '' );
			}
		}
	}

	/**
	 * Returns a document object from a REST request.
	 *
	 * @param \WP_REST_Request $request The REST request.
	 * @return DocumentObject The document object or null if experimental blocks are not enabled.
	 */
	public function get_document_object_from_rest_request( \WP_REST_Request $request ) {
		return new DocumentObject(
			[
				'customer' => [
					'billing_address'   => $request['billing_address'],
					'shipping_address'  => $request['shipping_address'],
					'additional_fields' => array_intersect_key(
						$request['additional_fields'] ?? [],
						array_flip( $this->additional_fields_controller->get_contact_fields_keys() )
					),
				],
				'checkout' => [
					'payment_method'    => $request['payment_method'],
					'create_account'    => $request['create_account'],
					'customer_note'     => $request['customer_note'],
					'additional_fields' => array_intersect_key(
						$request['additional_fields'] ?? [],
						array_flip( $this->additional_fields_controller->get_order_fields_keys() )
					),
				],
			]
		);
	}
}
