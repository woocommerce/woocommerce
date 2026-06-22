<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS\Auth;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Internal\Features\FeaturesController;

/**
 * Request-scoped detector for "POS-originated" REST requests — the boundary that gates the POS staff
 * current-user swap (see POSAuthHandler). Evaluated once per request and memoized.
 *
 * It runs at determine_current_user time, before REST dispatch, so there is no WP_REST_Request and
 * no parsed body. Detection reads only $_SERVER (method + headers) and the parsed REST route from
 * $GLOBALS['wp']->query_vars['rest_route'] (the source StoreApi\Authentication uses); request body
 * data is deliberately ignored — it is unavailable this early and client-controlled.
 *
 * A request is POS-originated when it is an allowlisted wc/v3 write (orders / refunds / coupons)
 * carrying both the `X-WC-POS-Request: 1` marker and an `X-WC-POS-Staff-Id` naming the staff member
 * to act as. (POS reads — the staff and catalog endpoints under /wc/pos/v1 — need no swap, so they
 * are not detected here.) The marker is spoofable, but it only scopes WHEN the swap runs;
 * POSAuthHandler still requires the pre-swap user to be the device admin, so spoofing it alone
 * achieves nothing. The POC trusts the staff id (gated by the device-admin auth); a per-request staff
 * credential is the deferred follow-up. The `X-WC-POS-*` headers are POC-local conventions, not a
 * WooCommerce standard.
 *
 * @since 11.0.0
 * @internal
 */
class POSRequestContext {

	private const FEATURE_FLAG = 'point_of_sale_staff';

	private const HEADER_POS_REQUEST  = 'HTTP_X_WC_POS_REQUEST';
	private const HEADER_STAFF_ID     = 'HTTP_X_WC_POS_STAFF_ID';
	private const HEADER_INITIATOR_ID = 'HTTP_X_WC_POS_INITIATOR_ID';

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
	 * Memoized detection result, or null until resolved.
	 *
	 * @var array{is_pos_request:bool,intent:string|null,staff_id:int}|null
	 */
	private ?array $resolved = null;

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
	 * Whether the current request is a POS-originated write naming a staff member.
	 *
	 * Necessary-but-not-sufficient for a swap: POSAuthHandler also requires the pre-swap user to be
	 * the device admin and the named staff to hold the operation's cap.
	 *
	 * @return bool
	 */
	public function is_pos_request(): bool {
		return $this->resolved()['is_pos_request'];
	}

	/**
	 * The staff user id asserted by the request header (0 when absent/invalid). Device-asserted; the
	 * POC trusts it without a per-request credential check.
	 *
	 * @return int
	 */
	public function get_staff_id(): int {
		return $this->resolved()['staff_id'];
	}

	/**
	 * The operation intent (an INTENT_* constant), or null when not POS-originated. Used by
	 * POSCapBridge to scope the temporary capability grant.
	 *
	 * @return string|null
	 */
	public function get_intent(): ?string {
		return $this->resolved()['intent'];
	}

	/**
	 * The initiator staff id from the `X-WC-POS-Initiator-Id` header (0 when absent).
	 *
	 * The initiator is the staff member who initiated an action another staff member authorized (e.g.
	 * the cashier on a manager-approved override refund). It is attribution context, not a credential,
	 * so it never gates detection or the swap and is read on demand.
	 *
	 * @return int
	 */
	public function get_initiator_id(): int {
		return isset( $_SERVER[ self::HEADER_INITIATOR_ID ] )
			? absint( wp_unslash( $_SERVER[ self::HEADER_INITIATOR_ID ] ) )
			: 0;
	}

	/**
	 * Resolve and memoize the detection result.
	 *
	 * The determine_current_user filter can fire during bootstrap, before REST_REQUEST/the route
	 * exist. A negative computed then must NOT be memoized, or the real REST dispatch (where the swap
	 * matters) would read a stale false — so the result is only locked in once the REST context is ready.
	 *
	 * @return array{is_pos_request:bool,intent:string|null,staff_id:int}
	 */
	private function resolved(): array {
		if ( null !== $this->resolved ) {
			return $this->resolved;
		}

		if ( ! $this->rest_context_ready() ) {
			return self::non_pos_result();
		}

		$this->resolved = $this->compute();
		return $this->resolved;
	}

