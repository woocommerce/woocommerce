<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\StoreApi\Utilities;

use Automattic\Jetpack\Constants;
use WP_Error;

/**
 * Builds safe Store API responses for unexpected engine failures.
 *
 * @internal
 */
final class UnexpectedErrorResponse {
	/**
	 * Maximum number of backtrace frames recorded in the log context.
	 */
	private const MAX_BACKTRACE_FRAMES = 10;

	/**
	 * Log an engine failure and create its Store API response.
	 *
	 * @since 11.3.0
	 *
	 * @param \Throwable  $error           Unexpected engine failure.
	 * @param string      $failure_context Context in which the failure occurred.
	 * @param string|null $public_message  Message shown to clients without debug access.
	 * @return WP_Error
	 */
	public static function create( \Throwable $error, string $failure_context, ?string $public_message = null ): WP_Error {
		$log_message = sprintf(
			'Store API request failed in %1$s: %2$s: %3$s in %4$s:%5$d',
			$failure_context,
			get_class( $error ),
			$error->getMessage(),
			$error->getFile(),
			$error->getLine()
		);

		try {
			wc_get_logger()->critical(
				$log_message,
				array(
					'source'         => 'store-api',
					'error'          => array(
						'file' => $error->getFile(),
						'line' => $error->getLine(),
					),
					// Same shape the fatal-error shutdown handler logs, so log handlers and readers see one trace format.
					'backtrace'      => array_slice( explode( "\n", $error->getTraceAsString() ), 0, self::MAX_BACKTRACE_FRAMES ),
					// These failures reached remote logging as fatals before the dispatchers caught them.
					'remote-logging' => true,
				)
			);
		} catch ( \Throwable $logging_error ) {
			// Logging must not prevent the safe response, but a failure that also broke logging should not vanish.
			error_log( $log_message . ' (logging failed: ' . $logging_error->getMessage() . ')' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}

		$message = $public_message ?? __( 'Internal server error', 'woocommerce' );
		$data    = array( 'status' => 500 );

		if ( self::can_expose_details() ) {
			$message                 = $error->getMessage();
			$data['exception_class'] = get_class( $error );
		}

		return new WP_Error( 'woocommerce_rest_unknown_server_error', $message, $data );
	}

	/**
	 * Whether the current request may receive the failure message and class.
	 *
	 * @return bool
	 */
	private static function can_expose_details(): bool {
		/**
		 * Filters whether unexpected Store API failures include the error message and exception class in the response.
		 *
		 * Details are only ever sent to users who can manage WooCommerce; this filter cannot bypass that check. It defaults to WP_DEBUG so store staff can debug a production store without enabling debug mode site-wide.
		 *
		 * @since 11.3.0
		 *
		 * @param bool $expose_error_details Whether to include the error message and exception class. Defaults to WP_DEBUG.
		 *
		 * @return bool
		 */
		$expose_error_details = (bool) apply_filters( 'woocommerce_store_api_expose_error_details', Constants::is_true( 'WP_DEBUG' ) );

		return $expose_error_details && current_user_can( 'manage_woocommerce' );
	}
}
