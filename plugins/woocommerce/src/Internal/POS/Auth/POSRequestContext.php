<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS\Auth;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Internal\Features\FeaturesController;

/**
 * Request-scoped detector for "POS-originated" REST requests.
 *
 * This is the boundary that gates the POS staff current-user swap (see POSAuthHandler),
 * so it must be sound: a false positive would let a non-POS request change the effective
 * user; a false negative would break POS. It is evaluated once per request and memoized.
 *
 * It runs at `determine_current_user` time, before REST dispatch — so there is NO
 * WP_REST_Request, no route params, and no parsed body available. Detection therefore reads
 * only `$_SERVER` (method + headers) and the parsed REST route from
 * `$GLOBALS['wp']->query_vars['rest_route']` (mirroring StoreApi\Authentication). Request body
 * data (e.g. `created_via`, `_pos_staff_user_id`) is deliberately NOT used — it is unavailable
 * this early and is client-controlled.
 *
 * Origin signal (one required):
 *  - Tier 1 (structural, primary, non-spoofable): the request targets a `/wc/pos/v1/*` route.
 *    A client cannot make a `/wc/v3/*` request look like a `/wc/pos/v1/*` one; WP routes it from
 *    the real URL.
 *  - Tier 2 (header bridge, interim): an allowlisted `/wc/v3/{orders,refunds,coupons}` write
 *    plus the `X-WC-POS-Request: 1` header — a crutch for routes not yet migrated to the POS
 *    namespace. The header is spoofable, but it only scopes WHEN the swap runs; it is not the gate
 *    (POSAuthHandler still requires the pre-swap user to be the device admin).
 *
 * Identity credential (both tiers): the `X-WC-POS-Staff-Id` header names the staff member to act
 * as. For this POC the swap TRUSTS that id (gated only by the device-admin auth) — there is NO
 * per-request staff credential check yet, so the swap is not an enforcement boundary on its own.
 * Authenticating the staff (verify a PIN once at login/override, then carry a short-lived token on
 * writes) is the deferred follow-up; see POSAuthHandler.
 *
 * Header-naming note: `X-WC-POS-*` are POC-local conventions, not a WooCommerce standard (the lone
 * core precedent is the boolean hint `X-WC-From-Product-Editor`).
 *
 * @since 11.0.0
 * @internal
 */
class POSRequestContext {

	private const PARENT_FLAG  = 'point_of_sale';
	private const FEATURE_FLAG = 'point_of_sale_staff';

	private const HEADER_POS_REQUEST  = 'HTTP_X_WC_POS_REQUEST';
	private const HEADER_STAFF_ID     = 'HTTP_X_WC_POS_STAFF_ID';
	private const HEADER_INITIATOR_ID = 'HTTP_X_WC_POS_INITIATOR_ID';

	public const TIER_STRUCTURAL = 'structural';
	public const TIER_HEADER     = 'header';
	public const TIER_NONE       = '';

	public const INTENT_ORDER_CREATE  = 'order.create';
	public const INTENT_ORDER_UPDATE  = 'order.update';
	public const INTENT_REFUND_CREATE = 'refund.create';
	public const INTENT_COUPON_CREATE = 'coupon.create';

	/**
	 * Features controller used to gate detection on the POS feature flags.
	 *
	 * @var FeaturesController
	 */
	private FeaturesController $features_controller;

	/**
	 * Memoized result of the request-shape check. Null until first resolved.
	 *
	 * @var bool|null
	 */
	private ?bool $is_pos_request = null;

	/**
	 * Origin tier resolved alongside is_pos_request (one of the TIER_* constants).
	 *
	 * @var string
	 */
	private string $tier = self::TIER_NONE;

	/**
	 * Operation intent resolved from the route + method, or null if not a known write.
	 *
	 * @var string|null
	 */
	private ?string $intent = null;

	/**
	 * Staff user id asserted by the request header (device-asserted; no per-request credential check
	 * in this POC).
	 *
	 * @var int
	 */
	private int $staff_id = 0;

	/**
	 * Initialize dependencies via the DI container.
	 *
	 * @internal
	 *
	 * @param FeaturesController $features_controller The features controller.
	 */
	final public function init( FeaturesController $features_controller ): void {
		$this->features_controller = $features_controller;
	}

	/**
	 * Whether the current request is shaped like a POS-originated request naming a staff member.
	 * Memoized for the request.
	 *
	 * This is necessary-but-not-sufficient for a swap: POSAuthHandler additionally requires the
	 * pre-swap user to be the device admin and the named staff to hold the operation's cap.
	 *
	 * @return bool
	 */
	public function is_pos_request(): bool {
		$this->resolve();
		return (bool) $this->is_pos_request;
	}

