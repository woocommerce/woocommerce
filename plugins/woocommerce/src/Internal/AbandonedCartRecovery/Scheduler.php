<?php
/**
 * Scheduler class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\AbandonedCartRecovery;

use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Internal\Orders\OrderNoteGroup;
use WC_Email_Customer_Abandoned_Cart_Recovery;
use WC_Order;

/**
 * Schedules and cancels the automated abandoned-cart recovery email via Action Scheduler.
 *
 * Listens for new orders in an abandoned-checkout status to enqueue a single
 * `woocommerce_send_abandoned_cart_recovery_notification` action that fires
 * after `WC_Email_Customer_Abandoned_Cart_Recovery::AUTO_SEND_DELAY_SECONDS`.
 * The pending action is cancelled when the order transitions out of the
 * eligible-status set or is trashed/deleted, so a customer who completes
 * checkout before the delay elapses never receives the nudge. Eligibility
 * comes from the same `woocommerce_abandoned_cart_recovery_eligible_statuses`
 * filter the send/manual paths use.
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
 * @since 11.0.0
 */
class Scheduler {

	/**
	 * Action Scheduler hook fired when the configured delay elapses.
	 *
	 * Registered in `Scheduler::init()` against `handle_scheduled_send()`, which
	 * resolves the email class through the mailer and performs the actual send
	 * when the hook fires.
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
	 * Order-creation origins (`created_via`) eligible for an automated send:
	 * the classic checkout and the block (Store API) checkout.
	 *
	 * @var string[]
	 */
	private const ELIGIBLE_CREATED_VIA = array( 'checkout', 'store-api' );

	/**
	 * Register hooks and filters.
	 *
	 * Auto-called by the WC dependency container after instantiation.
	 *
	 * @internal
	 */
	final public function init(): void {
		add_action( 'woocommerce_new_order', array( $this, 'handle_new_order' ), 10, 2 );
		// Catch every transition out of the eligible set so the pending send
		// is unscheduled regardless of which status the order moves to.
		add_action( 'woocommerce_order_status_changed', array( $this, 'handle_status_changed' ), 10, 3 );
		add_action( 'woocommerce_trash_order', array( $this, 'handle_cancellation' ), 10, 1 );
		add_action( 'woocommerce_before_delete_order', array( $this, 'handle_cancellation' ), 10, 1 );
		add_action( self::ACTION_HOOK, array( $this, 'handle_scheduled_send' ), 10, 1 );
	}

	/**
	 * Schedule the automated send when an order is created in an eligible status.
	 *
	 * No-op when the order is not eligible, when it was not created by a
	 * customer checkout flow, when the email is disabled or suppressed, when
	 * the merchant has opted out of automated sends, or when this order
	 * already has a pending or completed send.
	 *
	 * @internal
	 *
	 * @param int           $order_id The new order ID.
	 * @param WC_Order|null $order    The order object passed by `woocommerce_new_order`;
	 *                                falls back to a lookup only when absent.
	 */
	public function handle_new_order( int $order_id, $order = null ): void {
		if ( ! $order instanceof WC_Order ) {
			$order = wc_get_order( $order_id );
		}
		if ( ! $order instanceof WC_Order ) {
			return;
		}

		// Only nudge customers who abandoned a checkout — pending orders can
		// also originate from admin invoices, the REST API, or renewals.
		if ( ! in_array( $order->get_created_via(), self::ELIGIBLE_CREATED_VIA, true ) ) {
			return;
		}

		if ( ! in_array( $order->get_status(), $this->get_eligible_statuses( $order ), true ) ) {
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
		$order->save_meta_data();
	}

	/**
	 * Unschedule the pending recovery send whenever the order leaves the
	 * eligible-status set. `woocommerce_order_status_changed` fires for every
	 * transition, so a single listener covers all statuses in one place, and
	 * transitions inside a filter-widened eligible set keep the send queued.
	 *
	 * @internal
	 *
	 * @param int    $order_id   Order ID.
	 * @param string $old_status Previous status (sans `wc-` prefix).
	 * @param string $new_status New status (sans `wc-` prefix).
	 */
	public function handle_status_changed( int $order_id, string $old_status, string $new_status ): void {
		$order = wc_get_order( $order_id );
		if ( ! $order instanceof WC_Order ) {
			return;
		}

		$eligible_statuses = $this->get_eligible_statuses( $order );

		$was_eligible = in_array( $old_status, $eligible_statuses, true );
		$is_eligible  = in_array( $new_status, $eligible_statuses, true );

		if ( ! $was_eligible || $is_eligible ) {
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
	 * `pending`.
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
			$order->save_meta_data();
		}
	}

	/**
	 * Dispatch the recovery email when the scheduled AS action fires.
	 *
	 * Resolve the email lazily through the mailer, re-check the automation
	 * opt-in, and delegate the actual send to `trigger()`, which keeps every
	 * send-time gate in one place.
	 *
	 * @internal
	 *
	 * @param int $order_id Order id the AS action was scheduled with.
	 */
	public function handle_scheduled_send( int $order_id ): void {
		$email = $this->get_email();
		if ( null === $email ) {
			return;
		}

		if ( ! $email->is_automated() ) {
			return;
		}

		$dispatched = $email->trigger( $order_id );
		if ( ! $dispatched ) {
			return;
		}

		$order = wc_get_order( $order_id );
		if ( ! $order instanceof WC_Order ) {
			return;
		}

		$order->add_order_note(
			__( 'Abandoned cart recovery email sent automatically.', 'woocommerce' ),
			0,
			false,
			array( 'note_group' => OrderNoteGroup::EMAIL_NOTIFICATION )
		);
	}

	/**
	 * Order statuses eligible for a recovery send, shared with the send-time
	 * gate in `WC_Email_Customer_Abandoned_Cart_Recovery::is_order_eligible_for_recovery()`.
	 *
	 * @param WC_Order|null $order Order being inspected, or null if it could not be loaded.
	 * @return string[]
	 */
	private function get_eligible_statuses( ?WC_Order $order ): array {
		/**
		 * Filter the order statuses that are eligible to receive the abandoned cart recovery email.
		 *
		 * @since 11.0.0
		 *
		 * @param string[]      $eligible_statuses Default: `pending`.
		 * @param WC_Order|null $order             Order being inspected, or null if it could not be loaded.
		 */
		return (array) apply_filters(
			'woocommerce_abandoned_cart_recovery_eligible_statuses',
			array( OrderStatus::PENDING ),
			$order
		);
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
