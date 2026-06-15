<?php
/**
 * WooPaymentsProviderGatewayAdapter class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Payments\Providers\WooPayments;

use Automattic\WooCommerce\Internal\Payments\PaymentContext;
use Automattic\WooCommerce\Internal\Payments\PaymentOutcome;
use Automattic\WooCommerce\Proxies\LegacyProxy;
use WC_Order;
use WP_Error;

/**
 * Normalizes WooPayments gateway operations to native payment outcomes.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native payments runtime.
 */
class WooPaymentsProviderGatewayAdapter {

	/**
	 * Legacy proxy.
	 *
	 * @var LegacyProxy
	 */
	private LegacyProxy $legacy_proxy;

	/**
	 * Initialize the class instance.
	 *
	 * @internal
	 *
	 * @param LegacyProxy $legacy_proxy Legacy proxy.
	 */
	final public function init( LegacyProxy $legacy_proxy ): void {
		$this->legacy_proxy = $legacy_proxy;
	}

	/**
	 * Tell whether the legacy bridge can currently process operations.
	 *
	 * @return bool
	 */
	public function is_available(): bool {
		$gateway = $this->get_legacy_gateway();
		if ( ! is_object( $gateway ) ) {
			return false;
		}

		if ( is_callable( array( $gateway, 'is_available' ) ) ) {
			return (bool) $gateway->is_available();
		}

		return true;
	}

	/**
	 * Charge an order through the active WooPayments gateway.
	 *
	 * @since 11.0.0
	 *
	 * @param PaymentContext $context         Payment context.
	 * @param string         $idempotency_key Deterministic idempotency key.
	 * @return PaymentOutcome
	 */
	public function charge( PaymentContext $context, string $idempotency_key ): PaymentOutcome {
		$gateway = $this->get_legacy_gateway();
		if ( ! is_object( $gateway ) || ! is_callable( array( $gateway, 'process_payment' ) ) ) {
			return $this->unavailable_outcome( 'charge' );
		}

		$result = $this->with_idempotency_key(
			$idempotency_key,
			static function () use ( $gateway, $context ) {
				return $gateway->process_payment( $context->get_order_id() );
			}
		);

		return $this->normalize_charge_result( is_array( $result ) ? $result : null, $context );
	}

	/**
	 * Refund an order through the active WooPayments gateway.
	 *
	 * @since 11.0.0
	 *
	 * @param PaymentContext $context         Payment context.
	 * @param string         $idempotency_key Deterministic idempotency key.
	 * @return PaymentOutcome
	 */
	public function refund( PaymentContext $context, string $idempotency_key ): PaymentOutcome {
		$gateway = $this->get_legacy_gateway();
		if ( ! is_object( $gateway ) || ! is_callable( array( $gateway, 'process_refund' ) ) ) {
			return $this->unavailable_outcome( 'refund' );
		}

		$payment_data = $context->get_payment_data();
		$amount       = isset( $payment_data['amount'] ) ? (float) $payment_data['amount'] : 0.0;
		$reason       = isset( $payment_data['reason'] ) ? (string) $payment_data['reason'] : '';
		$result       = $this->with_idempotency_key(
			$idempotency_key,
			static function () use ( $gateway, $context, $amount, $reason ) {
				return $gateway->process_refund( $context->get_order_id(), $amount, $reason );
			}
		);

		if ( true === $result ) {
			return new PaymentOutcome( PaymentOutcome::STATUS_COMPLETED );
		}

		if ( $result instanceof WP_Error ) {
			return new PaymentOutcome(
				PaymentOutcome::STATUS_FAILED,
				'',
				'',
				'',
				'',
				array(
					'error_code'    => $result->get_error_code(),
					'error_message' => $result->get_error_message(),
				)
			);
		}

		return new PaymentOutcome(
			PaymentOutcome::STATUS_FAILED,
			'',
			'',
			'',
			'',
			array( 'error_code' => 'legacy_refund_failed' )
		);
	}

	/**
	 * Capture an authorized payment through the active WooPayments gateway.
	 *
	 * @since 11.0.0
	 *
	 * @param PaymentContext $context         Payment context.
	 * @param string         $idempotency_key Deterministic idempotency key.
	 * @return PaymentOutcome
	 */
	public function capture( PaymentContext $context, string $idempotency_key ): PaymentOutcome {
		$gateway = $this->get_legacy_gateway();
		if ( ! is_object( $gateway ) || ! is_callable( array( $gateway, 'capture_charge' ) ) ) {
			return $this->unavailable_outcome( 'capture' );
		}

		$result = $this->with_idempotency_key(
			$idempotency_key,
			static function () use ( $gateway, $context ) {
				return $gateway->capture_charge( $context->get_order() );
			}
		);

		return $this->normalize_capture_result( is_array( $result ) ? $result : array() );
	}

