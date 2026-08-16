<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\PushNotifications\Triggers;

use Automattic\WooCommerce\Internal\PushNotifications\Notifications\NewOrderNotification;
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

		wc_get_container()->get( PendingNotificationStore::class )->add(
			new NewOrderNotification( $order_id )
		);
	}
}
