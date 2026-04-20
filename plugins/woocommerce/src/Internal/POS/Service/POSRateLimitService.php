<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS\Service;

defined( 'ABSPATH' ) || exit;

use WP_Error;

/**
 * Rate limiting for POS PIN authentication endpoints.
 *
 * Implements progressive lockouts: 30s after 5 failures, 5min after 10, 24h after 15.
 * All state (attempt counter and lockout expiry) is stored in wp_options so it
 * survives object-cache evictions and cache flushes. Relying on the transient
 * API here would let a managed-host cache eviction silently reset the counter
 * and defeat the long-tier lockout that depends on it.
 *
 * @since 10.8.0
 */
class POSRateLimitService {

	const MAX_ATTEMPTS   = 20;
	const WINDOW_SECONDS = 900;
	const OPTION_PREFIX  = 'woocommerce_pos_pin_lockout_';

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
		$data = $this->read( $ip );
		if ( null === $data ) {
			return true;
		}

		$now = time();

		if ( ! empty( $data['lockout_until'] ) && $now < (int) $data['lockout_until'] ) {
			$retry_after = (int) $data['lockout_until'] - $now;
			return new WP_Error(
				'woocommerce_pos_rate_limited',
				$this->lockout_message( $retry_after ),
				array(
					'status'      => 429,
					'retry_after' => $retry_after,
				)
			);
		}

		// Lockout elapsed or never set. Prune window-expired data so a genuine
		// user isn't permanently counted against.
		if ( ! empty( $data['lockout_until'] ) && $now >= (int) $data['lockout_until'] ) {
			unset( $data['lockout_until'] );
		}
		if ( isset( $data['window_started_at'] ) && $now - (int) $data['window_started_at'] > self::WINDOW_SECONDS ) {
			$this->clear( $ip );
			return true;
		}

		if ( isset( $data['attempts'] ) && (int) $data['attempts'] >= self::MAX_ATTEMPTS ) {
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
		$now  = time();
		$data = $this->read( $ip );

		if ( null === $data || ( isset( $data['window_started_at'] ) && $now - (int) $data['window_started_at'] > self::WINDOW_SECONDS ) ) {
			$data = array(
				'attempts'          => 0,
				'window_started_at' => $now,
			);
		}

		++$data['attempts'];

		foreach ( array_reverse( self::LOCKOUT_THRESHOLDS, true ) as $threshold => $duration ) {
			if ( $data['attempts'] >= $threshold ) {
				$data['lockout_until'] = $now + $duration;
				break;
			}
		}

		$this->write( $ip, $data );
	}

	/**
	 * Clear the rate limit data for the given IP (admin reset).
	 *
	 * @since 10.8.0
	 * @param string $ip The client IP address.
	 */
	public function reset( string $ip ): void {
		$this->clear( $ip );
	}

	/**
	 * Read the rate-limit record for an IP from wp_options.
	 *
	 * @param string $ip The client IP address.
	 * @return array|null
	 */
	private function read( string $ip ): ?array {
		$value = get_option( $this->get_option_key( $ip ), null );
		return is_array( $value ) ? $value : null;
	}

	/**
	 * Persist the rate-limit record for an IP to wp_options.
	 *
	 * @param string $ip   The client IP address.
	 * @param array  $data The data to persist.
	 */
	private function write( string $ip, array $data ): void {
		update_option( $this->get_option_key( $ip ), $data, false );
	}

	/**
	 * Remove the rate-limit record for an IP.
	 *
	 * @param string $ip The client IP address.
	 */
	private function clear( string $ip ): void {
		delete_option( $this->get_option_key( $ip ) );
	}

	/**
	 * Hash the IP to create an option-safe key.
	 *
	 * @param string $ip The client IP address.
	 * @return string The option key.
	 */
	private function get_option_key( string $ip ): string {
		return self::OPTION_PREFIX . hash( 'sha256', $ip );
	}

	/**
	 * Human-readable lockout message tuned to the remaining duration.
	 *
	 * @param int $retry_after Seconds until the lockout expires.
	 * @return string
	 */
	private function lockout_message( int $retry_after ): string {
		if ( $retry_after >= HOUR_IN_SECONDS ) {
			return __(
				'Too many failed attempts. Please try again in 24 hours.',
				'woocommerce'
			);
		}
		return __(
			'Too many failed attempts. Please try again later.',
			'woocommerce'
		);
	}
}
