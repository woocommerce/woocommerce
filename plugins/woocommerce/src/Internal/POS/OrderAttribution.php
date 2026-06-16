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
 * Under the v3 server-side-auth model the acting staff member is the effective `current_user`
 * (swapped in by POSAuthHandler), so WordPress/Woo records them as the actor for free —
 * `refunded_by`, note authorship, and logs all reflect the staff member. There is therefore no
 * separate operator-attribution meta or note to write.
 *
 * The one identity the swap cannot represent is the *initiator* of a manager-authorized action:
 * when a cashier initiates a refund their manager approves, the manager is the swapped actor and
 * the cashier is the initiator. The client sends the initiator as the `X-WC-POS-Initiator-Id`
 * request header (so the whole auth/attribution context rides in headers, not the body); this class
 * validates it (best-effort, log-and-skip), records it on the object as
 * `_woocommerce_pos_initiator_user_id` meta, and writes a single combined note capturing both.
 * Plain writes (actor == initiator, no header) need nothing extra.
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
	 * Record the initiator for a saved POS order/refund, if one is present.
	 *
	 * No-op unless the request is POS-originated and the order carries an initiator distinct from
	 * the acting staff member (the current user). Writes one combined order note. Refund notes
	 * attach to the parent order, since refunds don't expose add_order_note().
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
		if ( $actor_id <= 0 ) {
			return;
		}

		$initiator = $this->resolve_initiator( $order, $actor_id );
		if ( ! $initiator instanceof WP_User ) {
			return;
		}

		$actor = get_userdata( $actor_id );
		if ( ! $actor instanceof WP_User ) {
			return;
		}

		// The wire carried the initiator as a header; record it on the object server-side so it
		// stays queryable (the swap already makes the actor the current user / note author).
		$order->update_meta_data( self::META_KEY_INITIATOR_USER_ID, (string) $initiator->ID );
		$order->save_meta_data();

		$is_refund   = $order instanceof WC_Order_Refund;
		$note_target = $is_refund ? wc_get_order( $order->get_parent_id() ) : $order;
		if ( ! $note_target instanceof WC_Order ) {
			return;
		}

		$note_target->add_order_note( $this->build_initiator_note( $actor, $initiator, $creating, $is_refund ), 0, false );

		wc_get_logger()->info(
			sprintf(
				'POS %1$s %2$d by user %3$s (ID %4$d), initiated by user %5$s (ID %6$d).',
				$is_refund ? 'refund' : 'order',
				$order->get_id(),
				$actor->user_login,
				$actor->ID,
				$initiator->user_login,
				$initiator->ID
			),
			array( 'source' => self::LOG_SOURCE )
		);
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
		if ( $initiator_id <= 0 || $initiator_id === $actor_id ) {
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
	 * Build the localized combined note naming the actor and the initiator.
	 *
	 * Each variant is its own full sentence so translators get an intact string rather than having
	 * to splice a verb into a template.
	 *
	 * @param WP_User $actor     The acting staff member (current user).
	 * @param WP_User $initiator The initiator recorded on the order.
	 * @param bool    $creating  Whether this was a create (vs update).
	 * @param bool    $is_refund Whether the object being attributed is a refund.
	 * @return string
	 */
	private function build_initiator_note( WP_User $actor, WP_User $initiator, bool $creating, bool $is_refund ): string {
		$actor_label     = sprintf( '%s (%s)', $actor->display_name, $actor->user_login );
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
}
