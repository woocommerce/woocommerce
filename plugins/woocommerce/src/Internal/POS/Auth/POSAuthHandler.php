<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS\Auth;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Internal\POS\Capabilities;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use WP_Error;

/**
 * Swaps the effective current user to the named POS staff member for POS-originated requests
 * ("authenticate high, at determine_current_user").
 *
 * The device authenticates first (Jetpack tunnel / Application Password) as a shop-manager/admin;
 * for a POS request naming a staff member (see POSRequestContext) the effective user is switched to
 * that staff member, so capability checks, order/refund authorship, and logs see them as the actor.
 *
 * The swap targets the named staff only if they hold the `woocommerce_pos_*` capability the
 * operation requires — the authorizing actor (cashier for a sale, approving manager for an override
 * refund). A cap-less initiator is recorded separately as attribution metadata, never swapped in.
 *
 * POC note: the asserted staff id is TRUSTED (gated only by the device-admin auth); a per-request
 * staff credential (verify a PIN once at login → carry a short-lived token) is the deferred follow-up.
 *
 * Two hooks are needed: `determine_current_user` @100 (primary; after core/WC auth) and
 * `rest_authentication_errors` @20 (safety net for WC's authentication_fallback, which sets the user
 * via a bare wp_set_current_user() that bypasses the determine_current_user chain).
 *
 * @since 11.0.0
 * @internal
 */
class POSAuthHandler implements RegisterHooksInterface {

	/**
	 * Request context detector.
	 *
	 * @var POSRequestContext
	 */
	private POSRequestContext $request_context;

	/**
	 * Pre-swap device admin id, captured once when a swap is committed (0 if none).
	 *
	 * @var int
	 */
	private int $device_admin_id = 0;

	/**
	 * Staff id swapped in, set once when a swap is committed (0 if none). Doubles as the
	 * "already resolved this request" guard.
	 *
	 * @var int
	 */
	private int $staff_user_id = 0;

	/**
	 * Initialize dependencies via the DI container.
	 *
	 * @internal
	 *
	 * @param POSRequestContext $request_context The request-shape detector.
	 */
	final public function init( POSRequestContext $request_context ): void {
		$this->request_context = $request_context;
	}

	/**
	 * Register the swap hooks.
	 *
	 * @since 11.0.0
	 */
	public function register(): void {
		add_filter( 'determine_current_user', array( $this, 'maybe_swap' ), 100 );
		add_filter( 'rest_authentication_errors', array( $this, 'maybe_swap_after_fallback' ), 20 );
	}

	/**
	 * Primary swap on determine_current_user.
	 *
	 * @internal
	 *
	 * @param int|false $user_id The user id resolved by earlier auth callbacks (the device admin).
	 * @return int|false The staff id when swapping, otherwise the input unchanged.
	 */
	public function maybe_swap( $user_id ) {
		$staff_id = $this->resolve_swap( (int) $user_id );
		return $staff_id > 0 ? $staff_id : $user_id;
	}

	/**
	 * Safety-net swap on rest_authentication_errors, covering WC's authentication_fallback path that
	 * bypasses the determine_current_user filter chain. Returns $errors unchanged (a swap never
	 * raises an auth error).
	 *
	 * @internal
	 *
	 * @param WP_Error|null|true $errors Current authentication error state.
	 * @return WP_Error|null|true
	 */
	public function maybe_swap_after_fallback( $errors ) {
		if ( is_wp_error( $errors ) ) {
			return $errors;
		}

		$staff_id = $this->resolve_swap( get_current_user_id() );
		if ( $staff_id > 0 && get_current_user_id() !== $staff_id ) {
			wp_set_current_user( $staff_id );
		}

		return $errors;
	}

	/**
	 * The single swap decision: return the staff id to run as, or 0 to leave the user unchanged.
	 *
	 * Reads top-to-bottom — POS request? POS admin? staffer exists with the cap? then commit.
	 * Idempotent: once committed, later calls return the same staff id without re-evaluating, so a
	 * post-swap current_user (which is the staff id) can never feed back in and be mistaken for the
	 * device admin.
	 *
	 * @param int $device_user_id The pre-swap (device admin) user id.
	 * @return int
	 */
	private function resolve_swap( int $device_user_id ): int {
		if ( $this->staff_user_id > 0 ) {
			return $this->staff_user_id;
		}

		// 1. POS-originated request?
		if ( ! $this->request_context->is_pos_request() ) {
			return 0;
		}

		// 2. A real POS admin? Never swap up from user 0 or a non-manager device user.
		if ( $device_user_id <= 0 || ! user_can( $device_user_id, 'manage_woocommerce' ) ) {
			return 0;
		}

		// 3. Does the named staffer exist with POS access — and, for a write, the required cap?
		$staff_id = $this->request_context->get_staff_id();
		if ( $staff_id <= 0 || ! Capabilities::has_pos_access( $staff_id ) ) {
			return 0;
		}
		$required_cap = self::required_pos_cap_for_intent( $this->request_context->get_intent() );
		if ( null !== $required_cap && ! Capabilities::user_has_pos_capability( $staff_id, $required_cap ) ) {
			return 0;
		}

		// 4. Commit. device_admin_id is captured exactly once, here.
		$this->device_admin_id = $device_user_id;
		$this->staff_user_id   = $staff_id;
		return $staff_id;
	}

	/**
	 * The pre-swap device admin user id, or 0 if no swap happened this request.
	 *
	 * @return int
	 */
	public function get_device_admin_id(): int {
		return $this->device_admin_id;
	}

	/**
	 * Map an operation intent to the POS capability it requires, or null for reads.
	 *
	 * @param string|null $intent One of the POSRequestContext::INTENT_* constants, or null.
	 * @return string|null A Capabilities::CAP_* value, or null when no specific cap is required.
	 */
	public static function required_pos_cap_for_intent( ?string $intent ): ?string {
		switch ( $intent ) {
			case POSRequestContext::INTENT_ORDER_CREATE:
			case POSRequestContext::INTENT_ORDER_UPDATE:
				return Capabilities::CAP_PROCESS_SALES;
			case POSRequestContext::INTENT_REFUND_CREATE:
				return Capabilities::CAP_ISSUE_REFUNDS;
			case POSRequestContext::INTENT_COUPON_CREATE:
				return Capabilities::CAP_CREATE_COUPONS;
			default:
				return null;
		}
	}
}
