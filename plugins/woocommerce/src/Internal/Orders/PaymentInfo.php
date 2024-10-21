<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Orders;

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
		'visa'
	);

	/**
	 * Get info about the card used for payment on an order.
	 *
	 * @param WC_Abstract_Order $order The order in question.
	 *
	 * @return array
	 */
	public static function get_card_info( WC_Abstract_Order $order ): array {
		/**
		 * Filter to allow payment gateways to provide payment card info for an order.
		 *
		 * @param array|null        $info  The card info.
		 * @param WC_Abstract_Order $order The order.
		 */
		$info = apply_filters( 'wc_order_payment_card_info', null, $order );

		if ( is_null( $info ) || ! is_array( $info ) ) {
			$stored = $order->get_meta( '_payment_card_info' );
			$info   = is_array( $stored ) ? $stored : array();
		}

		$defaults = array(
			'payment_method' => $order->get_payment_method(),
			'account_type'   => null,
			'aid'            => null,
			'app_name'       => null,
			'brand'          => null,
			'icon'           => null,
			'last4'          => null,
		);
		$info = array_intersect_key(
			$info,
			wp_parse_args( $info, $defaults )
		);

		$info['icon'] = self::get_card_icon( $info['brand'] );

		return $info;
	}

	/**
	 * Generate a CSS-compatible SVG icon of a card brand.
	 *
	 * @param string $brand The brand of the card.
	 *
	 * @return string
	 */
	private static function get_card_icon( string $brand ): string {
		$brand = strtolower( $brand );

		if ( ! in_array( $brand, self::KNOWN_CARD_BRANDS, true ) ) {
			$brand = 'unknown';
		}

		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode, WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		return base64_encode( file_get_contents( __DIR__ . "/CardIcons/{$brand}.svg" ) );
	}
}
