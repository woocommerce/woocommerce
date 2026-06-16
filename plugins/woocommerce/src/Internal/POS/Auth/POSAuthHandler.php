<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS\Auth;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Internal\POS\Capabilities;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use WP_Error;

/**
 * Swaps the effective current user to the named POS staff member for POS-originated requests,
 * implementing the "authenticate high, at determine_current_user" model.
 *
 * The device authenticates first (Jetpack tunnel / Application Password) and resolves to a
 * shop-manager/admin user. For a POS-originated request (see POSRequestContext) naming a staff
 * member, this handler switches the effective user to that staff member, so downstream WP/Woo
 * logic — capability checks, order/refund authorship, logs — naturally sees the staff member as
 * the actor.
 *
 * POC credential note: the named staff id is currently TRUSTED as asserted (gated only by the
 * device-admin auth) — there is no per-request staff credential check. Authenticating the staff
 * (verify a PIN once at login/override, then carry a short-lived token on the request) is the
 * deferred v3.1 follow-up. Until then the swap + capability bridge are a plumbing demonstration,
 * not an enforcement boundary against a malicious till operator.
 *
 * Swap-target rule: the swap targets the named staff member, and only if they genuinely hold the
 * `woocommerce_pos_*` capability the operation requires. That staff member is the authorizing actor
 * — for a normal sale the operator (cashier), and for a manager-approved override refund the
 * approving manager. A cap-less initiator (e.g. the cashier on an override refund) is never the
 * swap target; they are recorded separately as attribution metadata at insert time. If the named
 * staff does not hold the required cap, no swap happens and the request continues as the device
 * admin.
 *
 * Hooks (both required):
 *  - `determine_current_user` @ 100 — primary. Runs after core Application Password (20) and WC
 *    auth (15), so the device admin is already resolved.
 *  - `rest_authentication_errors` @ 20 — safety net. WC's authentication_fallback sets the user via
 *    a bare wp_set_current_user() that bypasses the determine_current_user filter chain; without
 *    this the swap would be silently skipped on that path. Runs before REST dispatch's permission
 *    callbacks.
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
	 * The device admin user id captured before the swap (0 if no swap happened).
	 *
	 * @var int
	 */
	private int $device_admin_id = 0;

	/**
	 * The staff user id swapped in (0 if no swap happened).
	 *
	 * @var int
	 */
	private int $staff_user_id = 0;

	/**
	 * Whether the swap has already been applied this request (idempotency guard).
	 *
	 * @var bool
	 */
	private bool $swapped = false;

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
	 * Primary swap point on determine_current_user.
	 *
	 * @internal
	 *
	 * @param int|false $user_id The user id resolved by earlier auth callbacks (the device admin),
	 *                           or false/0 if none.
	 * @return int|false The staff user id when swapping, otherwise the input unchanged.
	 */
	public function maybe_swap( $user_id ) {
		$staff_id = $this->decide_swap( (int) $user_id );
		if ( $staff_id <= 0 ) {
			return $user_id;
		}

		$this->record_swap( (int) $user_id, $staff_id );
		return $staff_id;
	}

	/**
	 * Safety-net swap on rest_authentication_errors, covering the authentication_fallback path that
	 * bypasses the determine_current_user filter chain.
	 *
	 * @internal
	 *
	 * @param WP_Error|null|true $errors Current authentication error state.
	 * @return WP_Error|null|true The input unchanged (a swap never raises an auth error).
	 */
	public function maybe_swap_after_fallback( $errors ) {
		// Do not swap into an already-failed authentication, and do not re-swap.
		if ( is_wp_error( $errors ) || $this->swapped ) {
			return $errors;
		}

		$device_id = get_current_user_id();
		$staff_id  = $this->decide_swap( $device_id );
		if ( $staff_id > 0 ) {
			$this->record_swap( $device_id, $staff_id );
			wp_set_current_user( $staff_id );
		}

		// Never convert a null/true auth state into an error.
		return $errors;
	}

	/**
	 * Decide whether to swap, and to whom, for the given pre-swap (device) user.
	 *
	 * Returns the staff user id to swap to, or 0 to leave the user unchanged. All conditions must
	 * hold: POS-originated request, device user is a real shop-manager/admin, the PIN verifies for
	 * the presented staff, the staff has POS access, and — for a write — the staff holds the POS
	 * capability the operation requires.
	 *
	 * @param int $device_user_id The resolved pre-swap (device admin) user id.
	 * @return int
	 */
	private function decide_swap( int $device_user_id ): int {
		if ( $this->swapped ) {
			return $this->staff_user_id;
		}

		if ( ! $this->request_context->is_pos_request() ) {
			return 0;
		}

		// Never swap from an unauthenticated or non-privileged device user: the device must already
		// be a real shop-manager/admin before a staff member can ride on top of it.
		if ( $device_user_id <= 0 || ! user_can( $device_user_id, 'manage_woocommerce' ) ) {
			return 0;
		}

		$staff_id = $this->request_context->get_staff_id();
		if ( $staff_id <= 0 ) {
			return 0;
		}

		// POC: the staff id is trusted as asserted (gated by the device-admin auth above). There is
		// no per-request staff credential check yet — authenticating the staff (verify a PIN once at
		// login/override, then carry a short-lived token here) is the deferred v3.1 follow-up.
		if ( ! Capabilities::has_pos_access( $staff_id ) ) {
			return 0;
		}

		// Swap-target rule: for a write, the swapped-in staff must genuinely hold the required POS
		// capability. Reads (null intent, e.g. the whoami route) require only POS access.
		$required_cap = self::required_pos_cap_for_intent( $this->request_context->get_intent() );
		if ( null !== $required_cap && ! Capabilities::user_has_pos_capability( $staff_id, $required_cap ) ) {
			return 0;
		}

		return $staff_id;
	}

	/**
	 * Record a completed swap (for the cap bridge / audit) and mark it applied.
	 *
	 * @param int $device_user_id The pre-swap device admin id.
	 * @param int $staff_id       The staff id swapped in.
	 */
	private function record_swap( int $device_user_id, int $staff_id ): void {
		$this->device_admin_id = $device_user_id;
		$this->staff_user_id   = $staff_id;
		$this->swapped         = true;
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
