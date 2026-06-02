<?php
/**
 * Context class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS\StoreApi;

/**
 * Request-scoped detection of POS Store API requests.
 *
 * Mirrors the URI-prefix detection WooCommerce uses for the Store API itself
 * (see WooCommerce::is_store_api_request), plus the `rest_route` GET-parameter
 * fallback core uses for proxied requests (see
 * Authentication::has_store_api_route_as_get_parameter). The fallback matters
 * because requests arriving through the Jetpack tunnel (WPCOM
 * `jetpack-blogs/{id}/rest-api` proxy) carry the route in `rest_route` rather
 * than the URI path, so URI-only detection would miss them and silently skip
 * every POS policy hook. Consulted by the POS session handler and policy hooks;
 * detection is cheap and idempotent. A test override lets PHPUnit simulate POS
 * context without a full REST request.
 *
 * @internal Just for internal use.
 *
 * @since 10.9.0
 */
class Context {

	/**
	 * Test-only override. When non-null, takes precedence over URI detection.
	 *
	 * @var bool|null
	 */
	private static $test_override = null;

	/**
	 * REST URI prefix that identifies a POS Store API request.
	 */
	private const URI_PREFIX = 'wc/pos/';

	/**
	 * Whether the current request is a POS Store API request.
	 *
	 * @return bool
	 */
	public static function is_pos_request(): bool {
		if ( null !== self::$test_override ) {
			return self::$test_override;
		}

		// Direct request: the route is in the URI path (/wp-json/wc/pos/...).
		if ( ! empty( $_SERVER['REQUEST_URI'] ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$request_uri = $_SERVER['REQUEST_URI'];
			if ( false !== strpos( $request_uri, trailingslashit( rest_get_url_prefix() ) . self::URI_PREFIX ) ) {
				return true;
			}
		}

		// Proxied request (e.g. Jetpack tunnel): the route arrives as a `rest_route` GET parameter.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Context check, not a state change.
		if ( isset( $_GET['rest_route'] ) && is_string( $_GET['rest_route'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Context check, not a state change.
			$rest_route = rawurldecode( esc_url_raw( wp_unslash( $_GET['rest_route'] ) ) );
			if ( 0 === strpos( $rest_route, '/' . self::URI_PREFIX ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Override POS request detection for testing.
	 *
	 * Pass true/false to force a value, or null to clear and revert to URI detection.
	 *
	 * @param bool|null $value Override value.
	 */
	public static function set_test_override( ?bool $value ): void {
		self::$test_override = $value;
	}
}
