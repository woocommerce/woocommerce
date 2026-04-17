<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS\Service;

defined( 'ABSPATH' ) || exit;

use WP_Error;

/**
 * Rate limiting for POS PIN authentication endpoints.
 *
 * Implements progressive lockouts: 30s after 5 failures, 5min after 10, 24h after 15.
 * Short-tier lockouts are stored in transients; the 24h tier is stored in wp_options so
 * it survives cache flushes and cannot be silently reset by object cache invalidation.
 *
 * @since 10.8.0
 */
class POSRateLimitService {

	const MAX_ATTEMPTS     = 20;
	const WINDOW_SECONDS   = 900;
	const TRANSIENT_PREFIX = '_wc_pos_rate_';
	const OPTION_PREFIX    = 'woocommerce_pos_pin_lockout_';

	private const LOCKOUT_THRESHOLDS = array(
		5  => 30,
		10 => 300,
		15 => DAY_IN_SECONDS,
	);

	/**
	 * Check whether the given IP is currently rate-limited.
	 *
	 * @since 10.8.0
	 * @param string $ip The client IP address.
	 * @return true|WP_Error True if allowed, WP_Error with 429 status if rate limited.
	 */
	public function check_rate_limit( string $ip ) {
		$long_lockout_until = $this->get_long_lockout_until( $ip );
		if ( null !== $long_lockout_until ) {
			if ( time() < $long_lockout_until ) {
				return new WP_Error(
					'woocommerce_pos_rate_limited',
					__(
						'Too many failed attempts. Please try again in 24 hours.',
						'woocommerce'
					),
					array(
						'status'      => 429,
						'retry_after' => $long_lockout_until - time(),
					)
				);
			}

			$this->clear_long_lockout( $ip );
		}

		$key  = $this->get_ip_key( $ip );
		$data = get_transient( $key );

		if ( false === $data ) {
			return true;
		}

		if ( isset( $data['lockout_until'] ) ) {
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

		$long_lockout_triggered = false;

		foreach ( array_reverse( self::LOCKOUT_THRESHOLDS, true ) as $threshold => $duration ) {
			if ( $data['attempts'] >= $threshold ) {
				if ( DAY_IN_SECONDS === $duration ) {
					$this->set_long_lockout( $ip, time() + DAY_IN_SECONDS );
					$long_lockout_triggered = true;
				} else {
					$data['lockout_until'] = time() + $duration;
				}
				break;
			}
		}

		if ( $long_lockout_triggered ) {
			delete_transient( $key );
			return;
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
		$this->clear_long_lockout( $ip );
	}

	/**
	 * Get the long (24h) lockout expiry timestamp if present and not yet expired in storage.
	 *
	 * @param string $ip The client IP address.
	 * @return int|null The timestamp when the lockout expires, or null if none is set.
	 */
	private function get_long_lockout_until( string $ip ): ?int {
		$value = get_option( $this->get_option_key( $ip ), false );

		if ( ! is_array( $value ) || ! isset( $value['until'] ) ) {
			return null;
		}

		return (int) $value['until'];
	}

	/**
	 * Persist the long (24h) lockout expiry in wp_options so cache flushes do not clear it.
	 *
	 * @param string $ip    The client IP address.
	 * @param int    $until Unix timestamp when the lockout expires.
	 */
	private function set_long_lockout( string $ip, int $until ): void {
		update_option(
			$this->get_option_key( $ip ),
			array( 'until' => $until ),
			false
		);
	}

	/**
	 * Remove the long (24h) lockout entry for the given IP.
	 *
	 * @param string $ip The client IP address.
	 */
	private function clear_long_lockout( string $ip ): void {
		delete_option( $this->get_option_key( $ip ) );
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

	/**
	 * Hash the IP to create an option-safe key for the 24h persistent lockout.
	 *
	 * @param string $ip The client IP address.
	 * @return string The option key.
	 */
	private function get_option_key( string $ip ): string {
		return self::OPTION_PREFIX . hash( 'sha256', $ip );
	}
}
