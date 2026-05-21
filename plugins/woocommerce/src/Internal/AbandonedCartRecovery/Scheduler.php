<?php
/**
 * Scheduler class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\AbandonedCartRecovery;

use Automattic\WooCommerce\Enums\OrderStatus;
use WC_Email_Customer_Abandoned_Cart_Recovery;
use WC_Order;

/**
 * Schedules and cancels the automated abandoned-cart recovery email via Action Scheduler.
 *
 * Listens for new orders in an abandoned-checkout status to enqueue a single
 * `woocommerce_send_abandoned_cart_recovery_notification` action that fires
 * after `WC_Email_Customer_Abandoned_Cart_Recovery::AUTO_SEND_DELAY_SECONDS`.
 * The pending action is cancelled when the order transitions out of the
 * abandoned set or is trashed/deleted, so a customer who completes checkout
 * before the delay elapses never receives the nudge.
 *
 * Per-order idempotency is enforced two ways: a scheduled-at meta key blocks
 * re-scheduling for the same order, and the trigger-time send gate refuses to
 * dispatch when `META_KEY_SENT_AT` is already populated. Together these handle
 * duplicate `woocommerce_new_order` fires, duplicate AS action firings, and
 * the race between a manual send and the still-pending automated send.
 *
 * The container auto-calls `init()` after instantiation; resolution is driven
 * by `WooCommerce::maybe_init_abandoned_cart_recovery()`, hooked on `init`
 * priority 1.
 *
 * @internal Just for internal use.
 *
 * @since 10.9.0
 */
class Scheduler {

	/**
	 * Action Scheduler hook fired when the configured delay elapses.
	 *
	 * Kept in sync with the `add_action` registration in
	 * `WC_Email_Customer_Abandoned_Cart_Recovery::__construct()`, which is the
	 * listener that performs the actual send when this hook fires.
	 */
	public const ACTION_HOOK = 'woocommerce_send_abandoned_cart_recovery_notification';

	/**
	 * Order meta key storing the unix timestamp the email is scheduled for.
	 *
	 * Used for idempotency (block duplicate schedules) and so merchants can
	 * tell from order meta when an automated send is due. Distinct from
	 * `META_KEY_SENT_AT`, which records that a send already happened.
	 */
	public const SCHEDULED_META_KEY = '_abandoned_cart_recovery_scheduled_at';

	/**
	 * Register hooks and filters.
	 *
	 * Auto-called by the WC dependency container after instantiation.
	 *
	 * @internal
	 */
	final public function init(): void {
		add_action( 'woocommerce_new_order', array( $this, 'handle_new_order' ), 10, 1 );
		// Catch every transition out of the abandoned set (processing, completed,
		// cancelled, failed, refunded, custom statuses…) so the pending send is
		// unscheduled regardless of which status the order moves to.
		add_action( 'woocommerce_order_status_changed', array( $this, 'handle_status_changed' ), 10, 3 );
		add_action( 'woocommerce_trash_order', array( $this, 'handle_cancellation' ), 10, 1 );
		add_action( 'woocommerce_before_delete_order', array( $this, 'handle_cancellation' ), 10, 1 );
	}

	/**
	 * Schedule the automated send when an abandoned-state order is created.
	 *
	 * No-op when the order is not in an abandoned status, when the email is
	 * disabled or suppressed, when the merchant has opted out of automated
	 * sends, or when this order already has a pending or completed send.
	 *
	 * @internal
	 *
	 * @param int $order_id The new order ID.
	 */
	public function handle_new_order( int $order_id ): void {
		$order = wc_get_order( $order_id );
		if ( ! $order instanceof WC_Order ) {
			return;
		}

		if ( ! in_array( $order->get_status(), array( OrderStatus::PENDING, OrderStatus::CHECKOUT_DRAFT ), true ) ) {
			return;
		}

		$email = $this->get_email();
		if ( null === $email || ! $email->is_enabled() || ! $email->is_automated() ) {
			return;
		}

		if ( WC_Email_Customer_Abandoned_Cart_Recovery::is_suppressed() ) {
			return;
		}

		if ( '' !== (string) $order->get_meta( self::SCHEDULED_META_KEY ) ) {
			return;
		}

		if ( '' !== (string) $order->get_meta( WC_Email_Customer_Abandoned_Cart_Recovery::META_KEY_SENT_AT ) ) {
			return;
		}

		$when = time() + WC_Email_Customer_Abandoned_Cart_Recovery::AUTO_SEND_DELAY_SECONDS;
		as_schedule_single_action( $when, self::ACTION_HOOK, array( $order_id ) );

		$order->update_meta_data( self::SCHEDULED_META_KEY, (string) $when );
		$order->save();
	}

	/**
	 * Unschedule the pending recovery send whenever the order leaves the
	 * abandoned set. `woocommerce_order_status_changed` fires for every
	 * transition, so a single listener covers processing / completed /
	 * cancelled / failed / refunded / custom statuses in one place.
	 *
	 * Transitions inside the abandoned set (pending → checkout-draft and
	 * vice versa) are no-ops so a customer who switches between classic and
	 * Blocks checkout doesn't lose their pending nudge.
	 *
	 * @internal
	 *
	 * @param int    $order_id   Order ID.
	 * @param string $old_status Previous status (sans `wc-` prefix).
	 * @param string $new_status New status (sans `wc-` prefix).
	 */
	public function handle_status_changed( int $order_id, string $old_status, string $new_status ): void {
		$abandoned     = array( OrderStatus::PENDING, OrderStatus::CHECKOUT_DRAFT );
		$was_abandoned = in_array( $old_status, $abandoned, true );
		$is_abandoned  = in_array( $new_status, $abandoned, true );

		if ( ! $was_abandoned || $is_abandoned ) {
			return;
		}

		$this->handle_cancellation( $order_id );
	}

	/**
	 * Cancel any pending recovery-send action and clear the scheduled-at meta.
	 *
	 * Hooked directly into `woocommerce_trash_order` and
	 * `woocommerce_before_delete_order` for the trash/delete lifecycle events,
	 * and called from `handle_status_changed()` for every transition out of
	 * the abandoned set.
	 *
	 * @internal
	 *
	 * @param int $order_id The affected order ID.
	 */
	public function handle_cancellation( int $order_id ): void {
		// Always attempt to unschedule, even when the order or meta is missing,
		// so an out-of-sync meta value cannot leave a stray scheduled send.
		// `as_unschedule_action()` is a no-op when no matching action exists.
		as_unschedule_action( self::ACTION_HOOK, array( $order_id ) );

		$order = wc_get_order( $order_id );
		if ( $order instanceof WC_Order && '' !== (string) $order->get_meta( self::SCHEDULED_META_KEY ) ) {
			$order->delete_meta_data( self::SCHEDULED_META_KEY );
			$order->save();
		}
	}

	/**
	 * Retrieve the recovery email class instance from the mailer.
	 */
	private function get_email(): ?WC_Email_Customer_Abandoned_Cart_Recovery {
		$mailer = WC()->mailer();
		if ( ! $mailer ) {
			return null;
		}

		$emails = $mailer->get_emails();
		$email  = $emails['WC_Email_Customer_Abandoned_Cart_Recovery'] ?? null;

		return $email instanceof WC_Email_Customer_Abandoned_Cart_Recovery ? $email : null;
	}
}
