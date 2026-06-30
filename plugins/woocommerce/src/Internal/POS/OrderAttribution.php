<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Internal\POS\Auth\POSRequestContext;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use WC_Abstract_Order;
use WC_Order;
use WC_Order_Refund;
use WP_User;

/**
 * Records the POS initiator on attributable order/refund writes.
 *
 * The swap (POSAuthHandler) makes the staff member the effective `current_user`, which is correct
 * for live signals — `refunded_by`, note authorship, capability checks. But Core does not durably
 * expose *who created an order* (HPOS has no author column; the legacy post_author is a fragile
 * placeholder, empty for orders not created through the swap), so this class records the actor
 * explicitly on every POS-originated write: `_woocommerce_pos_actor_user_id` meta + an order note.
 *
 * On a manager-authorized override the swap cannot represent the *initiator* — the cashier who
 * initiated an action the manager approved. The client sends it as the `X-WC-POS-Initiator-Id`
 * request header (so the whole auth/attribution context rides in headers, not the body); this class
 * validates it (best-effort, log-and-skip), records it as `_woocommerce_pos_initiator_user_id` meta,
 * and names both in the note. Plain writes carry only the actor.
 *
 * Clean break: this is a new contract. The pre-v3 `_pos_staff_user_id` / `_pos_override_*` shapes
 * are not read or supported — the feature is behind an off-by-default dev flag with no production
 * consumers.
 *
 * attribute_order() is path-agnostic (order + creating flag), so a future Store API
 * checkout/completion hook can reuse it unchanged.
 *
 * @since 11.0.0
 * @internal
 */
class OrderAttribution implements RegisterHooksInterface {

	public const META_KEY_ACTOR_USER_ID     = '_woocommerce_pos_actor_user_id';
	public const META_KEY_INITIATOR_USER_ID = '_woocommerce_pos_initiator_user_id';
	public const LOG_SOURCE                 = 'woocommerce-pos';

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
	 * Register the post-insert hooks for shop_order + shop_order_refund.
	 *
	 * @since 11.0.0
	 */
	public function register(): void {
		add_action( 'woocommerce_rest_insert_shop_order_object', array( $this, 'handle_post_insert' ), 10, 3 );
		add_action( 'woocommerce_rest_insert_shop_order_refund_object', array( $this, 'handle_post_insert' ), 10, 3 );
	}

	/**
	 * REST adapter: bridge the wc/v3 insert-action signature to attribute_order().
	 *
	 * @internal
	 *
	 * @param WC_Abstract_Order $order    The freshly-saved order or refund.
	 * @param mixed             $request  The incoming request (unused).
	 * @param bool              $creating Whether this is a create (true) or update (false).
	 */
	public function handle_post_insert( $order, $request, $creating ): void {
		unset( $request );

		if ( ! $order instanceof WC_Abstract_Order ) {
			return;
		}

		$this->attribute_order( $order, (bool) $creating );
	}

	/**
	 * Record POS staff attribution for a saved order/refund.
	 *
	 * No-op unless the request is POS-originated and the effective current user is a POS staff
	 * member (i.e. the swap landed). Records the actor on every such write and the initiator when an
	 * override header is present, as meta + one order note. Refund notes attach to the parent order,
	 * since refunds don't expose add_order_note().
	 *
	 * @since 11.0.0
	 *
	 * @param WC_Abstract_Order $order    The freshly-saved order or refund.
	 * @param bool              $creating Whether this is a create (true) or update (false).
	 */
	public function attribute_order( WC_Abstract_Order $order, bool $creating ): void {
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

		$initiator = $this->resolve_initiator( $order, $actor_id );

		// Core does not durably record the creating user (HPOS has no author column), so record the
		// staff actor — and the initiator, when present — on the object ourselves.
		$order->update_meta_data( self::META_KEY_ACTOR_USER_ID, (string) $actor_id );
		// Clear any prior initiator first so a write without an override doesn't keep
		// a stale one — otherwise a plain staff write could look manager-approved.
		$order->delete_meta_data( self::META_KEY_INITIATOR_USER_ID );
		if ( $initiator instanceof WP_User ) {
			$order->update_meta_data( self::META_KEY_INITIATOR_USER_ID, (string) $initiator->ID );
		}
		$order->save_meta_data();

		$is_refund   = $order instanceof WC_Order_Refund;
		$note_target = $is_refund ? wc_get_order( $order->get_parent_id() ) : $order;
		if ( $note_target instanceof WC_Order ) {
			$note_target->add_order_note( $this->build_note( $actor, $initiator, $creating, $is_refund ), 0, false );
		}

		$message = $initiator instanceof WP_User
			? sprintf(
				'POS %1$s %2$d by user %3$s (ID %4$d), initiated by user %5$s (ID %6$d).',
				$is_refund ? 'refund' : 'order',
				$order->get_id(),
				$actor->user_login,
				$actor->ID,
				$initiator->user_login,
				$initiator->ID
			)
			: sprintf(
				'POS %1$s %2$d by user %3$s (ID %4$d).',
				$is_refund ? 'refund' : 'order',
				$order->get_id(),
				$actor->user_login,
				$actor->ID
			);

		wc_get_logger()->info( $message, array( 'source' => self::LOG_SOURCE ) );
	}

