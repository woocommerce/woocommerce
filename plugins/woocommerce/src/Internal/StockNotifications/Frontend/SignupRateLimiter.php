<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\StockNotifications\Frontend;

defined( 'ABSPATH' ) || exit;

/**
 * Transient-backed rate limiter for Back-in-Stock sign-ups.
 *
 * Keeps per-IP and per-email counters in separate transients. A call to
 * {@see record_attempt()} increments both counters and re-sets the TTL, giving a
 * sliding window: once a client has been flagged, they need `$window` seconds of
 * silence before the counter expires. A call to {@see is_rate_limited()} returns
 * true when either counter is at or above its threshold, so whichever scope
 * trips first blocks the request.
 *
 * Sliding (rather than fixed) window is deliberate: it's stricter against
 * sustained abuse — an attacker who keeps trying can't ride the clock back to
 * zero by spacing hits within the window.
 *
 * This is intentionally simpler than the bucket-based {@see \WC_Rate_Limiter}:
 * sign-ups only need a sliding fixed-window counter and transients give us that
 * with built-in TTL handling.
 *
 * @internal
 */
class SignupRateLimiter {

	/**
	 * Transient prefix for the per-IP counter.
	 */
	public const IP_PREFIX = 'wc_bis_rl_ip_';

	/**
	 * Transient prefix for the per-email counter.
	 */
	public const EMAIL_PREFIX = 'wc_bis_rl_email_';

	/**
	 * Default maximum attempts per IP per window.
	 */
	public const DEFAULT_MAX_ATTEMPTS_PER_IP = 5;

	/**
	 * Default maximum attempts per email per window.
	 */
	public const DEFAULT_MAX_ATTEMPTS_PER_EMAIL = 3;

	/**
	 * Default window length in seconds (10 minutes).
	 */
	public const DEFAULT_WINDOW_SECONDS = 600;

	/**
	 * Whether the given IP + email combination has hit the rate limit.
	 *
	 * Note: this check and the caller-side {@see record_attempt()} in
	 * {@see FormHandlerService::handle_signup()} are non-atomic — two concurrent
	 * requests can each pass this check and each then increment, so the counter
	 * can drift one or two past the threshold. That's deliberate: this is a
	 * coarse abuse control, not a strict semaphore, and paying for an atomic
	 * increment on every sign-up wouldn't meaningfully change the defense.
	 *
	 * @param string $ip    Client IP.
	 * @param string $email Customer email.
	 * @return bool True if either scope is at or above its threshold.
	 */
	public function is_rate_limited( string $ip, string $email ): bool {
		$ip_key    = $this->ip_key( $ip );
		$email_key = $this->email_key( $email );

		if ( '' !== $ip_key ) {
			$ip_count = (int) get_transient( $ip_key );
			if ( $ip_count >= $this->max_attempts_per_ip() ) {
				return true;
			}
		}

		if ( '' !== $email_key ) {
			$email_count = (int) get_transient( $email_key );
			if ( $email_count >= $this->max_attempts_per_email() ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Record a sign-up attempt for the given IP + email.
	 *
	 * Each scope has its own counter. The TTL is re-set on every increment so
	 * the window slides with activity — a client that keeps trying has to go
	 * silent for `$window` seconds before the counter expires.
	 *
	 * @param string $ip    Client IP.
	 * @param string $email Customer email.
	 */
	public function record_attempt( string $ip, string $email ): void {
		$window = $this->window_seconds();

		$ip_key = $this->ip_key( $ip );
		if ( '' !== $ip_key ) {
			$this->increment( $ip_key, $window );
		}

		$email_key = $this->email_key( $email );
		if ( '' !== $email_key ) {
			$this->increment( $email_key, $window );
		}
	}

	/**
	 * Clear the counter for a specific IP.
	 *
	 * Primarily used by tests to avoid depending on wall-clock TTL.
	 *
	 * @param string $ip Client IP.
	 */
	public function reset_for_ip( string $ip ): void {
		$key = $this->ip_key( $ip );
		if ( '' !== $key ) {
			delete_transient( $key );
		}
	}

	/**
	 * Clear the counter for a specific email.
	 *
	 * @param string $email Customer email.
	 */
	public function reset_for_email( string $email ): void {
		$key = $this->email_key( $email );
		if ( '' !== $key ) {
			delete_transient( $key );
		}
	}

	/**
	 * Transient key for the per-IP counter.
	 *
	 * Returns an empty string when the IP cannot be determined, in which case
	 * the per-IP scope is skipped.
	 *
	 * @param string $ip Client IP.
	 * @return string
	 */
	private function ip_key( string $ip ): string {
		$ip = trim( $ip );
		if ( '' === $ip ) {
			return '';
		}

		return self::IP_PREFIX . md5( $ip );
	}

	/**
	 * Transient key for the per-email counter.
	 *
	 * Returns an empty string when the email is empty, in which case the
	 * per-email scope is skipped.
	 *
	 * @param string $email Customer email.
	 * @return string
	 */
	private function email_key( string $email ): string {
		$email = strtolower( trim( $email ) );
		if ( '' === $email ) {
			return '';
		}

		return self::EMAIL_PREFIX . md5( $email );
	}

	/**
	 * Increment a counter transient, re-setting its TTL on every hit.
	 *
	 * Re-setting the TTL is what makes this a sliding window — the counter
	 * only expires after `$window` seconds of silence. It also means that if
	 * the transient was evicted by the object cache between hits, the next
	 * increment re-establishes a sane TTL automatically.
	 *
	 * @param string $key    Transient key.
	 * @param int    $window Window length in seconds.
	 */
	private function increment( string $key, int $window ): void {
		$current = (int) get_transient( $key );
		$next    = $current > 0 ? $current + 1 : 1;
		set_transient( $key, $next, $window );
	}

	/**
	 * Maximum attempts per IP within the window.
	 *
	 * @return int
	 */
	private function max_attempts_per_ip(): int {
		/**
		 * Filter the per-IP sign-up attempt threshold.
		 *
		 * @since 10.8.0
		 *
		 * @param int $max Maximum number of sign-up attempts allowed per IP within the window.
		 */
		$max = (int) apply_filters( 'woocommerce_bis_signup_rate_limit_max_per_ip', self::DEFAULT_MAX_ATTEMPTS_PER_IP );
		return $max > 0 ? $max : self::DEFAULT_MAX_ATTEMPTS_PER_IP;
	}

	/**
	 * Maximum attempts per email within the window.
	 *
	 * @return int
	 */
	private function max_attempts_per_email(): int {
		/**
		 * Filter the per-email sign-up attempt threshold.
		 *
		 * @since 10.8.0
		 *
		 * @param int $max Maximum number of sign-up attempts allowed per email within the window.
		 */
		$max = (int) apply_filters( 'woocommerce_bis_signup_rate_limit_max_per_email', self::DEFAULT_MAX_ATTEMPTS_PER_EMAIL );
		return $max > 0 ? $max : self::DEFAULT_MAX_ATTEMPTS_PER_EMAIL;
	}

	/**
	 * Window length in seconds.
	 *
	 * @return int
	 */
	private function window_seconds(): int {
		/**
		 * Filter the sign-up rate-limiter window length, in seconds.
		 *
		 * @since 10.8.0
		 *
		 * @param int $seconds Window length in seconds.
		 */
		$window = (int) apply_filters( 'woocommerce_bis_signup_rate_limit_window_seconds', self::DEFAULT_WINDOW_SECONDS );
		return $window > 0 ? $window : self::DEFAULT_WINDOW_SECONDS;
	}
}
