<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\PushNotifications\Triggers;

use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Internal\PushNotifications\Notifications\NewOrderNotification;
use Automattic\WooCommerce\Internal\PushNotifications\PushNotifications;
use Automattic\WooCommerce\Internal\PushNotifications\Services\NotificationStepLogger;
use Automattic\WooCommerce\Internal\PushNotifications\Services\PendingNotificationStore;
use WC_Order;

defined( 'ABSPATH' ) || exit;

/**
 * Listens for new and status-changed orders and feeds notifications into
 * the PendingNotificationStore.
 *
 * @since 10.7.0
 */
class NewOrderNotificationTrigger {
	/**
	 * Order statuses that should trigger a notification.
	 */
	const NOTIFIABLE_STATUSES = array(
		/**
		 * Source: WooCommerce plugin.
		 */
		'processing',
		'on-hold',
		'completed',
		/**
		 * Source: WooCommerce Pre-Orders plugin.
		 */
		'pre-order',
		/**
		 *  Source: WPCOM - "commonly used custom pre-order status".
		 */
		'pre-ordered',
		/**
		 *  Source: WooCommerce Deposits plugin.
		 */
		'partial-payment',
	);

	/**
	 * Registers WordPress hooks for order events.
	 *
	 * @return void
	 *
	 * @since 10.7.0
	 */
	public function register(): void {
		add_action( 'woocommerce_new_order', array( $this, 'on_new_order' ), 10, 2 );
		add_action( 'woocommerce_order_status_changed', array( $this, 'on_order_status_changed' ), 10, 4 );
	}

	/**
	 * Handles the woocommerce_new_order hook.
	 *
	 * @param int      $order_id The order ID.
	 * @param WC_Order $order    The order object.
	 * @return void
	 *
	 * @since 10.7.0
	 */
	public function on_new_order( int $order_id, WC_Order $order ): void {
		if ( ! in_array( $order->get_status(), self::NOTIFIABLE_STATUSES, true ) ) {
			return;
		}

		wc_get_container()->get( PendingNotificationStore::class )->add(
			new NewOrderNotification( $order_id )
		);
	}

	// phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
	/**
	 * Handles the woocommerce_order_status_changed hook.
	 *
	 * @param int      $order_id        The order ID.
	 * @param string   $previous_status The previous order status.
	 * @param string   $next_status     The new order status.
	 * @param WC_Order $order           The order object.
	 * @return void
	 *
	 * @since 10.7.0
	 */
	public function on_order_status_changed(
		int $order_id,
		string $previous_status,
		string $next_status,
		WC_Order $order
	): void {
		// phpcs:enable Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		if (
			in_array( $previous_status, self::NOTIFIABLE_STATUSES, true )
			|| ! in_array( $next_status, self::NOTIFIABLE_STATUSES, true )
		) {
			return;
		}

		$notification = new NewOrderNotification( $order_id );

		if ( $this->predates_push_notifications( $previous_status, $order ) ) {
			wc_get_container()->get( NotificationStepLogger::class )->log_notification_step( $notification, 'triggered', 'predates_push_notifications' );
			return;
		}

		wc_get_container()->get( PendingNotificationStore::class )->add( $notification );
	}

	/**
	 * Whether the order was created before push notifications were enabled.
	 *
	 * Such an order was never marked as notified, so moving it out of a custom
	 * status into a notifiable one would otherwise announce an old order as new.
	 * Moves out of the unpaid statuses are a payment arriving, so they still notify.
	 *
	 * @param string   $previous_status The previous order status.
	 * @param WC_Order $order           The order object.
	 * @return bool
	 */
	private function predates_push_notifications( string $previous_status, WC_Order $order ): bool {
		if ( in_array( $previous_status, array( OrderStatus::PENDING, OrderStatus::FAILED, OrderStatus::CHECKOUT_DRAFT ), true ) ) {
			return false;
		}

		$enabled_at   = wc_get_container()->get( PushNotifications::class )->get_enabled_at();
		$date_created = $order->get_date_created();

		return null !== $enabled_at && null !== $date_created && $date_created->getTimestamp() < $enabled_at;
	}
}
