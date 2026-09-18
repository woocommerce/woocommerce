<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\StockNotifications\Frontend;

use Automattic\WooCommerce\Internal\StockNotifications\Notification;
use Automattic\WooCommerce\Internal\StockNotifications\NotificationQuery;
use Automattic\WooCommerce\Internal\StockNotifications\Utilities\EmailNormalizer;

/**
 * Links a customer's unclaimed guest sign-ups to their account.
 *
 * Guest sign-ups are stored with `user_id = 0`, so they never show up under My Account.
 * This mirrors what `wc_update_new_customer_past_orders()` does for guest orders: the rows
 * are claimed only once the customer has proven they own the mailbox.
 *
 * @since 11.3.0
 *
 * @internal
 */
final class NotificationClaimService {

	/**
	 * Init the service.
	 *
	 * @internal
	 */
	final public function init(): void { // phpcs:ignore Generic.CodeAnalysis.UnnecessaryFinalModifier.Found -- Required by WooCommerce injection method rules.
		// @phpstan-ignore-next-line return.void -- The claimed count is intentionally discarded here.
		add_action( 'woocommerce_customer_email_verified', array( $this, 'claim_guest_notifications' ) );
	}

	/**
	 * Assign every unclaimed guest sign-up matching the account email to that account.
	 *
	 * The parameter is untyped and cast here because any third-party code can fire
	 * `woocommerce_customer_email_verified` with a differently-typed argument.
	 *
	 * @internal
	 *
	 * @param mixed $customer_id The ID of the customer whose email was verified.
	 * @return int Number of sign-ups claimed.
	 */
	public function claim_guest_notifications( $customer_id ): int {
		$customer = get_user_by( 'id', absint( $customer_id ) );

		if ( ! $customer ) {
			return 0;
		}

		$user_email = EmailNormalizer::normalize( (string) $customer->user_email );

		if ( empty( $user_email ) ) {
			return 0;
		}

		$notifications = NotificationQuery::get_notifications(
			array(
				'user_email' => $user_email,
				'return'     => 'objects',
			)
		);

		$claimed = 0;

		foreach ( $notifications as $notification ) {
			if ( ! $notification instanceof Notification || 0 !== $notification->get_user_id() ) {
				continue;
			}

			$notification->set_user_id( $customer->ID );

			if ( is_wp_error( $notification->save() ) ) {
				continue;
			}

			/**
			 * Fires once a guest stock notification sign-up has been linked to a customer account.
			 *
			 * @since 11.3.0
			 *
			 * @param int      $notification_id The ID of the claimed notification.
			 * @param \WP_User $customer        The customer the notification was linked to.
			 */
			do_action( 'woocommerce_stock_notification_claimed_by_customer', $notification->get_id(), $customer );

			++$claimed;
		}

		return $claimed;
	}
}
