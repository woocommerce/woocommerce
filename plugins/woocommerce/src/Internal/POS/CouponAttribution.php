<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Internal\POS\Auth\POSRequestContext;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use WC_Coupon;
use WP_User;

/**
 * Records the POS initiator on attributable coupon writes.
 *
 * The coupon analogue of {@see OrderAttribution}: under the v3 server-side-auth model the acting
 * staff member is the effective `current_user`, so they are recorded as the actor for free. The one
 * fact the swap cannot carry is the *initiator* of a manager-authorized coupon creation, which the
 * client sends as the `X-WC-POS-Initiator-Id` header. This class records it on the coupon as
 * `_woocommerce_pos_initiator_user_id` meta. Coupons have no order-note timeline, so audit lands in
 * the WC log (the meta persists for a future wp-admin UI).
 *
 * Clean break: the pre-v3 `_pos_staff_user_id` / `_pos_override_*` shapes are not read or supported.
 *
 * @since 11.0.0
 * @internal
 */
class CouponAttribution implements RegisterHooksInterface {

	public const LOG_SOURCE = 'woocommerce-pos';

	/**
	 * Request context detector, used to scope attribution to POS-originated writes.
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
	 * Register the post-insert hook for shop_coupon.
	 *
	 * @since 11.0.0
	 */
	public function register(): void {
		add_action( 'woocommerce_rest_insert_shop_coupon_object', array( $this, 'handle_post_insert' ), 10, 3 );
	}

	/**
	 * Record POS staff attribution for a saved coupon.
	 *
	 * No-op unless the request is POS-originated and the effective current user is a POS staff
	 * member. Records the actor (and the initiator when an override header is present) as coupon meta
	 * and a WC log line — coupons have no order-note timeline.
	 *
	 * @internal
	 *
	 * @param WC_Coupon $coupon   The freshly-saved coupon.
	 * @param mixed     $request  The incoming request (unused).
	 * @param bool      $creating Whether this is a create (true) or update (false).
	 */
	public function handle_post_insert( $coupon, $request, $creating ): void {
		unset( $request, $creating );

		if ( ! $coupon instanceof WC_Coupon ) {
			return;
		}

		if ( ! $this->request_context->is_pos_request() ) {
			return;
		}

		$actor_id = get_current_user_id();
		if ( $actor_id <= 0 || ! Capabilities::has_pos_access( $actor_id ) ) {
			return;
		}

		$actor = get_userdata( $actor_id );
		if ( ! $actor instanceof WP_User ) {
			return;
		}

		$initiator = $this->resolve_initiator( $coupon, $actor_id );

		$coupon->update_meta_data( OrderAttribution::META_KEY_ACTOR_USER_ID, (string) $actor_id );
		if ( $initiator instanceof WP_User ) {
			$coupon->update_meta_data( OrderAttribution::META_KEY_INITIATOR_USER_ID, (string) $initiator->ID );
		}
		$coupon->save_meta_data();

		$message = $initiator instanceof WP_User
			? sprintf(
				'POS coupon %1$d by user %2$s (ID %3$d), initiated by user %4$s (ID %5$d).',
				$coupon->get_id(),
				$actor->user_login,
				$actor->ID,
				$initiator->user_login,
				$initiator->ID
			)
			: sprintf(
				'POS coupon %1$d by user %2$s (ID %3$d).',
				$coupon->get_id(),
				$actor->user_login,
				$actor->ID
			);

		wc_get_logger()->info( $message, array( 'source' => self::LOG_SOURCE ) );
	}

	/**
	 * Resolve the initiator user from the request header, or null when there is none to record.
	 *
	 * Best-effort: a missing initiator (or one equal to the actor) is ignored; an id referencing a
	 * non-existent user or one without POS access is logged and skipped rather than fatal.
	 *
	 * @param WC_Coupon $coupon   The saved coupon (for the log line).
	 * @param int       $actor_id The acting staff member (current user).
	 * @return WP_User|null
	 */
	private function resolve_initiator( WC_Coupon $coupon, int $actor_id ): ?WP_User {
		$initiator_id = $this->request_context->get_initiator_id();
		if ( null === $initiator_id || $initiator_id === $actor_id ) {
			return null;
		}

		$initiator = get_userdata( $initiator_id );
		if ( ! $initiator instanceof WP_User || ! Capabilities::has_pos_access( $initiator_id ) ) {
			wc_get_logger()->warning(
				sprintf(
					'POS coupon initiator attribution skipped: user %d is missing or lacks POS access at write time (coupon %d).',
					$initiator_id,
					$coupon->get_id()
				),
				array( 'source' => self::LOG_SOURCE )
			);
			return null;
		}

		return $initiator;
	}
}