	/**
	 * Resolve the initiator user for an order, or null when there is none to record.
	 *
	 * Best-effort: a missing initiator is a no-op (plain write); an initiator equal to the actor is
	 * ignored (no separate initiator); an id that references a non-existent user or one without POS
	 * access is logged and skipped rather than fatal — a bad attribution id must never fail a sale.
	 *
	 * @param WC_Abstract_Order $order    The saved order or refund.
	 * @param int               $actor_id The acting staff member (current user).
	 * @return WP_User|null
	 */
	private function resolve_initiator( WC_Abstract_Order $order, int $actor_id ): ?WP_User {
		$initiator_id = $this->request_context->get_initiator_id();
		if ( null === $initiator_id || $initiator_id === $actor_id ) {
			return null;
		}

		$initiator = get_userdata( $initiator_id );
		if ( ! $initiator instanceof WP_User || ! Capabilities::has_pos_access( $initiator_id ) ) {
			wc_get_logger()->warning(
				sprintf(
					'POS initiator attribution skipped: user %d is missing or lacks POS access at write time (order %d).',
					$initiator_id,
					$order->get_id()
				),
				array( 'source' => self::LOG_SOURCE )
			);
			return null;
		}

		return $initiator;
	}

	/**
	 * Build the localized attribution note naming the actor, and the initiator when present.
	 *
	 * Each variant is its own full sentence so translators get an intact string rather than having
	 * to splice a verb into a template.
	 *
	 * @param WP_User      $actor     The acting staff member (current user).
	 * @param WP_User|null $initiator The override initiator, or null for a plain write.
	 * @param bool         $creating  Whether this was a create (vs update).
	 * @param bool         $is_refund Whether the object being attributed is a refund.
	 * @return string
	 */
	private function build_note( WP_User $actor, ?WP_User $initiator, bool $creating, bool $is_refund ): string {
		$actor_label = sprintf( '%s (%s)', $actor->display_name, $actor->user_login );

		if ( $initiator instanceof WP_User ) {
			$initiator_label = sprintf( '%s (%s)', $initiator->display_name, $initiator->user_login );

			if ( $is_refund ) {
				$template = $creating
					/* translators: 1: actor display name + login, 2: initiator display name + login. */
					? __( 'POS: refunded by %1$s, initiated by %2$s.', 'woocommerce' )
					/* translators: 1: actor display name + login, 2: initiator display name + login. */
					: __( 'POS: refund updated by %1$s, initiated by %2$s.', 'woocommerce' );
			} else {
				$template = $creating
					/* translators: 1: actor display name + login, 2: initiator display name + login. */
					? __( 'POS: created by %1$s, initiated by %2$s.', 'woocommerce' )
					/* translators: 1: actor display name + login, 2: initiator display name + login. */
					: __( 'POS: updated by %1$s, initiated by %2$s.', 'woocommerce' );
			}

			return sprintf( $template, $actor_label, $initiator_label );
		}

		if ( $is_refund ) {
			$template = $creating
				/* translators: %s: actor display name + login. */
				? __( 'POS: refunded by %s.', 'woocommerce' )
				/* translators: %s: actor display name + login. */
				: __( 'POS: refund updated by %s.', 'woocommerce' );
		} else {
			$template = $creating
				/* translators: %s: actor display name + login. */
				? __( 'POS: created by %s.', 'woocommerce' )
				/* translators: %s: actor display name + login. */
				: __( 'POS: updated by %s.', 'woocommerce' );
		}

		return sprintf( $template, $actor_label );
	}
}
