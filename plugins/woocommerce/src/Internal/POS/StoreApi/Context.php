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
 * off, so a request is recognised as POS only when all four hold:
 *
 *   1. the `point_of_sale` feature is enabled (no POS, nothing to do);
 *   2. the POS client explicitly marked the request as POS — an
 *      `X-WooCommerce-POS` header, or a `pos` query parameter for transports
 *      that can't set custom headers (the Jetpack tunnel). This marker is what
 *      keeps a logged-in store manager's *own web checkout* — same route, same
 *      capability — from being mistaken for a POS sale: the web storefront never
 *      sends it;
 *   3. the request targets a Store API cart / checkout / batch route (so a stray
 *      `?pos=1` on a frontend page a manager is browsing can't engage the cart
 *      policy hooks); and
 *   4. the caller can `manage_woocommerce`. The marker only declares intent;
 *      this capability is what authorises the POS behaviour, so a guest who
 *      forges the marker gets a normal (rejected) web checkout, never a
 *      no-payment oversold POS order.
 *
 * Two consequences of (4) shape this class:
 *
 * - Capability is only knowable once REST authentication has resolved, which is
 *   long after the policy hooks are *registered* (in the WooCommerce
 *   constructor). So detection is evaluated lazily, per filter callback, not at
 *   registration time. Each policy hook installs its filter unconditionally and
 *   asks here whether to act.
 * - {@see PolicyHooks\CurrentUserSwap} drops the current user to a guest partway
 *   through the request. A live `current_user_can()` check would flip to false
 *   the moment that happens, silently disabling every later policy hook. To
 *   avoid that, the first positive verdict is latched for the rest of the
 *   request (PHP processes serve one request, so this is request-scoped).
 *
 * The `rest_route` GET-parameter fallback mirrors core's proxied-request
 * handling: requests through the Jetpack tunnel carry the route there rather
 * than in the URI path.
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
	 * Latched positive verdict for the current request. Once detection returns
	 * true it stays true, so the guest swap performed mid-request by
	 * {@see PolicyHooks\CurrentUserSwap} can't retroactively turn POS handling
	 * off. Null until the first positive detection.
	 *
	 * @var true|null
	 */
	private static $latched = null;

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
	 * Whether the current request is a POS Store API request.
	 *
	 * @return bool
	 */
	public static function is_pos_request(): bool {
		if ( null !== self::$test_override ) {
			return self::$test_override;
		}

		if ( true === self::$latched ) {
			return true;
		}

		$is_pos = self::detect();

		if ( $is_pos ) {
			self::$latched = true;
		}

		return $is_pos;
	}

	/**
	 * Evaluate the three POS conditions against the live request and current user.
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

		if ( ! self::targets_cart_or_checkout_route() ) {
			return false;
		}

		return current_user_can( self::REQUIRED_CAPABILITY );
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
	 * detection. Clearing also drops the latched verdict so tests don't leak
	 * POS context into one another within the same process.
	 *
	 * @param bool|null $value Override value.
	 */
	public static function set_test_override( ?bool $value ): void {
		self::$test_override = $value;

		if ( null === $value ) {
			self::$latched = null;
		}
	}
}
