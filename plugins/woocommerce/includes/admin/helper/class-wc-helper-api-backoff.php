<?php
/**
 * WooCommerce.com Helper API rate-limit backoff.
 *
 * @package WooCommerce\Admin\Helper
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Helper_API_Backoff Class
 *
 * Records and enforces a per-request-type backoff window when a WooCommerce.com
 * Helper API endpoint responds with a rate-limit status (HTTP 429), so the site
 * refrains from calling that endpoint again until the limit resets.
 *
 * The window is taken from the response's `Retry-After` header (delta seconds)
 * and honored as-is, capped only at a per-type maximum. This covers both the
 * short window of a global rate limit and the longer window of a per-endpoint
 * limit. When the header is absent, a per-type default window is applied
 * instead. `Retry-After` is used in preference to `X-RateLimit-Reset` because
 * it is a relative delta and therefore immune to clock skew between the site
 * and WooCommerce.com.
 *
 * A manual "Refresh" request (the Marketplace refresh button) always bypasses
 * and clears the backoff so the user can force a fresh request at any time.
 */
class WC_Helper_API_Backoff {

	/**
	 * Prefix for the transient that stores a request type's backoff expiry.
	 *
	 * @var string
	 */
	const TRANSIENT_PREFIX = '_woocommerce_helper_backoff_';

	/**
	 * Request type: the WooCommerce.com update-check endpoint.
	 *
	 * @var string
	 */
	const REQUEST_TYPE_UPDATE_CHECK = 'update-check';

	/**
	 * Request type: the WooCommerce.com subscriptions endpoint.
	 *
	 * @var string
	 */
	const REQUEST_TYPE_SUBSCRIPTIONS = 'subscriptions';

	/**
	 * Backoff bounds per request type, in seconds.
	 *
	 * `default` is the window applied only when the response carries no usable
	 * rate-limit header. `max` is the ceiling that an explicit header value is
	 * capped at — a safety net against an unexpectedly distant reset locking the
	 * endpoint out. An explicit header shorter than `default` (e.g. a global
	 * rate limit's brief `Retry-After`) is honored as-is, not floored.
	 *
	 * @return array<string, array{default:int, max:int}>
	 */
	private static function get_all_bounds(): array {
		return array(
			self::REQUEST_TYPE_UPDATE_CHECK  => array(
				'default' => HOUR_IN_SECONDS,
				'max'     => HOUR_IN_SECONDS * 3,
			),
			self::REQUEST_TYPE_SUBSCRIPTIONS => array(
				'default' => 15 * MINUTE_IN_SECONDS,
				'max'     => HOUR_IN_SECONDS * 3,
			),
		);
	}

	/**
	 * Backoff bounds for a single request type, in seconds.
	 *
	 * Unknown request types fall back to a conservative default.
	 *
	 * @param string $request_type The Helper API request type (e.g. 'update-check').
	 * @return array{default:int, max:int}
	 */
	private static function get_bounds( string $request_type ): array {
		$fallback = array(
			'default' => HOUR_IN_SECONDS,
			'max'     => WEEK_IN_SECONDS,
		);

		return self::get_all_bounds()[ $request_type ] ?? $fallback;
	}

	/**
	 * Transient key for a request type's backoff window.
	 *
	 * @param string $request_type The Helper API request type (e.g. 'update-check').
	 * @return string
	 */
	private static function get_transient_key( string $request_type ): string {
		return self::TRANSIENT_PREFIX . $request_type;
	}

	/**
	 * Whether the current request is a manual Marketplace refresh.
	 *
	 * @return bool
	 */
	public static function is_refresh_request(): bool {
		$request_uri = wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		return false !== stripos( (string) $request_uri, 'wc/v3/marketplace/refresh' );
	}

	/**
	 * Whether a request type is currently within a backoff window.
	 *
	 * A manual refresh always returns false and clears any recorded backoff, so
	 * clicking "Refresh" lets the user force a fresh request at any time.
	 *
	 * @param string $request_type The Helper API request type (e.g. 'update-check').
	 * @return bool True while the site should refrain from calling the endpoint.
	 */
	public static function is_rate_limited( string $request_type ): bool {
		if ( self::is_refresh_request() ) {
			self::clear( $request_type );
			return false;
		}

		$retry_after = get_transient( self::get_transient_key( $request_type ) );

		return is_numeric( $retry_after ) && (int) $retry_after > time();
	}

	/**
	 * Record a backoff window for a request type from a rate-limited response.
	 *
	 * The wait is taken from the response's rate-limit headers and honored as-is,
	 * capped only at the request type's `max`. This respects both a global rate
	 * limit's short `Retry-After` and a per-endpoint limit's longer window. When
	 * no usable header is present (a malformed 429), the per-type `default` is
	 * used so there is always a sensible backoff.
	 *
	 * @param string $request_type The Helper API request type (e.g. 'update-check').
	 * @param array  $response     The rate-limited (HTTP 429) response from the Helper API call.
	 * @return void
	 */
	public static function record_from_response( string $request_type, array $response ): void {
		$now    = time();
		$bounds = self::get_bounds( $request_type );

		$retry_after = self::get_retry_after_from_headers( $response );

		if ( null === $retry_after ) {
			// No Retry-After header — apply the per-type default window.
			$retry_after = $bounds['default'];
		} else {
			// Honor the server's directive, but never longer than the per-type
			// maximum (a safety net against an erroneous far value).
			$retry_after = min( $retry_after, $bounds['max'] );
		}

		set_transient( self::get_transient_key( $request_type ), $now + $retry_after, $retry_after );
	}

	/**
	 * Extract the wait, in seconds, from a rate-limited response's `Retry-After`
	 * header. Non-positive or missing values are treated as absent.
	 *
	 * @param array $response The rate-limited (HTTP 429) response from the Helper API call.
	 * @return int|null Seconds to wait, or null when the header is absent/invalid.
	 */
	private static function get_retry_after_from_headers( array $response ): ?int {
		$retry_after = wp_remote_retrieve_header( $response, 'retry-after' );
		if ( is_numeric( $retry_after ) && (int) $retry_after > 0 ) {
			return (int) $retry_after;
		}

		return null;
	}

	/**
	 * Clear any recorded backoff for a request type.
	 *
	 * @param string $request_type The Helper API request type (e.g. 'update-check').
	 * @return void
	 */
	public static function clear( string $request_type ): void {
		delete_transient( self::get_transient_key( $request_type ) );
	}

	/**
	 * Clear the recorded backoff for every known request type.
	 *
	 * Called when the user clicks the Marketplace "Refresh" button so a manual
	 * refresh always resets rate-limit backoffs and forces fresh Helper API
	 * calls.
	 *
	 * @return void
	 */
	public static function clear_all(): void {
		foreach ( array_keys( self::get_all_bounds() ) as $request_type ) {
			self::clear( $request_type );
		}
	}
}
