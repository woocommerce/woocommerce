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
 * The window is derived from the standard rate-limit headers returned by
 * WooCommerce.com — `X-RateLimit-Reset` (an absolute Unix timestamp) with a
 * `Retry-After` (delta seconds) fallback — and clamped to per-type minimum and
 * maximum bounds so each endpoint can back off for an appropriate amount of time.
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
	 * Backoff bounds per request type, in seconds.
	 *
	 * `min` is the floor applied after any 429 (also the default when the
	 * response carries no usable rate-limit header). `max` is the ceiling, a
	 * safety net against an unexpectedly distant reset locking the endpoint out.
	 *
	 * @return array<string, array{min:int, max:int}>
	 */
	private static function get_all_bounds(): array {
		return array(
			'update-check'  => array(
				'min' => HOUR_IN_SECONDS,
				'max' => HOUR_IN_SECONDS * 3,
			),
			'subscriptions' => array(
				'min' => 15 * MINUTE_IN_SECONDS,
				'max' => HOUR_IN_SECONDS * 3,
			),
		);
	}

	/**
	 * Backoff bounds for a single request type, in seconds.
	 *
	 * Unknown request types fall back to a conservative default.
	 *
	 * @param string $request_type The Helper API request type (e.g. 'update-check').
	 * @return array{min:int, max:int}
	 */
	private static function get_bounds( string $request_type ): array {
		$default = array(
			'min' => HOUR_IN_SECONDS,
			'max' => WEEK_IN_SECONDS,
		);

		return self::get_all_bounds()[ $request_type ] ?? $default;
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
	 * Reads `X-RateLimit-Reset` (absolute timestamp) then `Retry-After` (delta
	 * seconds), clamps the resulting wait to the request type's [min, max]
	 * bounds, and stores it. With no usable header the minimum bound is applied,
	 * so a malformed 429 still produces a sensible backoff.
	 *
	 * @param string         $request_type The Helper API request type (e.g. 'update-check').
	 * @param array|WP_Error $response     The raw response from the Helper API call.
	 * @return void
	 */
	public static function record_from_response( string $request_type, $response ): void {
		$now         = time();
		$bounds      = self::get_bounds( $request_type );
		$retry_after = 0;

		// Preferred signal: an absolute reset timestamp.
		$reset = wp_remote_retrieve_header( $response, 'x-ratelimit-reset' );
		if ( is_numeric( $reset ) ) {
			$retry_after = (int) $reset - $now;
		}

		// Fallback: Retry-After as a delta in seconds from now.
		if ( $retry_after <= 0 ) {
			$retry_after_header = wp_remote_retrieve_header( $response, 'retry-after' );
			if ( is_numeric( $retry_after_header ) ) {
				$retry_after = (int) $retry_after_header;
			}
		}

		// Clamp to the per-type bounds. A missing/expired header yields the
		// minimum; an unexpectedly distant reset is capped at the maximum.
		$retry_after = max( $bounds['min'], min( $retry_after, $bounds['max'] ) );

		set_transient( self::get_transient_key( $request_type ), $now + $retry_after, $retry_after );
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
