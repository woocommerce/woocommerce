<?php
/**
 * PaymentModeMarker class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Orders;

use Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders;
use Automattic\WooCommerce\Internal\Logging\SafeGlobalFunctionProxy;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use Throwable;
use WC_Order;
use WC_Payment_Gateway;

/**
 * Records on each paid order whether its payment gateway was in test or live mode.
 *
 * @internal Just for internal use.
 *
 * @since 11.4.0
 */
class PaymentModeMarker implements RegisterHooksInterface {

	/**
	 * Order meta key holding the payment mode.
	 */
	public const META_KEY = '_wc_payment_mode';

	/**
	 * Payment mode of an order paid in test mode.
	 */
	public const MODE_TEST = 'test';

	/**
	 * Payment mode of an order paid in live mode.
	 */
	public const MODE_LIVE = 'live';

	/**
	 * Order meta keys where gateways save their own mode, by gateway ID prefix.
	 *
	 * Matching on the prefix ignores a mode left by another gateway during an earlier payment attempt.
	 */
	private const GATEWAY_MODE_META_KEYS = array(
		'woocommerce_payments' => '_wcpay_mode',
		'ppcp-'                => '_ppcp_paypal_payment_mode',
	);

	/**
	 * Values gateways save in their own mode meta, mapped to a payment mode.
	 */
	private const GATEWAY_MODE_VALUES = array(
		'test'       => self::MODE_TEST,
		'sandbox'    => self::MODE_TEST,
		'live'       => self::MODE_LIVE,
		'prod'       => self::MODE_LIVE,
		'production' => self::MODE_LIVE,
	);

	/**
	 * Register hooks and filters.
	 */
	public function register(): void {
		add_action( 'woocommerce_payment_complete', array( $this, 'handle_woocommerce_payment_complete' ) );
	}

	/**
	 * Save the payment mode on an order that was just paid.
	 *
	 * @param mixed $order_id Order ID.
	 *
	 * @internal For exclusive usage within this class, backwards compatibility not guaranteed.
	 */
	public function handle_woocommerce_payment_complete( $order_id ): void {
		try {
			$order = wc_get_order( $order_id );
			if ( ! $order instanceof WC_Order ) {
				return;
			}

			$gateway = wc_get_payment_gateway_by_order( $order );
			if ( ! $gateway instanceof WC_Payment_Gateway ) {
				return;
			}

			$order->update_meta_data( self::META_KEY, $this->get_payment_mode( $order, $gateway ) );
			$order->save_meta_data();
		} catch ( Throwable $e ) {
			// Recording the mode is best effort and must never fail the payment.
			SafeGlobalFunctionProxy::wc_get_logger()->warning(
				'Failed to record the payment mode: ' . $e->getMessage(),
				array(
					'order_id'  => $order_id,
					'source'    => 'payment-mode',
					'exception' => $e,
				)
			);
		}
	}

	/**
	 * Get the payment mode, preferring the mode the gateway saved on the order.
	 *
	 * @param WC_Order           $order   The paid order.
	 * @param WC_Payment_Gateway $gateway The order's payment gateway.
	 * @return string One of the MODE_* constants.
	 */
	private function get_payment_mode( WC_Order $order, WC_Payment_Gateway $gateway ): string {
		// Gateways built on the SkyVerge framework save `_wc_{gateway_id}_environment`.
		$meta_keys = array( '_wc_' . $gateway->id . '_environment' );
		foreach ( self::GATEWAY_MODE_META_KEYS as $gateway_id_prefix => $meta_key ) {
			if ( str_starts_with( $gateway->id, $gateway_id_prefix ) ) {
				$meta_keys[] = $meta_key;
			}
		}
		// Payment Plugins for Stripe shares the `stripe_` ID prefix with the official Stripe plugin, so match its base class.
		if ( is_a( $gateway, 'WC_Payment_Gateway_Stripe' ) ) {
			$meta_keys[] = '_wc_stripe_mode';
		}

		foreach ( $meta_keys as $meta_key ) {
			$value = $order->get_meta( $meta_key );
			if ( is_string( $value ) && isset( self::GATEWAY_MODE_VALUES[ $value ] ) ) {
				return self::GATEWAY_MODE_VALUES[ $value ];
			}
		}

		$provider = wc_get_container()->get( PaymentsProviders::class )->get_payment_gateway_provider_instance( $gateway->id );

		return $provider->is_in_test_mode( $gateway ) ? self::MODE_TEST : self::MODE_LIVE;
	}
}