	/**
	 * Whether the REST request context is established enough to make a final decision.
	 *
	 * @return bool
	 */
	private function rest_context_ready(): bool {
		return defined( 'REST_REQUEST' ) && REST_REQUEST && '' !== $this->get_rest_route();
	}

	/**
	 * Compute detection from $_SERVER + the parsed REST route only.
	 *
	 * @return array{is_pos_request:bool,intent:string|null,staff_id:int}
	 */
	private function compute(): array {
		if ( ! $this->features_controller->feature_is_enabled( self::FEATURE_FLAG ) ) {
			return self::non_pos_result();
		}

		// Store API has its own (guest) identity model on the shared determine_current_user hook —
		// never treat its traffic as POS. Defensive: the wc/v3 write allowlist below already excludes it.
		$route = $this->get_rest_route();
		if ( '' === $route || 0 === strpos( $route, '/wc/store/' ) ) {
			return self::non_pos_result();
		}

		// Both POS headers are required: X-WC-POS-Staff-Id names who to act as, X-WC-POS-Request marks
		// the request as POS-originated.
		$staff_id   = isset( $_SERVER[ self::HEADER_STAFF_ID ] )
			? absint( wp_unslash( $_SERVER[ self::HEADER_STAFF_ID ] ) )
			: 0;
		$has_marker = isset( $_SERVER[ self::HEADER_POS_REQUEST ] )
			&& '1' === sanitize_text_field( wp_unslash( $_SERVER[ self::HEADER_POS_REQUEST ] ) );
		if ( $staff_id <= 0 || ! $has_marker ) {
			return self::non_pos_result();
		}

		$method = isset( $_SERVER['REQUEST_METHOD'] )
			? strtoupper( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) )
			: '';

		$intent = $this->intent_for_write( $route, $method );
		if ( null === $intent ) {
			return self::non_pos_result();
		}

		return array(
			'is_pos_request' => true,
			'intent'         => $intent,
			'staff_id'       => $staff_id,
		);
	}

	/**
	 * The "not POS-originated" detection result.
	 *
	 * @return array{is_pos_request:bool,intent:string|null,staff_id:int}
	 */
	private static function non_pos_result(): array {
		return array(
			'is_pos_request' => false,
			'intent'         => null,
			'staff_id'       => 0,
		);
	}

	/**
	 * Map an allowlisted wc/v3 write route + method to an operation intent, or null.
	 *
	 * @param string $route  Normalized REST route (leading slash, no prefix/query).
	 * @param string $method Uppercased HTTP method.
	 * @return string|null
	 */
	private function intent_for_write( string $route, string $method ): ?string {
		if ( 'POST' === $method && '/wc/v3/orders' === $route ) {
			return self::INTENT_ORDER_CREATE;
		}
		if ( 'POST' === $method && 1 === preg_match( '#^/wc/v3/orders/\d+/refunds$#', $route ) ) {
			return self::INTENT_REFUND_CREATE;
		}
		if ( in_array( $method, array( 'POST', 'PUT' ), true ) && 1 === preg_match( '#^/wc/v3/orders/\d+$#', $route ) ) {
			return self::INTENT_ORDER_UPDATE;
		}
		if ( 'POST' === $method && '/wc/v3/coupons' === $route ) {
			return self::INTENT_COUPON_CREATE;
		}
		return null;
	}

	/**
	 * The current REST route at determine_current_user time, or '' if not resolvable yet.
	 *
	 * Reads the parsed `rest_route` query var — the same source WC itself uses (e.g.
	 * StoreApi\Authentication::is_request_to_store_api(), Utilities\RestApiUtil, and
	 * wc_rest_should_load_namespace()). WP core's `WP::parse_request()` (wp-includes/class-wp.php)
	 * populates it for both pretty and plain (?rest_route=…) permalinks, before REST serve_request
	 * dispatches. An empty value keeps resolved() from memoizing (via rest_context_ready()), so a
	 * too-early call simply re-evaluates once the route exists. Normalized to a single leading
	 * slash, no query string.
	 *
	 * @return string
	 */
	private function get_rest_route(): string {
		$route = isset( $GLOBALS['wp']->query_vars['rest_route'] )
			? trim( (string) $GLOBALS['wp']->query_vars['rest_route'] )
			: '';

		return '' === $route ? '' : '/' . ltrim( $route, '/' );
	}
}
