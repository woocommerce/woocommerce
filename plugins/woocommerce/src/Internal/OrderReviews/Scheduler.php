<?php
/**
 * Scheduler class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\OrderReviews;

use Automattic\WooCommerce\Enums\OrderStatus;
use WC_Email_Customer_Review_Request;
use WC_Order;

/**
 * Schedules and cancels the delayed "Review request" customer email via Action Scheduler.
 *
 * Listens for order-completed transitions to enqueue a single
 * `woocommerce_send_review_request` action that fires after the delay
 * configured in the email's settings. Cancels the pending action when the
 * order is later refunded, cancelled, trashed or deleted.
 *
 * The container auto-calls `init()` after instantiation, which is where
 * the WordPress hooks are registered. Resolution is driven by the
 * `OrderReviews` wrapper that lists this class as an `init()` argument.
 *
 * @internal Just for internal use.
 *
 * @since 10.8.0
 */
class Scheduler {

	/**
	 * Action Scheduler hook fired when the configured delay elapses. The
	 * `WC_Email_Customer_Review_Request` class listens on the same hook.
	 */
	public const ACTION_HOOK = 'woocommerce_send_review_request';

	/**
	 * Order meta key storing the unix timestamp the email was scheduled for.
	 * Used both for idempotency and so merchants can see (via CRUD) when the
	 * review-request email is due.
	 */
	public const SCHEDULED_META_KEY = '_wc_review_request_scheduled_at';

	/**
	 * Source slug for WC logger entries produced by this class.
	 */
	private const LOG_SOURCE = 'review-request';

	/**
	 * Register hooks and filters.
	 *
	 * Auto-called by the WC dependency container after instantiation.
	 *
	 * @internal
	 */
	final public function init(): void {
		add_action( 'woocommerce_order_status_completed', array( $this, 'handle_woocommerce_order_status_completed' ), 10, 1 );
		// Catch every transition out of `completed` (cancelled, refunded,
		// processing, on-hold, pending, failed, custom statuses…) so the
		// pending email is unscheduled regardless of which status the order
		// moves to.
		add_action( 'woocommerce_order_status_changed', array( $this, 'handle_status_changed' ), 10, 3 );
		add_action( 'woocommerce_trash_order', array( $this, 'handle_cancellation' ), 10, 1 );
		add_action( 'woocommerce_before_delete_order', array( $this, 'handle_cancellation' ), 10, 1 );
	}

	/**
	 * Unschedule the pending review-request email whenever the order leaves
	 * the eligible state. `woocommerce_order_status_changed` fires for every
	 * transition, so a single listener covers cancelled / refunded /
	 * processing / on-hold / pending / failed / custom statuses in one place.
	 *
	 * Eligibility is read from the same `woocommerce_review_order_eligible_statuses`
	 * filter the trigger uses, so a site that widens the filter (e.g. to also
	 * accept `processing`) keeps the email queued through transitions inside
	 * its expanded eligible set.
	 *
	 * @internal
	 *
	 * @param int    $order_id   Order ID.
	 * @param string $old_status Previous status (sans `wc-` prefix).
	 * @param string $new_status New status (sans `wc-` prefix).
	 */
	public function handle_status_changed( int $order_id, string $old_status, string $new_status ): void {
		$order = wc_get_order( $order_id );

		/**
		 * Filter the order statuses that are eligible to receive the review-request email.
		 *
		 * Same hook the email's `trigger()` consults at send time; documented on
		 * `WC_Email_Customer_Review_Request::is_order_eligible_for_send()`.
		 *
		 * @since 10.8.0
		 *
		 * @param string[]      $eligible_statuses Default: `[ 'completed' ]`.
		 * @param WC_Order|null $order             Order being inspected, or null if it could not be loaded.
		 */
		$eligible_statuses = (array) apply_filters(
			'woocommerce_review_order_eligible_statuses',
			array( OrderStatus::COMPLETED ),
			$order instanceof WC_Order ? $order : null
		);

		$was_eligible = in_array( $old_status, $eligible_statuses, true );
		$is_eligible  = in_array( $new_status, $eligible_statuses, true );

		if ( ! $was_eligible || $is_eligible ) {
			return;
		}

		$this->handle_cancellation( $order_id );
	}

	/**
	 * Schedule the review-request email when an order becomes complete.
	 *
	 * @internal
	 *
	 * @param int $order_id The completed order ID.
	 */
	public function handle_woocommerce_order_status_completed( int $order_id ): void {
		$order = wc_get_order( $order_id );

		if ( ! $order instanceof WC_Order ) {
			return;
		}

		$email = $this->get_email();
		if ( null === $email || ! $email->is_enabled() ) {
			$this->log_skip( $order_id, 'email is disabled' );
			return;
		}

		if ( $order->get_meta( self::SCHEDULED_META_KEY ) ) {
			$this->log_skip( $order_id, 'already scheduled' );
			return;
		}

		/**
		 * Filter whether to schedule the review-request email for a given order.
		 *
		 * Return false to opt a specific order out of the automated email while
		 * leaving the email enabled store-wide.
		 *
		 * @param bool     $should_send Whether to schedule the email. Default true.
		 * @param WC_Order $order       The order being processed.
		 *
		 * @since 10.8.0
		 */
		$should_send = (bool) apply_filters( 'woocommerce_should_send_review_request', true, $order );
		if ( ! $should_send ) {
			$this->log_skip( $order_id, 'opt-out filter returned false' );
			return;
		}

		$when = time() + $email->get_delay_seconds();
		as_schedule_single_action( $when, self::ACTION_HOOK, array( $order_id ) );

		$order->update_meta_data( self::SCHEDULED_META_KEY, (string) $when );
		$order->save();
	}

	/**
	 * Cancel any pending review-request action and clear the scheduled-at meta.
	 *
	 * Hooked directly into `woocommerce_trash_order` and
	 * `woocommerce_before_delete_order` for the trash/delete lifecycle events,
	 * and called from `handle_status_changed()` for every status transition
	 * out of an eligible status (cancelled, refunded, processing, on-hold,
	 * pending, failed, custom statuses…).
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
		if ( $order instanceof WC_Order && $order->get_meta( self::SCHEDULED_META_KEY ) ) {
			$order->delete_meta_data( self::SCHEDULED_META_KEY );
			$order->save();
		}
	}

	/**
	 * Retrieve the review-request email class instance from the mailer.
	 */
	private function get_email(): ?WC_Email_Customer_Review_Request {
		$mailer = WC()->mailer();
		if ( ! $mailer ) {
			return null;
		}

		$emails = $mailer->get_emails();
		$email  = $emails['WC_Email_Customer_Review_Request'] ?? null;

		return $email instanceof WC_Email_Customer_Review_Request ? $email : null;
	}

	/**
	 * Log a skipped scheduling attempt with the reason.
	 *
	 * @param int    $order_id The order ID the attempt was for.
	 * @param string $reason   Human-readable reason the attempt was skipped.
	 */
	private function log_skip( int $order_id, string $reason ): void {
		wc_get_logger()->info(
			sprintf(
				/* translators: 1: order ID, 2: skip reason */
				__( 'Skipped scheduling review-request email for order %1$d: %2$s.', 'woocommerce' ),
				$order_id,
				$reason
			),
			array( 'source' => self::LOG_SOURCE )
		);
	}
}
