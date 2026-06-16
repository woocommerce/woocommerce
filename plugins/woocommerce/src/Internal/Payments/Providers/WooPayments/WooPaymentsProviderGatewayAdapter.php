<?php
/**
 * WooPaymentsProviderGatewayAdapter class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Payments\Providers\WooPayments;

use Automattic\WooCommerce\Internal\Payments\PaymentContext;
use Automattic\WooCommerce\Internal\Payments\PaymentOutcome;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\Api\WooPaymentsApiClient;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\Api\WooPaymentsApiException;
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
	 * WooPayments legacy runtime.
	 *
	 * @var WooPaymentsLegacyRuntime
	 */
	private WooPaymentsLegacyRuntime $legacy_runtime;

	/**
	 * Native WooPayments API client.
	 *
	 * @var WooPaymentsApiClient
	 */
	private WooPaymentsApiClient $api_client;

	/**
	 * Initialize the class instance.
	 *
	 * @internal
	 *
	 * @param WooPaymentsLegacyRuntime $legacy_runtime WooPayments legacy runtime.
	 * @param WooPaymentsApiClient     $api_client     Native WooPayments API client.
	 */
	final public function init( WooPaymentsLegacyRuntime $legacy_runtime, WooPaymentsApiClient $api_client ): void {
		$this->legacy_runtime = $legacy_runtime;
		$this->api_client     = $api_client;
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
		$order = $context->get_order();
		if ( $this->get_api_client()->is_available() ) {
			$charge_id = (string) $order->get_meta( '_charge_id', true );
			if ( '' !== $charge_id ) {
				$payment_data = $context->get_payment_data();
				$amount       = isset( $payment_data['amount'] ) ? (float) $payment_data['amount'] : 0.0;
				$reason       = isset( $payment_data['reason'] ) ? (string) $payment_data['reason'] : '';

				try {
					$result = $this->get_api_client()->refund_charge(
						$charge_id,
						$this->prepare_amount( $amount, (string) $order->get_currency() ),
						$reason,
						'woocommerce_native',
						$idempotency_key
					);

					return $this->normalize_refund_result( $result );
				} catch ( WooPaymentsApiException $exception ) {
					return $this->failed_transport_outcome( 'refund', $exception );
				}
			}
		}

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
		$order = $context->get_order();
		if ( $this->get_api_client()->is_available() ) {
			$intent_id = (string) $order->get_transaction_id();
			if ( '' !== $intent_id ) {
				try {
					$result = $this->get_api_client()->capture_intention(
						$intent_id,
						$this->prepare_amount( (float) $order->get_total(), (string) $order->get_currency() ),
						array()
					);

					return $this->normalize_capture_result( $result );
				} catch ( WooPaymentsApiException $exception ) {
					return $this->failed_transport_outcome( 'capture', $exception );
				}
			}
		}

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
		$order = $context->get_order();
		if ( $this->get_api_client()->is_available() ) {
			$intent_id = (string) $order->get_transaction_id();
			if ( '' !== $intent_id ) {
				try {
					$result = $this->get_api_client()->cancel_intention( $intent_id );

					return $this->normalize_cancel_result( $result );
				} catch ( WooPaymentsApiException $exception ) {
					return $this->failed_transport_outcome( 'cancel', $exception );
				}
			}
		}

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
		return $this->legacy_runtime->get_gateway();
	}

	/**
	 * Get the native API client.
	 *
	 * @return WooPaymentsApiClient
	 */
	private function get_api_client(): WooPaymentsApiClient {
		return $this->api_client;
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
	 * Normalize a native refund response.
	 *
	 * @param array<string,mixed> $result Native refund result.
	 * @return PaymentOutcome
	 */
	private function normalize_refund_result( array $result ): PaymentOutcome {
		return new PaymentOutcome(
			PaymentOutcome::STATUS_COMPLETED,
			isset( $result['id'] ) ? (string) $result['id'] : ''
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

	/**
	 * Build a failed outcome from a transport exception.
	 *
	 * @param string                  $operation Operation name.
	 * @param WooPaymentsApiException $exception Native transport exception.
	 * @return PaymentOutcome
	 */
	private function failed_transport_outcome( string $operation, WooPaymentsApiException $exception ): PaymentOutcome {
		return new PaymentOutcome(
			PaymentOutcome::STATUS_FAILED,
			'',
			'',
			'',
			'',
			array(
				'error_code'    => '' !== $exception->get_error_code() ? $exception->get_error_code() : 'wcpay_native_transport_failed',
				'error_message' => $exception->getMessage(),
				'operation'     => $operation,
			)
		);
	}

	/**
	 * Convert a decimal amount into provider minor units.
	 *
	 * @param float  $amount   Decimal amount.
	 * @param string $currency Order currency.
	 * @return int
	 */
	private function prepare_amount( float $amount, string $currency ): int {
		$conversion_rate = $this->is_zero_decimal_currency( $currency ) ? 1 : 100;

		return (int) round( $amount * $conversion_rate );
	}

	/**
	 * Tell whether the currency uses zero decimal places at the provider boundary.
	 *
	 * @param string $currency Currency code.
	 * @return bool
	 */
	private function is_zero_decimal_currency( string $currency ): bool {
		return in_array(
			strtolower( $currency ),
			array(
				'bif',
				'clp',
				'djf',
				'gnf',
				'jpy',
				'kmf',
				'krw',
				'mga',
				'pyg',
				'rwf',
				'vnd',
				'vuv',
				'xaf',
				'xof',
				'xpf',
			),
			true
		);
	}
}
