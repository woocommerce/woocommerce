<?php
/**
 * PaymentRecovery class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Checkout;

use Automattic\WooCommerce\Enums\OrderStatus;
use Throwable;
use WC_Order;

defined( 'ABSPATH' ) || exit;

/**
 * What both checkouts need when a failure follows the gateway: whether the order it left behind
 * moved past payment, and how to record the failure without sending the shopper back to pay again.
 *
 * The shortcode checkout (WC_Checkout) and the Store API checkout answer such a failure
 * differently, one with a redirect or JSON and the other with a PaymentResult, but they judge the
 * order and record the failure the same way, so that part lives here.
 *
 * @internal For WooCommerce core use only.
 */
final class PaymentRecovery {

	/**
	 * Session key holding the order last handed to a gateway.
	 *
	 * Unlike order_awaiting_payment, which WC_Order::payment_complete() clears before the status
	 * change, this survives a request that dies inside that change, so a repeat submit can still
	 * find the order. Emptying the cart clears it.
	 *
	 * @var string
	 */
	public const ORDER_SENT_TO_GATEWAY = 'order_sent_to_gateway';

	/**
	 * Remembers the order a checkout is about to hand to the gateway.
	 *
	 * @since 11.3.0
	 *
	 * @param int $order_id Order ID.
	 */
	public static function remember_order_sent_to_gateway( int $order_id ): void {
		if ( WC()->session ) {
			WC()->session->set( self::ORDER_SENT_TO_GATEWAY, $order_id );
		}
	}

	/**
	 * The session's order, when it moved past payment and the cart still holds it.
	 *
	 * That combination means a submit repeats one the gateway already took: the gateway moved the
	 * order on but the request died before the cart was emptied.
	 *
	 * @since 11.3.0
	 *
	 * @param int $fallback_order_id Order to use when nothing was remembered, as in a session written before the key existed.
	 * @return WC_Order|null
	 */
	public static function get_session_order_moved_past_payment( int $fallback_order_id = 0 ): ?WC_Order {
		if ( ! WC()->session || ! WC()->cart ) {
			return null;
		}

		$order_id = absint( WC()->session->get( self::ORDER_SENT_TO_GATEWAY ) );
		$order    = wc_get_order( $order_id ? $order_id : $fallback_order_id );

		if ( ! $order instanceof WC_Order || ! $order->has_cart_hash( WC()->cart->get_cart_hash() ) ) {
			return null;
		}

		return self::order_moved_past_payment( $order ) ? $order : null;
	}

	/**
	 * Notes on the order that a repeat submit was answered with its order received page.
	 *
	 * @since 11.3.0
	 *
	 * @param WC_Order $order The order the gateway already moved past payment.
	 */
	public static function record_repeat_submit( WC_Order $order ): void {
		$order->add_order_note( __( 'The checkout form was submitted again for this order after the payment step. No second order was created; the customer was sent to the order received page.', 'woocommerce' ) );
	}

	/**
	 * Reads the order again, so a status the gateway set on its own instance is seen.
	 *
	 * @since 11.3.0
	 *
	 * @param WC_Order $order Order object, possibly stale.
	 * @return WC_Order The stored order, or the given one when it can no longer be read.
	 */
	public static function refresh_order( WC_Order $order ): WC_Order {
		$refreshed_order = wc_get_order( $order->get_id() );

		return $refreshed_order instanceof WC_Order ? $refreshed_order : $order;
	}

	/**
	 * Whether the order has moved beyond the point of awaiting payment.
	 *
	 * An order reaches a gateway awaiting payment or as a draft, so any other status means
	 * something moved it on. This is about the status, not the money: an order parked on-hold
	 * for review counts, because sending the shopper back to place it again would be wrong.
	 *
	 * The statuses a site declares payable count as awaiting payment, so a custom status an
	 * extension parks the order in before the gateway runs is not mistaken for a paid one.
	 * Not needs_payment(): that folds in the order total, and a fully discounted order that
	 * failed would then keep its stock and coupon holds.
	 *
	 * @since 11.3.0
	 *
	 * @param WC_Order $order Order object.
	 * @return bool
	 */
	public static function order_moved_past_payment( WC_Order $order ): bool {
		/**
		 * This filter is documented in woocommerce/includes/class-wc-order.php
		 *
		 * @since 2.7.0
		 */
		$payable_statuses = apply_filters( 'woocommerce_valid_order_statuses_for_payment', array( OrderStatus::PENDING, OrderStatus::FAILED ), $order );

		return ! $order->has_status(
			array_merge(
				(array) $payable_statuses,
				array(
					OrderStatus::CHECKOUT_DRAFT,
					OrderStatus::CANCELLED,
					OrderStatus::REFUNDED,
					OrderStatus::TRASH,
				)
			)
		);
	}

	/**
	 * Records a failure raised after the gateway moved the order on, and clears the cart it left.
	 *
	 * The caller still has to answer the shopper with the order received page: this only writes
	 * down what happened, since neither checkout reports the failure to the shopper any more.
	 *
	 * @since 11.3.0
	 *
	 * @param WC_Order  $order      Order object.
	 * @param Throwable $error      The failure raised after the gateway moved the order on.
	 * @param string    $log_source Log source to file the failure under.
	 */
	public static function record_failure_after_payment( WC_Order $order, Throwable $error, string $log_source ): void {
		/*
		 * The failure goes in the message, not the context: the file handler renders context with
		 * wp_json_encode(), and neither WC_Order nor Throwable exposes public properties, so the
		 * objects alone would write an empty {}. This is now the only log of the error.
		 */
		wc_get_logger()->error(
			sprintf(
				'Checkout for order #%1$d failed after it moved past the payment step: %2$s: %3$s in %4$s:%5$d',
				$order->get_id(),
				get_class( $error ),
				$error->getMessage(),
				$error->getFile(),
				$error->getLine()
			),
			array( 'source' => $log_source )
		);

		$order->add_order_note(
			sprintf(
				/* translators: %s: the error that was raised after the order moved past the payment step. */
				__( 'Checkout could not be completed after the order moved past the payment step. The order keeps the status the gateway set: %s', 'woocommerce' ),
				wp_strip_all_tags( $error->getMessage() )
			)
		);

		/*
		 * The gateway never reached the point where it empties the cart, so do it here: otherwise
		 * the shopper lands on the confirmation with the order still in their cart and can place
		 * it again. Only when the cart still belongs to this order, since pay-for-order and a
		 * second tab both reach here with an unrelated cart. Same guard as
		 * wc_clear_cart_after_payment().
		 */
		if ( WC()->cart && $order->has_cart_hash( WC()->cart->get_cart_hash() ) ) {
			WC()->cart->empty_cart();
		}
	}
}
