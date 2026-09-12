<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS\Auth;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use WP_User;

/**
 * Bridges the isolated `woocommerce_pos_*` capabilities to the real Woo caps a POS write needs — but
 * only for the staff member a device-admin swap actually committed to this request.
 *
 * The POS caps aren't mapped to core post-type caps, so a staff member swapped in by POSAuthHandler
 * would 403 on `/wc/v3/orders` etc. (which check `edit_shop_orders` / `publish_shop_orders`). This
 * filter grants the minimal real cap — scoped to the user POSAuthHandler swapped in, never to a
 * directly-authenticated user who merely holds an isolated `woocommerce_pos_*` cap and sends the POS
 * headers. The committed swap only happens after the device-admin auth + capability checks, so
 * requiring it is what keeps the isolated POS caps from being self-escalated into real
 * order/refund/coupon write access. The granted set is intentionally minimal — widen it only as real
 * `current_user_can()` checks in the save path are observed.
 *
 * @since 11.0.0
 * @internal
 */
class POSCapBridge implements RegisterHooksInterface {

	/**
	 * Request context detector.
	 *
	 * @var POSRequestContext
	 */
	private POSRequestContext $request_context;

	/**
	 * Swap handler — the source of truth for whether, and to whom, a device-admin swap committed.
	 *
	 * @var POSAuthHandler
	 */
	private POSAuthHandler $auth_handler;

	/**
	 * Initialize dependencies via the DI container.
	 *
	 * @internal
	 *
	 * @param POSRequestContext $request_context The request-shape detector.
	 * @param POSAuthHandler    $auth_handler    The current-user swap handler.
	 */
	final public function init( POSRequestContext $request_context, POSAuthHandler $auth_handler ): void {
		$this->request_context = $request_context;
		$this->auth_handler    = $auth_handler;
	}

	/**
	 * Register the capability bridge filter.
	 *
	 * @since 11.0.0
	 */
	public function register(): void {
		add_filter( 'user_has_cap', array( $this, 'grant_pos_caps' ), 10, 4 );
	}

	/**
	 * Grant the minimal real Woo caps for the current POS write, but only to the staff member the
	 * device-admin swap committed to this request.
	 *
	 * Header-detected POS origin is request *context*, not authorization. Granting on "POS request +
	 * the user holds an isolated woocommerce_pos_* cap" alone would let a directly-authenticated user
	 * who happens to hold such a cap self-grant real write caps just by sending the headers. Gating on
	 * the committed swap — the user being checked IS the swapped-in staff member, which POSAuthHandler
	 * only sets after the device-admin auth + capability checks — closes that escalation.
	 *
	 * @internal
	 *
	 * @param array<string, bool> $allcaps All capabilities of the user being checked.
	 * @param string[]            $caps    Required primitive capabilities (unused).
	 * @param array<int, mixed>   $args    Cap-check args (unused).
	 * @param WP_User             $user    The user being checked.
	 * @return array<string, bool>
	 */
	public function grant_pos_caps( $allcaps, $caps, $args, $user ) {
		unset( $caps, $args );

		if ( ! is_array( $allcaps ) || ! $user instanceof WP_User ) {
			return $allcaps;
		}

		// Only the staff member a device-admin swap committed to this request gets the grant.
		$swapped_staff_id = $this->auth_handler->get_swapped_staff_id();
		if ( null === $swapped_staff_id || (int) $user->ID !== $swapped_staff_id ) {
			return $allcaps;
		}

		$intent = $this->request_context->get_intent();
		if ( null === $intent ) {
			return $allcaps;
		}

		foreach ( self::woo_caps_for_intent( $intent ) as $cap ) {
			$allcaps[ $cap ] = true;
		}

		return $allcaps;
	}

	/**
	 * The minimal real Woo capabilities to grant for an operation intent.
	 *
	 * @param string $intent One of the POSRequestContext::INTENT_* constants.
	 * @return string[]
	 */
	private static function woo_caps_for_intent( string $intent ): array {
		switch ( $intent ) {
			case POSRequestContext::INTENT_ORDER_CREATE:
				return array( 'publish_shop_orders', 'edit_shop_orders' );
			case POSRequestContext::INTENT_ORDER_UPDATE:
				return array( 'edit_shop_orders', 'edit_others_shop_orders' );
			case POSRequestContext::INTENT_REFUND_CREATE:
				// A refund is a shop_order_refund post (capability_type shop_order), so creating one
				// needs the "publish" create cap; editing the parent order's totals needs the edit caps.
				return array( 'publish_shop_orders', 'edit_shop_orders', 'edit_others_shop_orders' );
			case POSRequestContext::INTENT_COUPON_CREATE:
				return array( 'publish_shop_coupons', 'edit_shop_coupons' );
			default:
				return array();
		}
	}
}
