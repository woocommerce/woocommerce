<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\PushNotifications\Traits;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Internal\PushNotifications\PushNotifications;
use Automattic\WooCommerce\Proxies\LegacyProxy;
use Exception;
use WC_Data_Exception;
use WP_Error;
use WP_Http;

/**
 * Shared converter for REST controller catch blocks in the PushNotifications
 * module.
 *
 * Surfaces domain-specific `WC_Data_Exception` details for client-recoverable
 * failures (non-500 status codes), and logs + returns a generic
 * internal-error response for 500-level or unrecognized exceptions so that
 * internal details aren't leaked to API clients.
 */
trait ConvertsExceptionsToWpError {
	/**
	 * Convert an exception thrown by a service into a WP_Error suitable for
	 * the REST response.
	 *
	 * @param Exception $e The exception to convert.
	 * @return WP_Error
	 */
	protected function convert_exception_to_wp_error( Exception $e ): WP_Error {
		// Non-500 WC_Data_Exception: client-recoverable, surface details.
		if (
			$e instanceof WC_Data_Exception
			&& $e->getCode() !== WP_Http::INTERNAL_SERVER_ERROR
		) {
			return new WP_Error(
				$e->getErrorCode(),
				$e->getMessage(),
				$e->getErrorData()
			);
		}

		// Anything else: log and surface a generic 500.
		wc_get_container()
			->get( LegacyProxy::class )
			->call_function( 'wc_get_logger' )
			->error( $e->getMessage(), array( 'source' => PushNotifications::FEATURE_NAME ) );

		return new WP_Error(
			'woocommerce_internal_error',
			__( 'Internal server error', 'woocommerce' ),
			array( 'status' => WP_Http::INTERNAL_SERVER_ERROR )
		);
	}
}
