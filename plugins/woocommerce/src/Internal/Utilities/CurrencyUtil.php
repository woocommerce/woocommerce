<?php
/**
 * Currency precision utilities.
 *
 * @package Automattic\WooCommerce\Internal\Utilities
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Utilities;

/**
 * Resolves price precision without reducing the configured store precision.
 */
final class CurrencyUtil {
	/**
	 * Currency decimals indexed by currency code.
	 *
	 * @var array<string, int>|null
	 */
	private static $currency_decimals;

	/**
	 * Get price decimals for an order currency.
	 *
	 * @since 11.4.0
	 * @param mixed $currency Currency code.
	 * @return int
	 */
	public static function get_price_decimals( $currency ): int {
		$store_decimals = wc_get_price_decimals();
		if ( ! is_string( $currency ) || '' === $currency || get_woocommerce_currency() === $currency ) {
			return $store_decimals;
		}

		if ( null === self::$currency_decimals ) {
			self::$currency_decimals = array();
			$locales                 = include WC_ABSPATH . 'i18n/locale-info.php';
			foreach ( $locales as $locale ) {
				$code                             = $locale['currency_code'];
				self::$currency_decimals[ $code ] = max( self::$currency_decimals[ $code ] ?? 0, (int) $locale['num_decimals'] );
			}
		}

		return max( $store_decimals, self::$currency_decimals[ $currency ] ?? $store_decimals );
	}
}
