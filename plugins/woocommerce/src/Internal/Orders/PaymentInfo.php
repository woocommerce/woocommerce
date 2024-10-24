<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Orders;

use Automattic\Jetpack\Constants;
use Automattic\WooCommerce\Utilities\StringUtil;
use WC_Abstract_Order;

/**
 * Class PaymentInfo.
 */
class PaymentInfo {
	/**
	 * This array must contain all the names of the files in the CardIcons directory (without extension),
	 * except 'unknown'.
	 */
	private const KNOWN_CARD_BRANDS = array(
		'amex',
		'diners',
		'discover',
		'interac',
		'jcb',
		'mastercard',
		'visa',
	);

	/**
	 * Get info about the card used for payment on an order.
	 *
	 * @param WC_Abstract_Order $order The order in question.
	 *
	 * @return array
	 */
	public static function get_card_info( WC_Abstract_Order $order ): array {
		$method = $order->get_payment_method();

		if ( 'woocommerce_payments' === $method ) {
			$info = self::get_wcpay_card_info( $order );
		} else {
			/**
			 * Filter to allow payment gateways to provide payment card info for an order.
			 *
			 * @since 9.5.0
			 *
			 * @param array|null        $info  The card info.
			 * @param WC_Abstract_Order $order The order.
			 */
			$info = apply_filters( 'wc_order_payment_card_info', array(), $order );

			if ( ! is_array( $info ) ) {
				$info = array();
			}
		}

		$defaults = array(
			'payment_method' => $method,
			'brand'          => '',
			'icon'           => '',
			'last4'          => '',
		);
		$info     = wp_parse_args( $info, $defaults );

		if ( empty( $info['icon'] ) ) {
			$info['icon'] = self::get_card_icon( $info['brand'] );
		}

		return $info;
	}

	/**
	 * Generate a CSS-compatible SVG icon of a card brand.
	 *
	 * @param string $brand The brand of the card.
	 *
	 * @return string
	 */
	private static function get_card_icon( ?string $brand ): string {
		$brand = strtolower( (string) $brand );

		if ( ! in_array( $brand, self::KNOWN_CARD_BRANDS, true ) ) {
			$brand = 'unknown';
		}

		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode, WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		return base64_encode( file_get_contents( __DIR__ . "/CardIcons/{$brand}.svg" ) );
	}

	/**
	 * Get info about the card used for payment on an order, when the payment gateway is WooPayments.
	 *
	 * @param WC_Abstract_Order $order The order in question.
	 *
	 * @return array
	 */
	private static function get_wcpay_card_info( WC_Abstract_Order $order ): array {
		if ( 'woocommerce_payments' !== $order->get_payment_method() ) {
			return array();
		}

		// For testing purposes: if WooCommerce Payments development mode is enabled, an order meta item with
		// key '_wcpay_payment_details' will be used if it exists as a replacement for the call to the Stripe
		// API's 'get intent' endpoint. The value must be the JSON encoding of an array simulating the
		// "payment_details" part of the response from the endpoint.
		$stored_payment_details = Constants::get_constant( 'WCPAY_DEV_MODE' ) ? $order->get_meta( '_wcpay_payment_details' ) : '';
		$payment_details        = json_decode( $stored_payment_details, true );

		if ( ! $payment_details ) {
			if ( ! class_exists( \WC_Payments::class ) ) {
				return array();
			}

			$intent_id = $order->get_meta( '_intent_id' );
			if ( ! $intent_id ) {
				return array();
			}

			try {
				$payment_details = \WC_Payments::get_payments_api_client()
					->get_intent( $intent_id )
					->get_charge()
					->get_payment_method_details();
			} catch ( Exception $ex ) {
				$order_id = $order->get_id();
				$message  = $ex->getMessage();
				wc_get_logger()->error(
					sprintf(
						'%s - retrieving info for charge %s for order %s: %s',
						StringUtil::class_name_without_namespace( static::class ),
						$intent_id,
						$order_id,
						$message
					),
					array(
						'source' => 'payment-info',
					)
				);

				return array();
			}
		}

		$card_info = array();

		if ( isset( $payment_details['card_present'] ) ) {
			$card_info['brand'] = $payment_details['card_present']['brand'] ?? '';
			$card_info['last4'] = $payment_details['card_present']['last4'] ?? '';
		} elseif ( isset( $payment_details['card'] ) ) {
			$card_info['brand'] = $payment_details['card']['brand'] ?? '';
			$card_info['last4'] = $payment_details['card']['last4'] ?? '';
		}

		return array_map( 'sanitize_text_field', $card_info );
	}
}
