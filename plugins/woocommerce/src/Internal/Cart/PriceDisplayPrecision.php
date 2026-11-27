<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Cart;

defined( 'ABSPATH' ) || exit;

/**
 * Works out how many decimals to show a product's unit price with.
 *
 * A merchant can enter a price with more decimals than the currency has, such as £0.207 for a bulk item. When the
 * shopper sees that price as entered, it is shown with its own decimals, so 200 × £0.207 = £41.40 is easy to follow.
 * When the price is converted for tax (shown with VAT but entered without, or adjusted to the customer's VAT rate), the
 * converted price is shown with the currency's decimals as usual.
 *
 * @since 11.5.0
 */
final class PriceDisplayPrecision {

	/**
	 * Get the number of decimals to show a unit price with.
	 *
	 * @param \WC_Product $product       Product.
	 * @param bool        $including_tax Whether the price is shown including tax.
	 * @param mixed       $price         Price as entered, e.g. a regular or sale price. Defaults to the product's active price.
	 * @return int At least the store's price decimals.
	 */
	public static function get_decimals( \WC_Product $product, bool $including_tax, $price = null ): int {
		$decimals = wc_get_price_decimals();

		if ( null === $price ) {
			if ( self::is_price_changed( $product ) ) {
				return $decimals;
			}
			$price = $product->get_price();
		}

		$entered = self::count_decimals( $price );

		if ( $entered <= $decimals || ! self::shows_price_as_entered( $product, $including_tax ) ) {
			return $decimals;
		}

		return $entered;
	}

	/**
	 * Whether the product's active price differs from the saved price, for example because a discount extension changed it
	 * in the cart. A changed price wasn't entered by the merchant, so it's shown with the store's decimals.
	 *
	 * @param \WC_Product $product Product.
	 * @return bool
	 */
	public static function is_price_changed( \WC_Product $product ): bool {
		$saved = $product->get_data()['price'] ?? '';

		if ( '' === $saved ) {
			return false;
		}

		return abs( (float) $product->get_price() - (float) $saved ) > 1e-9;
	}

	/**
	 * Whether the shopper sees the price exactly as the merchant entered it, with no tax conversion.
	 *
	 * @param \WC_Product $product       Product.
	 * @param bool        $including_tax Whether the price is shown including tax.
	 * @return bool
	 */
	public static function shows_price_as_entered( \WC_Product $product, bool $including_tax ): bool {
		if ( ! wc_tax_enabled() || ! $product->is_taxable() ) {
			return true;
		}

		if ( wc_prices_include_tax() !== $including_tax ) {
			return false;
		}

		if ( ! wc_prices_include_tax() ) {
			return true;
		}

		if ( WC()->customer && WC()->customer->get_is_vat_exempt() ) {
			return false;
		}

		/**
		 * Filters if taxes should be removed from locations outside the store base location.
		 *
		 * @since 2.4.7
		 *
		 * @param bool $adjust_non_base_location_prices True by default.
		 */
		if ( ! apply_filters( 'woocommerce_adjust_non_base_location_prices', true ) ) {
			return true;
		}

		return \WC_Tax::get_rates( $product->get_tax_class() ) === \WC_Tax::get_base_tax_rates( $product->get_tax_class( 'unfiltered' ) );
	}

	/**
	 * Count the decimals in a price, ignoring trailing zeros and float noise beyond the rounding precision.
	 *
	 * @param mixed $price Price.
	 * @return int
	 */
	private static function count_decimals( $price ): int {
		if ( '' === $price || null === $price || ! is_numeric( $price ) ) {
			return 0;
		}

		// PHP's round(), not NumberUtil::round(), which first rounds to WC_ROUNDING_PRECISION and so drops digits from
		// stores with more decimals than that allows.
		$precision = wc_get_rounding_precision();
		$digits    = rtrim( number_format( round( (float) $price, $precision ), $precision, '.', '' ), '0' );
		$dot       = strpos( $digits, '.' );

		return false === $dot ? 0 : strlen( $digits ) - $dot - 1;
	}
}