	/**
	 * Cancel an authorized payment through the active WooPayments gateway.
	 *
	 * @since 11.0.0
	 *
	 * @param PaymentContext $context         Payment context.
	 * @param string         $idempotency_key Deterministic idempotency key.
	 * @return PaymentOutcome
	 */
	public function cancel( PaymentContext $context, string $idempotency_key ): PaymentOutcome {
		$gateway = $this->get_legacy_gateway();
		if ( ! is_object( $gateway ) || ! is_callable( array( $gateway, 'cancel_authorization' ) ) ) {
			return $this->unavailable_outcome( 'cancel' );
		}

		$result = $this->with_idempotency_key(
			$idempotency_key,
			static function () use ( $gateway, $context ) {
				return $gateway->cancel_authorization( $context->get_order() );
			}
		);

		return $this->normalize_cancel_result( is_array( $result ) ? $result : array() );
	}

	/**
	 * Run a legacy gateway operation with a scoped WooPayments API idempotency key.
	 *
	 * @param string   $idempotency_key Deterministic idempotency key.
	 * @param callable $operation       Operation callback.
	 * @return mixed
	 */
	private function with_idempotency_key( string $idempotency_key, callable $operation ) {
		$idempotency_filter = static function ( $params, $api = '', $method = '' ) use ( $idempotency_key ) {
			unset( $api );

			if ( '' === $idempotency_key || ! is_array( $params ) || in_array( strtoupper( (string) $method ), array( 'GET', 'DELETE' ), true ) ) {
				return $params;
			}

			$params['idempotency_key'] = $idempotency_key;

			return $params;
		};

		add_filter( 'wcpay_api_request_params', $idempotency_filter, 10, 3 );

		try {
			return $operation();
		} finally {
			remove_filter( 'wcpay_api_request_params', $idempotency_filter, 10 );
		}
	}

	/**
	 * Get the active legacy WooPayments gateway.
	 *
	 * @return object|null
	 */
	private function get_legacy_gateway(): ?object {
		if ( ! $this->legacy_proxy->call_function( 'class_exists', 'WC_Payments' ) ) {
			return null;
		}

		$gateway = $this->legacy_proxy->call_static( 'WC_Payments', 'get_gateway' );

		return is_object( $gateway ) ? $gateway : null;
	}

	/**
	 * Normalize a process_payment result.
	 *
	 * @param array<string,mixed>|null $result  Legacy result.
	 * @param PaymentContext           $context Payment context.
	 * @return PaymentOutcome
	 */
	private function normalize_charge_result( ?array $result, PaymentContext $context ): PaymentOutcome {
		if ( null === $result ) {
			return new PaymentOutcome(
				PaymentOutcome::STATUS_FAILED,
				'',
				'',
				'',
				'',
				array( 'error_code' => 'legacy_process_payment_empty_response' )
			);
		}

		if ( 'success' !== ( $result['result'] ?? '' ) ) {
			return new PaymentOutcome(
				PaymentOutcome::STATUS_FAILED,
				'',
				'',
				'',
				'',
				array( 'error_code' => 'legacy_process_payment_failed' )
			);
		}

		$redirect              = isset( $result['redirect'] ) ? (string) $result['redirect'] : '';
		$payment_method_id     = isset( $result['payment_method'] ) ? (string) $result['payment_method'] : $context->get_payment_method_id();
		$fresh_order           = wc_get_order( $context->get_order_id() );
		$order                 = $fresh_order instanceof WC_Order ? $fresh_order : $context->get_order();
		$provider_payment_id   = (string) $order->get_meta( '_intent_id', true );
		$stored_payment_method = (string) $order->get_meta( '_payment_method_id', true );
		$intention_status      = (string) $order->get_meta( '_intention_status', true );
		$data                  = array();

		if ( '' === $payment_method_id ) {
			$payment_method_id = $stored_payment_method;
		}

		if ( array_key_exists( 'redirect', $result ) ) {
			$data['checkout_redirect'] = $redirect;
		}

		if ( str_starts_with( $redirect, '#wcpay-confirm-' ) ) {
			return new PaymentOutcome( PaymentOutcome::STATUS_REQUIRES_CUSTOMER_ACTION, $provider_payment_id, $redirect, $payment_method_id, '', $data );
		}

		if ( '' !== $redirect && ! $this->is_order_received_redirect( $order, $redirect ) ) {
			return new PaymentOutcome( PaymentOutcome::STATUS_REQUIRES_REDIRECT, $provider_payment_id, $redirect, $payment_method_id, '', $data );
		}

		$status = $this->map_intention_status_to_outcome_status( $intention_status );
		if ( '' === $status && '' === $provider_payment_id && 0.0 < (float) $order->get_total() && '' === $redirect ) {
			$status = PaymentOutcome::STATUS_PENDING_ASYNC;
		}

		return new PaymentOutcome(
			'' === $status ? PaymentOutcome::STATUS_COMPLETED : $status,
			$provider_payment_id,
			$redirect,
			$payment_method_id,
			'',
			$data
		);
	}

