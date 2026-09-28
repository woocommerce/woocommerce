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
				'Checkout for order #%1$d failed after payment was taken: %2$s: %3$s in %4$s:%5$d',
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
				/* translators: %s: the error that was raised after payment was taken. */
				__( 'Checkout could not be completed after payment was taken: %s', 'woocommerce' ),
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
