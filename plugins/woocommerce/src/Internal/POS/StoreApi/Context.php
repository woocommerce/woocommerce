<?php
/**
 * Context class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS\StoreApi;

/**
 * Request-scoped detection of POS Store API requests.
 *
 * Detection matches the URI *path* (never the query string, so a crafted
 * storefront URL cannot force POS behaviour onto a shopper's request), plus
 * the `rest_route` GET-parameter fallback used by proxied requests (e.g. the
 * Jetpack tunnel and plain-permalink sites), where the route is not in the
 * URI path. Consulted lazily by the POS policy hooks on every check — never
 * cached at registration time — so filters like `rest_url_prefix` are
 * respected regardless of load order. A test override lets PHPUnit simulate
 * POS context without a full REST request.
 *
 * Deliberately not tied to any feature flag: the `point_of_sale` feature is
 * deprecated (always-enabled) as of 11.0.0 and must not be consulted — its
 * public facade logs a deprecation notice per request. Access control for the
 * POS surface is the routes' capability check, not a flag.
 *
 * @internal Just for internal use.
 *
 * @since 11.0.0
 */
class Context {

	/**
	 * REST URI prefix that identifies a POS Store API request.
	 *
	 * Matches the transaction routes under `wc/internal/pos/` only — not the
	 * public POS catalog feed at `wc/pos/v1/catalog`, which must not run the
	 * POS policy hooks. {@see Controller::REST_NAMESPACE} is derived from this
	 * constant so the two can never drift apart.
	 */
	public const URI_PREFIX = 'wc/internal/pos/';

	/**
	 * Test-only override. When non-null, takes precedence over URI detection.
	 *
	 * @var bool|null
	 */
	private static $test_override = null;

	/**
	 * Per-request memoized detection results, keyed by the request state.
	 *
	 * The consumers include site-wide hot-path filters (stock checks fire per
	 * product per page), and the answer is immutable for a given request
	 * state, so re-parsing the URI on every call would be pure waste.
	 *
	 * @var array<string, bool>
	 */
	private static $memo = array();

	/**
	 * Whether the current request is a POS Store API request.
	 *
	 * @return bool
	 */
	public static function is_pos_request(): bool {
		if ( null !== self::$test_override ) {
			return self::$test_override;
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.Recommended
		$memo_key = ( $_SERVER['REQUEST_URI'] ?? '' ) . '|' . ( is_string( $_GET['rest_route'] ?? null ) ? $_GET['rest_route'] : '' ) . '|' . ( is_admin() ? 'a' : 'f' );

		if ( ! isset( self::$memo[ $memo_key ] ) ) {
			self::$memo = array( $memo_key => self::detect_pos_request() );
		}

		return self::$memo[ $memo_key ];
	}

	/**
	 * Uncached detection.
	 *
	 * @return bool
	 */
	private static function detect_pos_request(): bool {
		// Admin requests (wp-admin pages, admin-ajax.php) never dispatch POS
		// REST routes; any POS-looking URI or rest_route there is spoofing.
		if ( is_admin() ) {
			return false;
		}

		// Direct request: the URI path starts with the site's actual REST base
		// for the POS namespace (rest_url() accounts for subdirectory installs
		// and prefix filters). Anchored on purpose: a substring match would
		// let the prefix appear mid-path (PATH_INFO on a storefront URL) and
		// force POS behaviour onto a shopper's request. On plain-permalink
		// sites rest_url() is query-string based, so this branch never
		// matches there — those requests carry rest_route instead.
		if ( ! empty( $_SERVER['REQUEST_URI'] ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$request_path  = wp_parse_url( (string) $_SERVER['REQUEST_URI'], PHP_URL_PATH );
			$pos_rest_path = wp_parse_url( rest_url( self::URI_PREFIX ), PHP_URL_PATH );

			if (
				is_string( $request_path ) && is_string( $pos_rest_path )
				&& false !== strpos( $pos_rest_path, self::URI_PREFIX )
				&& 0 === strpos( $request_path, $pos_rest_path )
			) {
				return true;
			}
		}

		// Proxied request (e.g. Jetpack tunnel): the route arrives as a `rest_route` GET parameter.
		// Sanitized with sanitize_text_field, not esc_url_raw — the latter treats a
		// schemaless route like `wc/internal/pos/...` as a hostname and mangles it.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Context check, not a state change.
		if ( isset( $_GET['rest_route'] ) && is_string( $_GET['rest_route'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Context check, not a state change.
			$rest_route = rawurldecode( sanitize_text_field( wp_unslash( $_GET['rest_route'] ) ) );
			if ( 0 === strpos( ltrim( $rest_route, '/' ), self::URI_PREFIX ) ) {
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
