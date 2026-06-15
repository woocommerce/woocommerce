<?php
/**
 * PaymentExceptionPolicy class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Payments;

use Throwable;

/**
 * Normalizes provider exceptions for the native payments runtime.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native payments runtime.
 */
class PaymentExceptionPolicy {

	/**
	 * Convert an exception to a failed payment outcome.
	 *
	 * @since 11.0.0
	 *
	 * @param Throwable $exception Exception thrown by a provider.
	 * @return PaymentOutcome
	 */
	public function to_failed_outcome( Throwable $exception ): PaymentOutcome {
		return new PaymentOutcome(
			PaymentOutcome::STATUS_FAILED,
			'',
			'',
			'',
			'',
			array(
				'error_code'    => $this->get_exception_error_code( $exception ),
				'error_message' => $exception->getMessage(),
			)
		);
	}

	/**
	 * Get a provider error code from an exception when one exists.
	 *
	 * @param Throwable $exception Exception thrown by a provider.
	 * @return string
	 */
	private function get_exception_error_code( Throwable $exception ): string {
		if ( is_callable( array( $exception, 'get_error_code' ) ) ) {
			return (string) $exception->get_error_code();
		}

		return '';
	}
}
