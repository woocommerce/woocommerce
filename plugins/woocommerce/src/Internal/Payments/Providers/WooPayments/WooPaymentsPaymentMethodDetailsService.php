<?php
/**
 * WooPaymentsPaymentMethodDetailsService class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Payments\Providers\WooPayments;

use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\Api\WooPaymentsApiClient;
use Throwable;

/**
 * Retrieves WooPayments payment method details through a namespaced core seam.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native payments runtime.
 */
class WooPaymentsPaymentMethodDetailsService {

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
	 * Get payment method details from the active WooPayments runtime or native transport.
	 *
	 * @since 11.0.0
	 *
	 * @param string $payment_method_id Payment method ID.
	 * @return array<string,mixed>
	 */
	public function get_payment_method_details( string $payment_method_id ): array {
		if ( '' === $payment_method_id ) {
			return array();
		}

		if ( ! $this->legacy_runtime->is_loaded() ) {
			return $this->get_native_payment_method_details( $payment_method_id );
		}

		return $this->get_legacy_payment_method_details( $payment_method_id );
	}

	/**
	 * Get payment method details from the active WooPayments plugin runtime.
	 *
	 * @param string $payment_method_id Payment method ID.
	 * @return array<string,mixed>
	 */
	private function get_legacy_payment_method_details( string $payment_method_id ): array {
		try {
			$api_client = $this->legacy_runtime->get_payments_api_client();
			if ( ! is_object( $api_client ) || ! is_callable( array( $api_client, 'get_payment_method' ) ) ) {
				return array();
			}

			$details = $api_client->get_payment_method( $payment_method_id );

			return is_array( $details ) ? $details : array();
		} catch ( Throwable $exception ) {
			$this->log_fetch_error( $payment_method_id, $exception );
			return array();
		}
	}

	/**
	 * Get payment method details from the native transport.
	 *
	 * @param string $payment_method_id Payment method ID.
	 * @return array<string,mixed>
	 */
	private function get_native_payment_method_details( string $payment_method_id ): array {
		try {
			return $this->api_client->get_payment_method( $payment_method_id );
		} catch ( Throwable $exception ) {
			$this->log_fetch_error( $payment_method_id, $exception );
			return array();
		}
	}

	/**
	 * Log a payment method details fetch error.
	 *
	 * @param string    $payment_method_id Payment method ID.
	 * @param Throwable $exception         Exception.
	 */
	private function log_fetch_error( string $payment_method_id, Throwable $exception ): void {
		$logger = $this->legacy_runtime->get_logger();
		if ( ! is_object( $logger ) || ! is_callable( array( $logger, 'error' ) ) ) {
			return;
		}

		$logger->error(
			sprintf(
				'Error retrieving WooPayments payment method details for %s: %s',
				$payment_method_id,
				$exception->getMessage()
			),
			array(
				'source' => 'payment-info',
			)
		);
	}
}
