<?php
/**
 * Context class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS\StoreApi;

/**
 * Request-scoped detection of POS Store API requests.
 *
 * Detection resolves the route WordPress will actually dispatch, so it can
 * never diverge from dispatch. An explicit `rest_route` request parameter
 * ($_POST then $_GET — the precedence WP::parse_request() applies to public
 * query vars) is the authority when present: this both catches proxied
 * requests (the Jetpack tunnel, plain-permalink sites) whose route is not in
 * the URI path, and rejects a crafted POS-looking path carrying a `rest_route`
 * that points at a public web route. Absent that parameter, the pretty-
 * permalink URI path is matched, anchored to the site's REST base (so the
 * prefix cannot appear mid-path and force POS behaviour onto a shopper) and
 * case-insensitively (WP_REST_Server matches routes case-insensitively).
 * Consulted lazily by the POS policy hooks on every check — never cached at
 * registration time — so filters like `rest_url_prefix` are respected
 * regardless of load order. A test override lets PHPUnit simulate POS context
 * without a full REST request.
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

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing
		$memo_key = ( $_SERVER['REQUEST_URI'] ?? '' )
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.Missing
			. '|' . ( is_string( $_POST['rest_route'] ?? null ) ? $_POST['rest_route'] : '' )
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.Recommended
			. '|' . ( is_string( $_GET['rest_route'] ?? null ) ? $_GET['rest_route'] : '' )
			. '|' . ( is_admin() ? 'a' : 'f' );

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

		// The route WordPress dispatches is the resolved `rest_route` query var,
		// for which an explicit request parameter — $_POST then $_GET — overrides
		// the value the permalink rewrite derives from the URL path (the
		// precedence WP::parse_request() applies to public query vars). Detection
		// must key on that same route, or a crafted POS-looking path carrying
		// e.g. `?rest_route=/wc/store/v1/checkout` would run the guest-accessible
		// web route while the POS policy hooks (which drop the payment-method
		// requirement, force stock, swap the session handler) engage without the
		// POS routes' capability check. So when an explicit parameter is present
		// it is the sole authority — positive for a POS route, negative for a
		// non-POS one. Reproducing the precedence from the superglobals (rather
		// than reading $GLOBALS['wp']->query_vars) keeps the answer stable
		// whether detection runs before or after WP::parse_request(), e.g. during
		// eager session initialization.
		$explicit_route = self::explicit_rest_route_param();
		if ( null !== $explicit_route ) {
			return self::route_is_pos( $explicit_route );
		}

		// No explicit parameter: a pretty-permalink request whose route lives in
		// the URI path, which starts with the site's actual REST base for the POS
		// namespace (rest_url() accounts for subdirectory installs and prefix
		// filters). Anchored on purpose — a substring match would let the prefix
		// appear mid-path (PATH_INFO on a storefront URL) and force POS behaviour
		// onto a shopper — and case-insensitive, because WP_REST_Server matches
		// routes case-insensitively so the dispatched route can differ in case
		// from the constant.
		if ( ! empty( $_SERVER['REQUEST_URI'] ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$request_path  = wp_parse_url( (string) $_SERVER['REQUEST_URI'], PHP_URL_PATH );
			$pos_rest_path = wp_parse_url( rest_url( self::URI_PREFIX ), PHP_URL_PATH );

			if (
				is_string( $request_path ) && is_string( $pos_rest_path )
				&& false !== stripos( $pos_rest_path, self::URI_PREFIX )
				&& 0 === stripos( $request_path, $pos_rest_path )
			) {
				return true;
			}
		}

		return false;
	}

	/**
	 * The explicit `rest_route` request parameter — $_POST then $_GET, the
	 * precedence WP::parse_request() applies to public query vars — or null when
	 * neither carries a non-empty value.
	 *
	 * Sanitized with sanitize_text_field, not esc_url_raw — the latter treats a
	 * schemaless route like `wc/internal/pos/...` as a hostname and mangles it.
	 *
	 * @return string|null
	 */
	private static function explicit_rest_route_param(): ?string {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing -- Context check, not a state change.
		foreach ( array( $_POST, $_GET ) as $source ) {
			if ( ! isset( $source['rest_route'] ) || ! is_string( $source['rest_route'] ) ) {
				continue;
			}
			$route = rawurldecode( sanitize_text_field( wp_unslash( $source['rest_route'] ) ) );
			if ( '' !== $route ) {
				return $route;
			}
		}

		return null;
	}

	/**
	 * Whether a resolved REST route falls under the POS namespace, matched
	 * case-insensitively (WP_REST_Server matches routes case-insensitively).
	 *
	 * @param string $route The resolved route, with or without a leading slash.
	 * @return bool
	 */
	private static function route_is_pos( string $route ): bool {
		return 0 === stripos( ltrim( $route, '/' ), self::URI_PREFIX );
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
