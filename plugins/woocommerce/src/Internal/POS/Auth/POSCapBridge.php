<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS\Auth;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Internal\POS\Capabilities;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use WP_User;

/**
 * Bridges the isolated `woocommerce_pos_*` capabilities to the real Woo caps a POS write needs, only
 * for the duration of a POS-originated request.
 *
 * The POS caps aren't mapped to core post-type caps, so a staff member swapped in by POSAuthHandler
 * would 403 on `/wc/v3/orders` etc. (which check `edit_shop_orders` / `publish_shop_orders`). This
 * filter grants the minimal real cap, scoped on three conditions so it can't leak: the request is
 * POS-originated, it has a recognized write intent, and the user being checked already holds the
 * matching `woocommerce_pos_*` cap — which, by the swap-target rule, is exactly the swapped-in staff
 * member. That last check is also why there's no override special case (the approving manager already
 * holds `woocommerce_pos_issue_refunds`). The grant is inert the instant the request isn't
 * POS-originated, and intentionally minimal — widen it only as real `current_user_can()` checks in
 * the save path are observed.
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
	 * Register the capability bridge filter.
	 *
	 * @since 11.0.0
	 */
	public function register(): void {
		add_filter( 'user_has_cap', array( $this, 'grant_pos_caps' ), 10, 4 );
	}

	/**
	 * Grant the minimal real Woo caps for the current POS write, scoped to POS-origin + intent +
	 * the user genuinely holding the matching POS capability.
	 *
	 * @internal
	 *
	 * @param array<string, bool> $allcaps All capabilities of the user being checked.
	 * @param string[]            $caps    Required primitive capabilities (unused).
	 * @param array<int, mixed>   $args    Cap-check args (unused).
	 * @param WP_User             $user    The user being checked (unused; scoping is via $allcaps).
	 * @return array<string, bool>
	 */
	public function grant_pos_caps( $allcaps, $caps, $args, $user ) {
		unset( $caps, $args, $user );

		if ( ! is_array( $allcaps ) ) {
			return $allcaps;
		}

		if ( ! $this->request_context->is_pos_request() ) {
			return $allcaps;
		}

		$intent = $this->request_context->get_intent();
		if ( null === $intent ) {
			return $allcaps;
		}

		$required_pos_cap = POSAuthHandler::required_pos_cap_for_intent( $intent );
		if ( null === $required_pos_cap ) {
			return $allcaps;
		}

		// The user being checked must already hold the POS cap for this operation. By the
		// swap-target rule only the swapped-in staff member does, which scopes the grant to them.
		if ( empty( $allcaps[ $required_pos_cap ] ) ) {
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
