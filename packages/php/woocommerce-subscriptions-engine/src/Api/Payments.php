<?php
/**
 * Payments - the engine's broker between extensions and payment gateways.
 *
 * Gateways integrate once: they declare the `subscriptions_engine` feature in their
 * `$supports`, charge on the scheduled-payment action and answer nothing else. What a
 * gateway can do for a subscription (renewal payments, payment method changes, ...) it
 * declares with the existing WooCommerce gateway features, which the engine does not
 * interpret. Extensions ask whether a gateway supports the engine and the features an
 * operation needs, request a scheduled payment for an order they built, and answer whether an order
 * needs a reusable payment method. The engine fires and aggregates; it reads no outcome
 * and writes nothing.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine\Api
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Api;

use Throwable;
use WC_Order;
use WC_Payment_Gateway;

defined( 'ABSPATH' ) || exit;

/**
 * Public payments facade.
 *
 * Final and static-only: a stateless entry point, not an extension seam.
 */
final class Payments {

	/**
	 * The feature a gateway adds to its `$supports` to integrate with the engine: it charges
	 * an order off-session on `woocommerce_subscriptions_engine_scheduled_payment_{gateway_id}`
	 * and reports the outcome through the order status. It is the only feature the engine
	 * defines; what the gateway can do beyond that it declares with existing gateway features.
	 */
	public const GATEWAY_FEATURE = 'subscriptions_engine';

	/**
	 * Logger source tag.
	 */
	private const LOG_SOURCE = 'woocommerce-subscriptions-engine';

	/**
	 * Whether a registered gateway supports the engine ({@see self::GATEWAY_FEATURE}) and
	 * every feature in `$features`. The features are the caller's to choose (for example
	 * `subscriptions` before charging a renewal); the engine passes them to the gateway's
	 * `supports()` without interpreting them, so core's `woocommerce_payment_gateway_supports`
	 * filter applies to each. False for an empty or unknown id.
	 *
	 * @param string          $gateway_id Gateway id.
	 * @param string|string[] $features   Gateway feature, or features, the operation needs
	 *                                    besides the engine's. Default none.
	 */
	public static function gateway_supports( string $gateway_id, $features = array() ): bool {
		$gateway = self::get_gateway( $gateway_id );
		if ( null === $gateway ) {
			return false;
		}

		foreach ( array_merge( array( self::GATEWAY_FEATURE ), (array) $features ) as $feature ) {
			if ( ! $gateway->supports( $feature ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Ask the order's gateway to take a scheduled payment for it off-session.
	 *
	 * Fires `woocommerce_subscriptions_engine_scheduled_payment_{gateway_id}` with the order
	 * when the order's gateway supports the engine. Everything else is the caller's: whether
	 * the order should be paid at all (paid, $0 or already in flight) and which further
	 * gateway features the payment needs (such as `subscriptions` for a renewal, checked with
	 * {@see self::gateway_supports()} first). A throwing gateway is logged.
	 *
	 * The return says whether the request reached the gateway cleanly, never whether the
	 * payment succeeded; in every case the caller reads the outcome from the order status.
	 * - True: the action fired and no callback threw.
	 * - False, either of two cases the return does not tell apart:
	 *   - the order's gateway does not support the engine (nothing was fired), or
	 *   - the gateway threw (logged with the order id): the outcome is unknown, since the
	 *     gateway may have charged before throwing.
	 * A caller that sees false and an order still needing payment should not assume the
	 * gateway was never reached.
	 *
	 * @param WC_Order $order Order to pay, carrying the payment method and token.
	 * @return bool True when the action fired and no callback threw; false when the gateway
	 *              does not support the engine or threw (outcome unknown).
	 */
	public static function process_scheduled_payment( WC_Order $order ): bool {
		$gateway_id = $order->get_payment_method();

		if ( ! self::gateway_supports( $gateway_id ) ) {
			return false;
		}

		try {
			/**
			 * Fires to take a scheduled payment for an order off-session. The gateway charges the
			 * order total against the payment method and token stored on the order, then reports
			 * the outcome through the order status: paid (`payment_complete()`), `failed` on a
			 * decline, or `on-hold` while an asynchronous charge awaits confirmation.
			 *
			 * @param WC_Order $order The order to pay.
			 */
			do_action( 'woocommerce_subscriptions_engine_scheduled_payment_' . $gateway_id, $order );
		} catch ( Throwable $e ) {
			wc_get_logger()->error(
				sprintf( 'Payments::process_scheduled_payment(): the %s gateway threw while paying order %d: %s', $gateway_id, $order->get_id(), $e->getMessage() ),
				array(
					'source'   => self::LOG_SOURCE,
					'order_id' => $order->get_id(),
				)
			);

			return false;
		}

		return true;
	}

	/**
	 * Whether an order (or, with null, the current cart) needs a payment method that can be
	 * charged off-session - the question a gateway asks before deciding to save the method.
	 * Extensions answer through the filter below; the engine decides nothing.
	 *
	 * @param WC_Order|null $order The order being paid, or null for the current cart.
	 */
	public static function requires_reusable_payment_method( ?WC_Order $order = null ): bool {
		/**
		 * Filters whether an order (or, with a null order, the current cart) needs a payment
		 * method that can be charged off-session. Extensions return true for what they will
		 * charge later; the answer is cast to bool, so any truthy value counts. Several
		 * extensions answer through the same filter, so combine with the incoming value rather
		 * than replace it: `return $requires || $mine;`. A plain `false` would discard another
		 * extension's true.
		 *
		 * @param bool          $requires Whether a reusable payment method is required. Default false.
		 * @param WC_Order|null $order    The order being paid, or null for the current cart.
		 */
		$requires = apply_filters( 'woocommerce_subscriptions_engine_requires_reusable_payment_method', false, $order );

		return (bool) $requires;
	}

	/**
	 * The registered gateway instance for an id, or null.
	 *
	 * @param string $gateway_id Gateway id.
	 */
	private static function get_gateway( string $gateway_id ): ?WC_Payment_Gateway {
		$gateway = WC()->payment_gateways()->payment_gateways()[ $gateway_id ] ?? null;

		return $gateway instanceof WC_Payment_Gateway ? $gateway : null;
	}
}
