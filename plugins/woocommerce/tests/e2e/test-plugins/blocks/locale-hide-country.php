<?php
/**
 * Plugin Name: WooCommerce Blocks Test Locale Hide Country
 * Description: Uses woocommerce_get_country_locale to hide the country field and other address fields.
 * Plugin URI: https://github.com/woocommerce/woocommerce
 * Author: WooCommerce
 *
 * @package woocommerce-blocks-test-locale-hide-country
 */

declare(strict_types=1);

add_filter(
	'woocommerce_get_country_locale',
	function ( $locales ) {
		$hidden_fields = array( 'country', 'city', 'postcode', 'address_1', 'address_2', 'state', 'phone' );
		foreach ( $locales as $country => $locale ) {
			foreach ( $hidden_fields as $field ) {
				$locales[ $country ][ $field ]['hidden']   = true;
				$locales[ $country ][ $field ]['required'] = false;
			}
		}
		return $locales;
	}
);
