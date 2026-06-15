<?php
/**
 * WooPaymentsPaymentMethodDetailsService class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Payments\Providers\WooPayments;

use Automattic\WooCommerce\Proxies\LegacyProxy;
use Throwable;

/**
 * Retrieves WooPayments payment method details through a namespaced core seam.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native payments runtime.
 */
class WooPaymentsPaymentMethodDetailsService {

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

		if ( ! $this->legacy_proxy->call_function( 'class_exists', 'WC_Payments' ) ) {
			return array();
		}

		try {
			$api_client = $this->legacy_proxy->call_static( 'WC_Payments', 'get_payments_api_client' );
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
		$logger = $this->legacy_proxy->call_function( 'wc_get_logger' );
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
