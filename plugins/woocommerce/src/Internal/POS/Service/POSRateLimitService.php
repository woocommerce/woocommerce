<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS\Service;

defined( 'ABSPATH' ) || exit;

use WP_Error;

/**
 * Rate limiting for POS PIN authentication endpoints using WordPress transients.
 *
 * Implements progressive lockouts: 30s after 5 failures, 5min after 10, permanent after 15.
 *
 * @since 10.8.0
 */
class POSRateLimitService {

	const MAX_ATTEMPTS    = 20;
	const WINDOW_SECONDS  = 900;
	const TRANSIENT_PREFIX = '_wc_pos_rate_';

	private const LOCKOUT_THRESHOLDS = array(
		5  => 30,
		10 => 300,
		15 => -1,
	);

	/**
	 * Check whether the given IP is currently rate-limited.
	 *
	 * @since 10.8.0
	 * @param string $ip The client IP address.
	 * @return true|WP_Error True if allowed, WP_Error with 429 status if rate limited.
	 */
	public function check_rate_limit( string $ip ) {
		$key  = $this->get_ip_key( $ip );
		$data = get_transient( $key );

		if ( false === $data ) {
			return true;
		}

		if ( isset( $data['lockout_until'] ) ) {
			if ( -1 === $data['lockout_until'] ) {
				return new WP_Error(
					'woocommerce_pos_rate_limited',
					__(
						'Too many failed attempts. Contact an administrator to reset.',
						'woocommerce'
					),
					array( 'status' => 429 )
				);
			}

			if ( time() < $data['lockout_until'] ) {
				$retry_after = $data['lockout_until'] - time();
				return new WP_Error(
					'woocommerce_pos_rate_limited',
					__(
						'Too many failed attempts. Please try again later.',
						'woocommerce'
					),
					array(
						'status'      => 429,
						'retry_after' => $retry_after,
					)
				);
			}

			unset( $data['lockout_until'] );
			set_transient( $key, $data, self::WINDOW_SECONDS );
		}

		if ( isset( $data['attempts'] ) && $data['attempts'] >= self::MAX_ATTEMPTS ) {
			return new WP_Error(
				'woocommerce_pos_rate_limited',
				__(
					'Too many failed attempts. Please try again later.',
					'woocommerce'
				),
				array( 'status' => 429 )
			);
		}

		return true;
	}

	/**
	 * Record a failed PIN attempt for the given IP.
	 *
	 * @since 10.8.0
	 * @param string $ip The client IP address.
	 */
	public function record_failure( string $ip ): void {
		$key  = $this->get_ip_key( $ip );
		$data = get_transient( $key );

		if ( false === $data ) {
			$data = array( 'attempts' => 0 );
		}

		++$data['attempts'];

		foreach ( array_reverse( self::LOCKOUT_THRESHOLDS, true ) as $threshold => $duration ) {
			if ( $data['attempts'] >= $threshold ) {
				$data['lockout_until'] = -1 === $duration
					? -1
					: time() + $duration;
				break;
			}
		}

		set_transient( $key, $data, self::WINDOW_SECONDS );
	}

	/**
	 * Clear the rate limit data for the given IP (admin reset).
	 *
	 * @since 10.8.0
	 * @param string $ip The client IP address.
	 */
	public function reset( string $ip ): void {
		delete_transient( $this->get_ip_key( $ip ) );
	}

	/**
	 * Hash the IP to create a transient-safe key.
	 *
	 * @param string $ip The client IP address.
	 * @return string The transient key.
	 */
	private function get_ip_key( string $ip ): string {
		return self::TRANSIENT_PREFIX . hash( 'sha256', $ip );
	}
}
