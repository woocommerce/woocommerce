<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\StockNotifications\Frontend;

use WC_Rate_Limiter;

/**
 * Rate limits stock notification sign-up attempts.
 *
 * Sign-ups are throttled per client (the logged-in user, or the IP address for guests) and per
 * e-mail address, so that neither a single client nor a single mailbox can be used to flood the
 * store with sign-ups or verification e-mails. The two windows are independent, so a mailbox
 * is still covered when the same address is used from several clients.
 *
 * @internal
 */
class SignupRateLimiter {

	/**
	 * Rate limit ID prefix for the IP address of the request.
	 */
	private const RATE_LIMIT_IP_PREFIX = 'stock_notifications_signup_ip_';

	/**
	 * Rate limit ID prefix for the logged-in user making the request.
	 */
	private const RATE_LIMIT_USER_PREFIX = 'stock_notifications_signup_user_';

	/**
	 * Rate limit ID prefix for the e-mail address used to sign up.
	 */
	private const RATE_LIMIT_EMAIL_PREFIX = 'stock_notifications_signup_email_';

	/**
	 * Default number of seconds a client has to wait between two sign-up attempts.
	 */
	private const RATE_LIMIT_CLIENT_DELAY = MINUTE_IN_SECONDS / 2;

	/**
	 * Default number of seconds an e-mail address has to wait between two sign-up attempts.
	 */
	private const RATE_LIMIT_EMAIL_DELAY = MINUTE_IN_SECONDS / 2;

	/**
	 * Check whether the current sign-up attempt is rate limited.
	 *
	 * @since 11.2.0
	 *
	 * @param string $user_email The e-mail address used to sign up.
	 * @return bool True if the attempt must be rejected.
	 */
	public function is_rate_limited( string $user_email ): bool {
		foreach ( array_keys( $this->get_rate_limits( $user_email ) ) as $rate_limit_id ) {
			if ( WC_Rate_Limiter::retried_too_soon( $rate_limit_id ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Start the rate limit windows for the current sign-up attempt.
	 *
	 * @since 11.2.0
	 *
	 * @param string $user_email The e-mail address used to sign up.
	 * @return bool True if every rate limit was applied.
	 */
	public function apply( string $user_email ): bool {
		$applied_rate_limit_ids = array();

		foreach ( $this->get_rate_limits( $user_email ) as $rate_limit_id => $delay ) {
			if ( ! WC_Rate_Limiter::set_rate_limit( $rate_limit_id, $delay ) ) {
				// Leave no partial window behind: a half-applied limit would block the
				// customer on an attempt that never went through. The failed limit is
				// cleared too, since set_rate_limit() caches the new expiry even when the
				// write itself fails.
				foreach ( array_merge( array( $rate_limit_id ), $applied_rate_limit_ids ) as $applied_rate_limit_id ) {
					WC_Rate_Limiter::set_rate_limit( $applied_rate_limit_id, -1 );
				}

				return false;
			}

			$applied_rate_limit_ids[] = $rate_limit_id;
		}

		return true;
	}

	/**
	 * Get the rate limits that apply to the current sign-up attempt, keyed by rate limit ID.
	 *
	 * Buckets with a zero delay are left out, so switching one off writes nothing.
	 *
	 * @param string $user_email The e-mail address used to sign up.
	 * @return array<string, int>
	 */
	private function get_rate_limits( string $user_email ): array {
		$delays      = $this->get_delays();
		$rate_limits = array();

		if ( $delays['client'] > 0 ) {
			if ( is_user_logged_in() ) {
				$rate_limits[ self::RATE_LIMIT_USER_PREFIX . get_current_user_id() ] = $delays['client'];
			} else {
				$ip_address = $this->get_ip_address();
				if ( '' !== $ip_address ) {
					$rate_limits[ self::RATE_LIMIT_IP_PREFIX . hash( 'sha256', $ip_address ) ] = $delays['client'];
				}
			}
		}

		$user_email = strtolower( trim( $user_email ) );
		if ( '' !== $user_email && $delays['email'] > 0 ) {
			$rate_limits[ self::RATE_LIMIT_EMAIL_PREFIX . hash( 'sha256', $user_email ) ] = $delays['email'];
		}

		return $rate_limits;
	}

	/**
	 * Get the IP address to rate limit the request on.
	 *
	 * Only applies to guests: a logged-in shopper is keyed on their user ID instead. Only
	 * REMOTE_ADDR is trusted: forwarded headers are attacker-controlled unless the store
	 * is actually behind a proxy that sets them, so a store in that position opts in through
	 * the filter below.
	 *
	 * @return string The IP address, or an empty string if it could not be resolved.
	 */
	private function get_ip_address(): string {
		$ip_address = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated -- format-validated below via rest_is_ip_address().

		/**
		 * Filter: woocommerce_customer_stock_notifications_signup_rate_limit_ip
		 *
		 * IP address the sign-up rate limit is keyed on for a guest sign-up (logged-in
		 * shoppers are keyed on their user ID instead). Stores behind a proxy, load balancer
		 * or CDN can return the client address taken from the relevant transport header.
		 * Values that are not valid IP addresses switch the per-IP limit off.
		 *
		 * @since 11.2.0
		 *
		 * @param string $ip_address The remote address of the request.
		 */
		$ip_address = apply_filters( 'woocommerce_customer_stock_notifications_signup_rate_limit_ip', $ip_address );

		if ( ! is_string( $ip_address ) || ! rest_is_ip_address( $ip_address ) ) {
			return '';
		}

		return $ip_address;
	}

	/**
	 * Get the number of seconds to wait between two sign-up attempts, per bucket.
	 *
	 * @return array{client: int, email: int}
	 */
	private function get_delays(): array {
		$defaults = array(
			'client' => (int) self::RATE_LIMIT_CLIENT_DELAY,
			'email'  => (int) self::RATE_LIMIT_EMAIL_DELAY,
		);

		/**
		 * Filter: woocommerce_customer_stock_notifications_signup_rate_limit_delays
		 *
		 * Number of seconds to wait between two sign-up attempts, for the client making the
		 * request (the logged-in user, or the IP address for guests) and for the e-mail
		 * address used to sign up. Use 0 to switch a limit off.
		 *
		 * @since 11.2.0
		 *
		 * @param array $delays Delays in seconds, keyed by 'client' and 'email'.
		 */
		$delays = apply_filters( 'woocommerce_customer_stock_notifications_signup_rate_limit_delays', $defaults );

		if ( ! is_array( $delays ) ) {
			$delays = $defaults;
		}

		// A callback can return anything: non-numeric values fall back to the default,
		// and a negative delay would clear the limit instead of setting one, so it is
		// clamped to zero.
		return array(
			'client' => is_numeric( $delays['client'] ?? null ) ? max( 0, (int) $delays['client'] ) : $defaults['client'],
			'email'  => is_numeric( $delays['email'] ?? null ) ? max( 0, (int) $delays['email'] ) : $defaults['email'],
		);
	}
}
