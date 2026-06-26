<?php
/**
 * Context class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS\StoreApi;

use Automattic\WooCommerce\Utilities\FeaturesUtil;

/**
 * Request-scoped detection of POS Store API requests.
 *
 * Unlike the inheritance spike — which gave POS its own `wc/internal/pos/v1`
 * namespace and could therefore recognise a POS request purely from the URI —
 * the shared-routes approach runs POS over the very same `wc/store/v1`
 * cart/checkout routes the web storefront uses. There is no namespace to key
 * off, so {@see self::is_pos_request()} recognises POS *intent* from three
 * request-level signals:
 *
 *   1. the `point_of_sale` feature is enabled (no POS, nothing to do);
 *   2. the POS client explicitly marked the request as POS — an
 *      `X-WooCommerce-POS` header, or a `pos` query parameter for transports
 *      that can't set custom headers (the Jetpack tunnel). This marker is what
 *      keeps a logged-in store manager's *own web checkout* — same route, same
 *      capability — from being mistaken for a POS sale: the web storefront never
 *      sends it; and
 *   3. the request targets a Store API cart / checkout / batch route (so a stray
 *      `?pos=1` on a frontend page a manager is browsing can't engage the cart
 *      policy hooks).
 *
 * Intent is not authorisation. The `manage_woocommerce` capability that
 * authorises POS behaviour is checked separately, by {@see PolicyHooks\CapabilityGate},
 * which rejects a marked-but-unauthorised request (e.g. a guest forging the
 * marker) with a 401/403 at the very start of dispatch — before any other POS
 * policy runs. Keeping the capability out of `is_pos_request()` is deliberate:
 * the three intent signals all live on the request and so survive the
 * mid-request guest swap that {@see PolicyHooks\CurrentUserSwap} performs,
 * whereas a live `current_user_can()` check would flip to false the moment the
 * user is dropped to a guest and silently disable every later policy hook. Once
 * the gate has rejected unauthorised intent up front, every hook that fires
 * afterwards can trust the marker alone, so no verdict has to be latched.
 *
 * Detection is still evaluated lazily, per filter callback, rather than at
 * registration time (the request globals aren't populated when the policy hooks
 * are wired up in the WooCommerce constructor). The `rest_route` GET-parameter
 * fallback mirrors core's proxied-request handling: requests through the Jetpack
 * tunnel carry the route there rather than in the URI path.
 *
 * @internal Just for internal use.
 *
 * @since 10.9.0
 */
class Context {

	/**
	 * Test-only override. When non-null, takes precedence over live detection.
	 *
	 * @var bool|null
	 */
	private static $test_override = null;

	/**
	 * REST route fragments that identify a POS cart/checkout request. POS reuses
	 * the shared Store API cart and checkout pipeline, so these are the same
	 * routes the web storefront hits — the capability check is what tells the
	 * two apart.
	 */
	private const ROUTE_FRAGMENTS = array(
		'wc/store/v1/cart',
		'wc/store/v1/checkout',
		'wc/store/v1/batch',
		'wc/store/cart',
		'wc/store/checkout',
		'wc/store/batch',
	);

	/**
	 * Capability that marks a caller as a POS operator rather than a shopper.
	 */
	private const REQUIRED_CAPABILITY = 'manage_woocommerce';

	/**
	 * Request header POS clients set to opt a request into POS handling. PHP
	 * exposes it as `$_SERVER['HTTP_X_WOOCOMMERCE_POS']`.
	 */
	private const MARKER_HEADER = 'HTTP_X_WOOCOMMERCE_POS';

	/**
	 * Query-parameter equivalent of {@see self::MARKER_HEADER}, for transports
	 * that can't set a custom header (e.g. the Jetpack tunnel).
	 */
	private const MARKER_PARAM = 'pos';

	/**
	 * Whether the current request carries POS intent: the feature is enabled, the
	 * request is marked as POS, and it targets a Store API cart/checkout route.
	 *
	 * Authorisation (the operator capability) is enforced separately by
	 * {@see PolicyHooks\CapabilityGate}; see the class docblock for why it is not
	 * part of this check.
	 *
	 * @return bool
	 */
	public static function is_pos_request(): bool {
		if ( null !== self::$test_override ) {
			return self::$test_override;
		}

		return self::detect();
	}

	/**
	 * Whether the current user holds the capability required to operate POS.
	 *
	 * The capability authorises POS behaviour but — unlike the marker — does not
	 * survive the mid-request guest swap, so it is intentionally not part of
	 * {@see self::is_pos_request()}. {@see PolicyHooks\CapabilityGate} evaluates
	 * it once, up front, against the authenticated operator; any other caller
	 * must likewise check it before that swap runs.
	 *
	 * @return bool
	 */
	public static function current_user_can_operate_pos(): bool {
		return current_user_can( self::REQUIRED_CAPABILITY );
	}

	/**
	 * Evaluate the three POS intent signals against the live request.
	 *
	 * @return bool
	 */
	private static function detect(): bool {
		if ( ! FeaturesUtil::feature_is_enabled( 'point_of_sale' ) ) {
			return false;
		}

		if ( ! self::has_pos_marker() ) {
			return false;
		}

		return self::targets_cart_or_checkout_route();
	}

	/**
	 * Whether the client explicitly opted this request into POS handling, via the
	 * `X-WooCommerce-POS` header or a truthy `pos` query parameter.
	 *
	 * @return bool
	 */
	private static function has_pos_marker(): bool {
		if ( ! empty( $_SERVER[ self::MARKER_HEADER ] ) ) {
			return true;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Context check, not a state change.
		if ( isset( $_GET[ self::MARKER_PARAM ] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Context check, not a state change.
			$value = strtolower( sanitize_text_field( wp_unslash( $_GET[ self::MARKER_PARAM ] ) ) );
			return ! in_array( $value, array( '', '0', 'false', 'no' ), true );
		}

		return false;
	}

	/**
	 * Whether the request is addressed to a Store API cart/checkout/batch route,
	 * via either the URI path (direct request) or the `rest_route` GET parameter
	 * (proxied request, e.g. the Jetpack tunnel).
	 *
	 * @return bool
	 */
	private static function targets_cart_or_checkout_route(): bool {
		// Direct request: the route is in the URI path (/wp-json/wc/store/...).
		if ( ! empty( $_SERVER['REQUEST_URI'] ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$request_uri = (string) $_SERVER['REQUEST_URI'];
			foreach ( self::ROUTE_FRAGMENTS as $fragment ) {
				if ( false !== strpos( $request_uri, trailingslashit( rest_get_url_prefix() ) . $fragment ) ) {
					return true;
				}
			}
		}

		// Proxied request: the route arrives as a `rest_route` GET parameter.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Context check, not a state change.
		if ( isset( $_GET['rest_route'] ) && is_string( $_GET['rest_route'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Context check, not a state change.
			$rest_route = rawurldecode( esc_url_raw( wp_unslash( $_GET['rest_route'] ) ) );
			foreach ( self::ROUTE_FRAGMENTS as $fragment ) {
				if ( 0 === strpos( $rest_route, '/' . $fragment ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Override POS request detection for testing.
	 *
	 * Pass true/false to force a value, or null to clear and revert to live
	 * detection.
	 *
	 * @param bool|null $value Override value.
	 */
	public static function set_test_override( ?bool $value ): void {
		self::$test_override = $value;
	}
}
