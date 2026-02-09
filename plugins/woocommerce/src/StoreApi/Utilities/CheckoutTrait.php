<?php
declare( strict_types = 1);
namespace Automattic\WooCommerce\StoreApi\Utilities;

use Automattic\Jetpack\Constants;
use Automattic\WooCommerce\StoreApi\Exceptions\RouteException;
use Automattic\WooCommerce\StoreApi\Payments\PaymentContext;
use Automattic\WooCommerce\StoreApi\Payments\PaymentResult;
use Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFieldsSchema\DocumentObject;
use Automattic\WooCommerce\Admin\Features\Features;
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
	 * For orders which do not require payment, just update status.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @param PaymentResult    $payment_result Payment result object.
	 */
	private function process_without_payment( \WP_REST_Request $request, PaymentResult $payment_result ) {
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
	 * @param \WP_REST_Request $request Full details about the request.
	 */
	private function update_order_from_request( \WP_REST_Request $request ) {
		$this->order->set_customer_note( wc_sanitize_textarea( $request['customer_note'] ) ?? '' );
		$payment_method = $this->get_request_payment_method( $request );
		if ( null !== $payment_method ) {
			WC()->session->set( 'chosen_payment_method', $payment_method->id );
			$this->order->set_payment_method( $payment_method->id );
			$this->order->set_payment_method_title( $payment_method->title );
		} elseif ( ! $this->order->needs_payment() ) {
			$this->order->set_payment_method( '' );
		}
		wc_log_order_step(
			'[Store API #5::update_order_from_request] Set customer note and payment method',
			array(
				'order_id' => $this->order->get_id(),
				'payment'  => $this->order->get_payment_method_title(),
			)
		);
		$this->persist_additional_fields_for_order( $request );
		wc_log_order_step(
			'[Store API #5::update_order_from_request] Persisted additional fields',
			array(
				'order_id' => $this->order->get_id(),
				'payment'  => $this->order->get_payment_method_title(),
			)
		);

		wc_do_deprecated_action(
			'__experimental_woocommerce_blocks_checkout_update_order_from_request',
			array(
				$this->order,
				$request,
			),
			'6.3.0',
			'woocommerce_store_api_checkout_update_order_from_request',
			'This action was deprecated in WooCommerce Blocks version 6.3.0. Please use woocommerce_store_api_checkout_update_order_from_request instead.'
		);

		wc_do_deprecated_action(
			'woocommerce_blocks_checkout_update_order_from_request',
			array(
				$this->order,
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
		do_action( 'woocommerce_store_api_checkout_update_order_from_request', $this->order, $request );

		$this->order->save();
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
	 * @param \WP_REST_Request $request Full details about the request.
	 */
	private function persist_additional_fields_for_order( \WP_REST_Request $request ) {
		if ( Features::is_enabled( 'experimental-blocks' ) ) {
			$document_object = $this->get_document_object_from_rest_request( $request );
			$document_object->set_context( 'order' );
			$additional_fields_order   = $this->additional_fields_controller->get_contextual_fields_for_location( 'order', $document_object );
			$additional_fields_contact = $this->additional_fields_controller->get_contextual_fields_for_location( 'contact', $document_object );
			$additional_fields         = array_merge( $additional_fields_order, $additional_fields_contact );
		} else {
			$additional_fields_order   = $this->additional_fields_controller->get_fields_for_location( 'order' );
			$additional_fields_contact = $this->additional_fields_controller->get_fields_for_location( 'contact' );
			$additional_fields         = array_merge( $additional_fields_order, $additional_fields_contact );
		}

		$field_values = (array) $request['additional_fields'] ?? [];

		foreach ( $additional_fields as $key => $field ) {
			if ( isset( $field_values[ $key ] ) ) {
				$this->additional_fields_controller->persist_field_for_order( $key, $field_values[ $key ], $this->order, 'other', false );
			}
		}

		// The above logic sets visible fields, but not hidden fields. Unset the hidden fields here.
		$other_posted_field_values = array_diff_key( $field_values, $additional_fields );

		foreach ( $other_posted_field_values as $key => $value ) {
			if ( $this->additional_fields_controller->is_field( $key ) ) {
				$this->additional_fields_controller->persist_field_for_order( $key, '', $this->order, 'other', false );
			}
		}

		// We need to sync the customer additional fields with the order otherwise they will be overwritten on next page load.
		if ( 0 !== $this->order->get_customer_id() && get_current_user_id() === $this->order->get_customer_id() ) {
			$this->additional_fields_controller->sync_customer_additional_fields_with_order( $this->order, wc()->customer );
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
