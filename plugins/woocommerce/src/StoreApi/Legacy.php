<?php
namespace Automattic\WooCommerce\StoreApi;

use Automattic\WooCommerce\StoreApi\Payments\PaymentContext;
use Automattic\WooCommerce\StoreApi\Payments\PaymentResult;
use Automattic\WooCommerce\StoreApi\Utilities\NoticeHandler;
use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\StoreApi\Exceptions\RouteException;
/**
 * Legacy class.
 */
class Legacy {
	/**
	 * Hook into WP lifecycle events.
	 */
	public function init() {
		add_action( 'woocommerce_rest_checkout_process_payment_with_context', array( $this, 'process_legacy_payment' ), 999, 2 );
	}

	/**
	 * Attempt to process a payment for the checkout API if no payment methods support the
	 * woocommerce_rest_checkout_process_payment_with_context action.
	 *
	 * @param PaymentContext $context Holds context for the payment.
	 * @param PaymentResult  $result  Result of the payment.
	 *
	 * @throws RouteException If the gateway returns an explicit error message.
	 */
	public function process_legacy_payment( PaymentContext $context, PaymentResult &$result ) {
		if ( $result->status ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification
		$post_data = $_POST;

		// Set constants.
		wc_maybe_define_constant( 'WOOCOMMERCE_CHECKOUT', true );

		// Add the payment data from the API to the POST global.
		$_POST = $context->payment_data;

		// Call the process payment method of the chosen gateway.
		$payment_method_object = $context->get_payment_method_instance();

		if ( ! $payment_method_object instanceof \WC_Payment_Gateway ) {
			return;
		}

		$payment_method_object->validate_fields();

		// If errors were thrown, we need to abort.
		NoticeHandler::convert_notices_to_exceptions( 'woocommerce_rest_payment_error' );

		// Process Payment.
		$gateway_result = $payment_method_object->process_payment( $context->order->get_id() );

		// Restore $_POST data.
		$_POST = $post_data;

		// If the payment failed with a message, throw an exception.
		if ( isset( $gateway_result['result'] ) && 'failure' === $gateway_result['result'] ) {
			if ( isset( $gateway_result['message'] ) ) {
				throw new RouteException( 'woocommerce_rest_payment_error', esc_html( wp_strip_all_tags( $gateway_result['message'] ) ), 400 );
			} else {
				NoticeHandler::convert_notices_to_exceptions( 'woocommerce_rest_payment_error' );
			}
		}

		// Handle result. If status was not returned we consider this invalid and return failure.
		$result_status = $gateway_result['result'] ?? 'failure';
		// These are the same statuses supported by the API and indicate processing status. This is not the same as order status.
		$valid_status = array( 'success', 'failure', 'pending', 'error' );
		$result->set_status( in_array( $result_status, $valid_status, true ) ? $result_status : 'failure' );

		// If `process_payment` added notices but didn't set the status to failure, clear them. Notices are not displayed from the API unless status is failure.
		wc_clear_notices();

		// set payment_details from result.
		$result->set_payment_details( array_merge( $result->payment_details, $gateway_result ) );
		$result->set_redirect_url( $gateway_result['redirect'] ?? '' );
	}
}
