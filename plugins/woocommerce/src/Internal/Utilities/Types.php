<?php

namespace Automattic\WooCommerce\Internal\Utilities;

use WP_Error;

/**
 * Utilities to help ensure type safety.
 */
class Types {
	/**
	 * Checks if the $thing is either the expected type, or a WP Error.
	 *
	 * If it passes the test then $thing will be returned unmodified, otherwise a WP_Error is returned. This is helpful
	 * for ensuring return types are honored, but where the return value is filterable.
	 *
	 * Note that, in the context of REST API requests, the WP_Error may be exposed to the caller and therefore care
	 * should be taken not to expose sensitive information.
	 *
	 * @since 9.1.0
	 *
	 * @param mixed           $thing        The return value to be assessed.
	 * @param string          $desired_type What we expect the return type to be, if it is not a WP_Error.
	 * @param string|null     $message      Optional message used to form a WP_Error if the type checks fail.
	 * @param int|string|null $code         Optional code used to form a WP_Error if the type checks fail.
	 * @param bool            $data         If $thing should be supplied to the WP_Error. Defaults to false.
	 *
	 * @return mixed|WP_Error
	 */
	public static function ensure_is_type_or_wp_error( $thing, string $desired_type, string $message = null, $code = null, bool $data = false ) {
		// Is the $thing of the expected type?
		if ( $thing instanceof $desired_type || is_wp_error( $thing ) ) {
			return $thing;
		}

		// Log the problem, so the site operator has an opportunity to look at the source of the problem.
		$logger = wc_get_logger();

		// Where did the problem come from?
		$backtrace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS );
		$file      = __( 'Unknown source file', 'woocommerce');
		$line      = __( 'Unknown line', 'woocommerce' );

		if ( count( $backtrace ) < 1 ) {
			$file = $backtrace[0]['file'] ?? $file;
			$line = $backtrace[0]['line'] ?? $line;
		}

		if ( $logger ) {
			$logger->error(
				sprintf(
					/* translators: 1: file path 2: line number. */
					__( 'Return type could not be ensured. This may be the result of problematic code using a hook provided close to %1$s:%2$d.', 'woocommerce' ),
					$file,
					$line
				)
			);
		}

		// Generate a WP_Error that can be returned in place of the misshapen $thing. Note that the code, message etc
		// are deliberately vague by default, to avoid unintentional exposure of information to end users.
		return new WP_Error(
			$code ?? 'unexpected-error',
			$message ?? __( 'There was an unexpected problem generating the return value.', 'woocommerce' ),
			$data ? $thing : ''
		);
	}
}
