<?php
namespace Automattic\WooCommerce\Blocks\Utils;

/**
 * Class containing utility methods for dealing with the Cart and Checkout blocks.
 */
class CartCheckoutUtils {

	/**
	 * Checks if the default cart page is using the Cart block.
	 *
	 * @return bool true if the WC cart page is using the Cart block.
	 */
	public static function is_cart_block_default() {
		$cart_page_id = wc_get_page_id( 'cart' );
		return $cart_page_id && has_block( 'woocommerce/cart', $cart_page_id );
	}

	/**
	 * Checks if the default checkout page is using the Checkout block.
	 *
	 * @return bool true if the WC checkout page is using the Checkout block.
	 */
	public static function is_checkout_block_default() {
		$checkout_page_id = wc_get_page_id( 'checkout' );
		return $checkout_page_id && has_block( 'woocommerce/checkout', $checkout_page_id );
	}

	/**
	 * Gets country codes, names, states, and locale information.
	 *
	 * @return array
	 */
	public static function get_country_data() {
		$billing_countries  = WC()->countries->get_allowed_countries();
		$shipping_countries = WC()->countries->get_shipping_countries();
		$country_locales    = wc()->countries->get_country_locale();
		$country_states     = wc()->countries->get_states();
		$all_countries      = self::deep_sort_with_accents( array_unique( array_merge( $billing_countries, $shipping_countries ) ) );

		$country_data = [];

		foreach ( array_keys( $all_countries ) as $country_code ) {
			$country_data[ $country_code ] = [
				'allowBilling'  => isset( $billing_countries[ $country_code ] ),
				'allowShipping' => isset( $shipping_countries[ $country_code ] ),
				'states'        => self::deep_sort_with_accents( $country_states[ $country_code ] ?? [] ),
				'locale'        => $country_locales[ $country_code ] ?? [],
			];
		}

		return $country_data;
	}

	/**
	 * Removes accents from an array of values, sorts by the values, then returns the original array values sorted.
	 *
	 * @param array $array Array of values to sort.
	 * @return array Sorted array.
	 */
	protected static function deep_sort_with_accents( $array ) {
		if ( ! is_array( $array ) || empty( $array ) ) {
			return $array;
		}

		$array_without_accents = array_map(
			function( $value ) {
				return is_array( $value )
					? self::deep_sort_with_accents( $value )
					: remove_accents( wc_strtolower( html_entity_decode( $value ) ) );
			},
			$array
		);

		asort( $array_without_accents );
		return array_replace( $array_without_accents, $array );
	}
}
