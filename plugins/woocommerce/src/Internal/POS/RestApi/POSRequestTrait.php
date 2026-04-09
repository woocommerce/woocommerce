<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS\RestApi;

defined( 'ABSPATH' ) || exit;

/**
 * Shared request utilities for POS REST API controllers.
 *
 * @since 10.8.0
 * @internal
 */
trait POSRequestTrait {

	/**
	 * Pad response time to a minimum duration to prevent timing attacks.
	 *
	 * @since 10.8.0
	 * @param float $start_time  The microtime at request start.
	 * @param float $min_seconds Minimum response time in seconds.
	 */
	private function pad_response_time( float $start_time, float $min_seconds = 0.5 ): void {
		$elapsed = microtime( true ) - $start_time;
		if ( $elapsed < $min_seconds ) {
			usleep( (int) ( ( $min_seconds - $elapsed ) * 1_000_000 ) );
		}
	}

	/**
	 * Get the client IP address from the request.
	 *
	 * @since 10.8.0
	 * @return string
	 */
	private function get_client_ip(): string {
		if ( class_exists( 'WC_Geolocation' ) ) {
			$ip = \WC_Geolocation::get_ip_address();
			if ( '' !== $ip ) {
				return $ip;
			}
		}

		return sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0' ) );
	}
}
