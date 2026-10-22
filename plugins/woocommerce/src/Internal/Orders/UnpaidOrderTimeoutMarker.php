<?php
/**
 * UnpaidOrderTimeoutMarker class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Orders;

use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use WC_Order;

/**
 * Marks orders that the unpaid order timer cancelled.
 *
 * The marker lives in order meta so it survives the deferred email queue, which reloads
 * the order later. It is only managed through status transitions: set by the timer,
 * and cleared when the order enters the cancelled status for any other reason.
 *
 * @internal Just for internal use.
 *
 * @since 11.4.0
 */
class UnpaidOrderTimeoutMarker implements RegisterHooksInterface {

	/**
	 * Order meta key holding the marker.
	 */
	public const META_KEY = '_cancelled_by_unpaid_timeout';

	/**
	 * ID of the order the timer is cancelling right now.
	 *
	 * @var int|null
	 */
	private static ?int $timer_order_id = null;

	/**
	 * Whether the unpaid order timer cancelled the order.
	 *
	 * @param WC_Order $order The order.
	 * @return bool
	 */
	public static function is_marked( WC_Order $order ): bool {
		return 'yes' === $order->get_meta( self::META_KEY );
	}

	/**
	 * Cancel an order on behalf of the unpaid order timer.
	 *
	 * The marker is saved together with the status, before any status hook runs.
	 *
	 * @param WC_Order $order The order to cancel.
	 * @param string   $note  Order note to add.
	 */
	public function cancel_unpaid_order( WC_Order $order, string $note ): void {
		$previous_id          = self::$timer_order_id;
		self::$timer_order_id = $order->get_id();

		try {
			$order->update_meta_data( self::META_KEY, 'yes' );
			$order->update_status( OrderStatus::CANCELLED, $note );
		} finally {
			self::$timer_order_id = $previous_id;
		}
	}

	/**
	 * Register hooks and filters.
	 */
	public function register(): void {
		add_action( 'woocommerce_order_status_cancelled', array( $this, 'clear_marker_on_other_cancellations' ), 10, 2 );
	}

	/**
	 * Remove the marker when an order is cancelled by anything other than the timer.
	 *
	 * @param mixed $order_id Order ID.
	 * @param mixed $order    Order object, if the hook passes it.
	 *
	 * @internal For exclusive usage within this class, backwards compatibility not guaranteed.
	 */
	public function clear_marker_on_other_cancellations( $order_id, $order = null ): void {
		if ( ! $order instanceof WC_Order ) {
			$order = wc_get_order( $order_id );
		}

		if ( ! $order instanceof WC_Order || (int) $order_id === self::$timer_order_id || ! self::is_marked( $order ) ) {
			return;
		}

		$order->delete_meta_data( self::META_KEY );
		$order->save_meta_data();
	}
}
