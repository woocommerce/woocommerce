<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\StockNotifications\Frontend;

use WC_Geolocation;
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
	 * Rate limiting enabled default value.
	 */
	private const ENABLED = true;

	/**
	 * Proxy support enabled default value.
	 */
	private const PROXY_SUPPORT = false;

	/**
	 * Default number of seconds a client has to wait between two sign-up attempts.
	 */
	private const CLIENT_DELAY = MINUTE_IN_SECONDS / 2;

	/**
	 * Default number of seconds an e-mail address has to wait between two sign-up attempts.
	 */
	private const EMAIL_DELAY = MINUTE_IN_SECONDS / 2;

	/**
	 * Check whether the current sign-up attempt is rate limited.
	 *
	 * Fires `woocommerce_customer_stock_notifications_signup_rate_limit_exceeded` when it is.
	 *
	 * @since 11.2.0
	 *
	 * @param string $user_email The e-mail address used to sign up.
	 * @return bool True if the attempt must be rejected.
	 */
	public function is_rate_limited( string $user_email ): bool {
		foreach ( array_keys( $this->get_rate_limits( $user_email ) ) as $rate_limit_id ) {
			if ( ! WC_Rate_Limiter::retried_too_soon( $rate_limit_id ) ) {
				continue;
			}

			/**
			 * Action: woocommerce_customer_stock_notifications_signup_rate_limit_exceeded
			 *
			 * Fires when a stock notification sign-up attempt is refused because the client
			 * or the e-mail address retried too soon. Useful for tracking abuse.
			 *
			 * @since 11.2.0
			 *
			 * @param string $rate_limit_id The rate limit ID that was hit. Starts with
			 *                              'stock_notifications_signup_user_', '..._ip_' or
			 *                              '..._email_', followed by the user ID or a hash.
			 * @param string $user_email    The e-mail address used to sign up.
			 */
			do_action( 'woocommerce_customer_stock_notifications_signup_rate_limit_exceeded', $rate_limit_id, $user_email );

			return true;
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
		$options     = $this->get_options();
		$rate_limits = array();

		if ( ! $options['enabled'] ) {
			return $rate_limits;
		}

		if ( $options['client_delay'] > 0 ) {
			if ( is_user_logged_in() ) {
				$rate_limits[ self::RATE_LIMIT_USER_PREFIX . get_current_user_id() ] = $options['client_delay'];
			} else {
				$ip_address = $this->get_ip_address( $options['proxy_support'] );
				if ( '' !== $ip_address ) {
					$rate_limits[ self::RATE_LIMIT_IP_PREFIX . hash( 'sha256', $ip_address ) ] = $options['client_delay'];
				}
			}
		}

		$user_email = strtolower( trim( $user_email ) );
		if ( '' !== $user_email && $options['email_delay'] > 0 ) {
			$rate_limits[ self::RATE_LIMIT_EMAIL_PREFIX . hash( 'sha256', $user_email ) ] = $options['email_delay'];
		}

		return $rate_limits;
	}

	/**
	 * Get the IP address to rate limit a guest request on.
	 *
	 * Only REMOTE_ADDR is trusted by default: forwarded headers are attacker-controlled
	 * unless the store is actually behind a proxy that sets them. With proxy support on,
	 * the address comes from the forwarding headers the rest of WooCommerce trusts.
	 *
	 * @param bool $proxy_support Whether to read the client address from forwarding headers.
	 * @return string The IP address, or an empty string if it could not be resolved.
	 */
	private function get_ip_address( bool $proxy_support ): string {
		if ( $proxy_support ) {
			return WC_Geolocation::get_ip_address();
		}

		$ip_address = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated -- format-validated below via rest_is_ip_address().

		return (string) rest_is_ip_address( $ip_address );
	}

	/**
	 * Get the rate limiting options, with the filter applied and every value coerced to its type.
	 *
	 * @return array{enabled: bool, proxy_support: bool, client_delay: int, email_delay: int}
	 */
	private function get_options(): array {
		$defaults = array(
			'enabled'       => self::ENABLED,
			'proxy_support' => self::PROXY_SUPPORT,
			'client_delay'  => (int) self::CLIENT_DELAY,
			'email_delay'   => (int) self::EMAIL_DELAY,
		);

		/**
		 * Filter: woocommerce_customer_stock_notifications_signup_rate_limit_options
		 *
		 * Options for rate limiting stock notification sign-ups. Mirrors the shape of
		 * `woocommerce_store_api_rate_limit_options`.
		 *
		 * - `enabled`: switches the limiter off entirely. Default true.
		 * - `proxy_support`: read the client address from forwarding headers (X-Real-IP,
		 *   X-Forwarded-For) rather than REMOTE_ADDR. Enable only when the store is behind a
		 *   proxy, load balancer or CDN that sets them, since the headers are otherwise
		 *   spoofable. Default false.
		 * - `client_delay`: seconds a client (the logged-in user, or the IP address for
		 *   guests) has to wait between two sign-up attempts. 0 switches this limit off.
		 *   Default 30.
		 * - `email_delay`: seconds an e-mail address has to wait between two sign-up attempts.
		 *   0 switches this limit off. Default 30.
		 *
		 * @since 11.2.0
		 *
		 * @param array $options Rate limiting options.
		 */
		$options = apply_filters( 'woocommerce_customer_stock_notifications_signup_rate_limit_options', $defaults );

		if ( ! is_array( $options ) ) {
			$options = $defaults;
		}

		// A callback can return anything: unknown or non-numeric values fall back to the
		// default, and a negative delay would clear the limit instead of setting one, so it
		// is clamped to zero.
		return array(
			'enabled'       => $this->to_bool( $options['enabled'] ?? $defaults['enabled'] ),
			'proxy_support' => $this->to_bool( $options['proxy_support'] ?? $defaults['proxy_support'] ),
			'client_delay'  => is_numeric( $options['client_delay'] ?? null ) ? max( 0, (int) $options['client_delay'] ) : $defaults['client_delay'],
			'email_delay'   => is_numeric( $options['email_delay'] ?? null ) ? max( 0, (int) $options['email_delay'] ) : $defaults['email_delay'],
		);
	}

	/**
	 * Coerce a filtered option to a boolean, accepting the usual 'yes'/'true'/1 spellings.
	 *
	 * @param mixed $value The filtered value.
	 */
	private function to_bool( $value ): bool {
		return is_bool( $value ) ? $value : filter_var( $value, FILTER_VALIDATE_BOOLEAN );
	}
}