	/**
	 * The origin tier that matched, or TIER_NONE.
	 *
	 * @return string One of the TIER_* constants.
	 */
	public function tier(): string {
		$this->resolve();
		return $this->tier;
	}

	/**
	 * The staff user id asserted by the request header (0 when absent/invalid).
	 *
	 * Device-asserted: this POC trusts the id without a per-request staff credential check. A token
	 * check is the deferred follow-up.
	 *
	 * @return int
	 */
	public function get_staff_id(): int {
		$this->resolve();
		return $this->staff_id;
	}

	/**
	 * The operation intent (one of the INTENT_* constants) or null.
	 *
	 * Null on POS-origin reads (e.g. the structural whoami route) and on non-write requests.
	 * Used by POSCapBridge to scope the temporary capability grant.
	 *
	 * @return string|null
	 */
	public function get_intent(): ?string {
		$this->resolve();
		return $this->intent;
	}

	/**
	 * The initiator staff user id from the `X-WC-POS-Initiator-Id` header (0 when absent).
	 *
	 * The initiator is the staff member who initiated an action a different staff member authorized
	 * — e.g. the cashier on a manager-approved override refund. It is attribution context, not an
	 * auth credential, so it is read on demand and never gates detection or the swap.
	 *
	 * @return int
	 */
	public function get_initiator_id(): int {
		return isset( $_SERVER[ self::HEADER_INITIATOR_ID ] )
			? absint( wp_unslash( $_SERVER[ self::HEADER_INITIATOR_ID ] ) )
			: 0;
	}

	/**
	 * Resolve and memoize the request-shape detection.
	 *
	 * `determine_current_user` fires on the first wp_get_current_user() of the request, which can
	 * happen during bootstrap — before REST_REQUEST is defined and before the route is parsed. A
	 * negative computed then must NOT be memoized, or the real REST dispatch (where the swap matters)
	 * would read a stale false. So the result is only locked in once the REST context exists; until
	 * then is_pos_request() reports a transient false and re-evaluates on the next call.
	 */
	private function resolve(): void {
		if ( null !== $this->is_pos_request ) {
			return;
		}

		if ( ! $this->rest_context_ready() ) {
			$this->tier     = self::TIER_NONE;
			$this->intent   = null;
			$this->staff_id = 0;
			return;
		}

		$this->is_pos_request = $this->compute();
	}

	/**
	 * Whether the REST request context is established enough to make a final detection decision.
	 *
	 * @return bool
	 */
	private function rest_context_ready(): bool {
		return defined( 'REST_REQUEST' ) && REST_REQUEST && '' !== $this->get_rest_route();
	}

	/**
	 * Compute the request-shape detection from $_SERVER + the parsed REST route only.
	 *
	 * @return bool
	 */
	private function compute(): bool {
		// Reset derived fields so a negative result never leaks a stale tier/intent/staff id.
		$this->tier     = self::TIER_NONE;
		$this->intent   = null;
		$this->staff_id = 0;

		if ( ! defined( 'REST_REQUEST' ) || ! REST_REQUEST ) {
			return false;
		}

		// Safe to read flags here: determine_current_user runs during REST dispatch, long after
		// `init`, so feature_is_enabled()'s translated strings do not load too early.
		if ( ! $this->features_controller->feature_is_enabled( self::PARENT_FLAG ) ) {
			return false;
		}
		if ( ! $this->features_controller->feature_is_enabled( self::FEATURE_FLAG ) ) {
			return false;
		}

		$route = $this->get_rest_route();
		if ( '' === $route ) {
			return false;
		}

		// Store API has its own (guest) identity model and shares the global determine_current_user
		// hook — never treat its traffic as POS-originated. Redundant with the origin allowlist
		// below, kept as an explicit invariant.
		if ( 0 === strpos( $route, '/wc/store/' ) ) {
			return false;
		}

		// The staff-id header must name a staff member to act as. This POC trusts the id (gated by
		// the device-admin auth); a per-request staff credential check is the deferred follow-up.
		$staff_id = isset( $_SERVER[ self::HEADER_STAFF_ID ] )
			? absint( wp_unslash( $_SERVER[ self::HEADER_STAFF_ID ] ) )
			: 0;
		if ( $staff_id <= 0 ) {
			return false;
		}

		$method = isset( $_SERVER['REQUEST_METHOD'] )
			? strtoupper( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) )
			: '';

		$origin = $this->resolve_origin( $route, $method );
		if ( null === $origin ) {
			return false;
		}

		$this->tier     = $origin['tier'];
		$this->intent   = $origin['intent'];
		$this->staff_id = $staff_id;