	/**
	 * Map a persisted WooPayments intention status to a neutral payment outcome.
	 *
	 * @param string $intention_status WooPayments intention status.
	 * @return string Empty string when the status does not imply a different outcome.
	 */
	private function map_intention_status_to_outcome_status( string $intention_status ): string {
		switch ( $intention_status ) {
			case 'succeeded':
				return PaymentOutcome::STATUS_COMPLETED;

			case 'requires_capture':
			case 'processing':
				return PaymentOutcome::STATUS_AUTHORIZED;

			case 'requires_action':
			case 'requires_confirmation':
				return PaymentOutcome::STATUS_REQUIRES_CUSTOMER_ACTION;

			case 'requires_payment_method':
				return PaymentOutcome::STATUS_FAILED;

			case 'canceled':
				return PaymentOutcome::STATUS_CANCELED;
		}

		return '';
	}

	/**
	 * Normalize a capture result.
	 *
	 * @param array<string,mixed> $result Legacy capture result.
	 * @return PaymentOutcome
	 */
	private function normalize_capture_result( array $result ): PaymentOutcome {
		$status     = isset( $result['status'] ) ? (string) $result['status'] : 'failed';
		$intent_id  = isset( $result['id'] ) ? (string) $result['id'] : '';
		$error_code = isset( $result['error_code'] ) ? (string) $result['error_code'] : '';
		$message    = isset( $result['message'] ) ? (string) $result['message'] : '';

		if ( 'succeeded' === $status ) {
			return new PaymentOutcome( PaymentOutcome::STATUS_COMPLETED, $intent_id );
		}

		if ( 'requires_capture' === $status && '' === $message ) {
			return new PaymentOutcome( PaymentOutcome::STATUS_AUTHORIZED, $intent_id );
		}

		return new PaymentOutcome(
			PaymentOutcome::STATUS_FAILED,
			$intent_id,
			'',
			'',
			'',
			array(
				'error_code'    => $error_code,
				'error_message' => $message,
			)
		);
	}

	/**
	 * Normalize a cancel authorization result.
	 *
	 * @param array<string,mixed> $result Legacy cancel result.
	 * @return PaymentOutcome
	 */
	private function normalize_cancel_result( array $result ): PaymentOutcome {
		$status    = isset( $result['status'] ) ? (string) $result['status'] : 'failed';
		$intent_id = isset( $result['id'] ) ? (string) $result['id'] : '';
		$message   = isset( $result['message'] ) ? (string) $result['message'] : '';

		if ( 'canceled' === $status ) {
			return new PaymentOutcome( PaymentOutcome::STATUS_CANCELED, $intent_id );
		}

		return new PaymentOutcome(
			PaymentOutcome::STATUS_FAILED,
			$intent_id,
			'',
			'',
			'',
			array(
				'error_code'    => 'legacy_cancel_authorization_failed',
				'error_message' => $message,
			)
		);
	}

	/**
	 * Tell whether a redirect URL is the order received URL.
	 *
	 * @param WC_Order $order    Order object.
	 * @param string   $redirect Redirect URL.
	 * @return bool
	 */
	private function is_order_received_redirect( WC_Order $order, string $redirect ): bool {
		return '' !== $redirect && $redirect === $order->get_checkout_order_received_url();
	}

	/**
	 * Build a failed outcome for unavailable legacy gateway calls.
	 *
	 * @param string $operation Operation name.
	 * @return PaymentOutcome
	 */
	private function unavailable_outcome( string $operation ): PaymentOutcome {
		return new PaymentOutcome(
			PaymentOutcome::STATUS_FAILED,
			'',
			'',
			'',
			'',
			array(
				'error_code' => 'wcpay_gateway_unavailable',
				'operation'  => $operation,
			)
		);
	}
}
