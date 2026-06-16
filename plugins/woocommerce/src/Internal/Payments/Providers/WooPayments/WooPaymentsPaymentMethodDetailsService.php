<?php
/**
 * WooPaymentsPaymentMethodDetailsService class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Payments\Providers\WooPayments;

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
	 * Initialize the class instance.
	 *
	 * @internal
	 *
	 * @param WooPaymentsLegacyRuntime $legacy_runtime WooPayments legacy runtime.
	 */
	final public function init( WooPaymentsLegacyRuntime $legacy_runtime ): void {
		$this->legacy_runtime = $legacy_runtime;
	}

	/**
	 * Get payment method details from the active WooPayments runtime.
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