		return true;
	}

	/**
	 * Resolve the origin tier + intent for a route/method, or null when not POS-originated.
	 *
	 * @param string $route  Normalized REST route (leading slash, no prefix/query).
	 * @param string $method Uppercased HTTP method.
	 * @return array{tier:string,intent:string|null}|null
	 */
	private function resolve_origin( string $route, string $method ): ?array {
		// Tier 1 — structural: any route under the dedicated POS namespace. Non-spoofable.
		// Intent may be null (e.g. a POS read), which is fine: the swap still applies, the cap
		// bridge simply has nothing to grant.
		if ( 0 === strpos( $route, '/wc/pos/v1/' ) ) {
			return array(
				'tier'   => self::TIER_STRUCTURAL,
				'intent' => $this->intent_for_pos_route( $route, $method ),
			);
		}

		// Tier 2 — header bridge: only allowlisted wc/v3 writes, and only with the POS marker
		// header. Requires a recognized write intent; reads are out of scope for the bridge.
		$has_marker = isset( $_SERVER[ self::HEADER_POS_REQUEST ] )
			&& '1' === sanitize_text_field( wp_unslash( $_SERVER[ self::HEADER_POS_REQUEST ] ) );
		if ( ! $has_marker ) {
			return null;
		}

		$intent = $this->intent_for_v3_route( $route, $method );
		if ( null === $intent ) {
			return null;
		}

		return array(
			'tier'   => self::TIER_HEADER,
			'intent' => $intent,
		);
	}

	/**
	 * Map an allowlisted wc/v3 write route + method to an operation intent, or null.
	 *
	 * @param string $route  Normalized REST route.
	 * @param string $method Uppercased HTTP method.
	 * @return string|null
	 */
	private function intent_for_v3_route( string $route, string $method ): ?string {
		if ( 'POST' === $method && '/wc/v3/orders' === $route ) {
			return self::INTENT_ORDER_CREATE;
		}
		if (
			'POST' === $method
			&& 1 === preg_match( '#^/wc/v3/orders/\d+/refunds$#', $route )
		) {
			return self::INTENT_REFUND_CREATE;
		}
		if (
			in_array( $method, array( 'POST', 'PUT' ), true )
			&& 1 === preg_match( '#^/wc/v3/orders/\d+$#', $route )
		) {
			return self::INTENT_ORDER_UPDATE;
		}
		if ( 'POST' === $method && '/wc/v3/coupons' === $route ) {
			return self::INTENT_COUPON_CREATE;
		}
		return null;
	}

	/**
	 * Map a structural POS-namespace write route + method to an operation intent, or null.
	 *
	 * Reads (e.g. GET /wc/pos/v1/whoami) return null intent but are still POS-originated.
	 *
	 * @param string $route  Normalized REST route.
	 * @param string $method Uppercased HTTP method.
	 * @return string|null
	 */
	private function intent_for_pos_route( string $route, string $method ): ?string {
		if ( 'POST' === $method && '/wc/pos/v1/orders' === $route ) {
			return self::INTENT_ORDER_CREATE;
		}
		if (
			'POST' === $method
			&& 1 === preg_match( '#^/wc/pos/v1/orders/\d+/refunds$#', $route )
		) {
			return self::INTENT_REFUND_CREATE;
		}
		if ( 'POST' === $method && '/wc/pos/v1/coupons' === $route ) {
			return self::INTENT_COUPON_CREATE;
		}
		return null;
	}

	/**
	 * Derive the normalized REST route at determine_current_user time.
	 *
	 * Prefers the parsed `rest_route` query var (set before serve_request, the same source
	 * StoreApi\Authentication uses); falls back to the `rest_route` query arg, then to parsing
	 * REQUEST_URI relative to the REST prefix. Returns '' when this is not resolvable as a REST
	 * route. Output always has a single leading slash and no query string.
	 *
	 * @return string
	 */
	private function get_rest_route(): string {
		$route = '';

		if ( isset( $GLOBALS['wp'] ) && isset( $GLOBALS['wp']->query_vars['rest_route'] ) ) {
			$route = (string) $GLOBALS['wp']->query_vars['rest_route'];
		} elseif ( isset( $_GET['rest_route'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$route = sanitize_text_field( wp_unslash( $_GET['rest_route'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		} elseif ( isset( $_SERVER['REQUEST_URI'] ) ) {
			$request_uri = esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) );
			$prefix      = '/' . rest_get_url_prefix() . '/';
			$pos         = strpos( $request_uri, $prefix );
			if ( false !== $pos ) {
				$route = substr( $request_uri, $pos + strlen( $prefix ) );
				$route = (string) strtok( $route, '?' );
			}
		}

		$route = trim( $route );
		if ( '' === $route ) {
			return '';
		}

		return '/' . ltrim( $route, '/' );
	}
}
