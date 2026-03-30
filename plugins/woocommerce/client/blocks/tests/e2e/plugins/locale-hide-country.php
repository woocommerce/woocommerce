<?php
declare(strict_types=1);
/**
 * Plugin Name: WooCommerce Blocks Test Locale Hide Country
 * Description: Uses woocommerce_get_country_locale to hide the country field and other address fields.
 * Plugin URI: https://github.com/woocommerce/woocommerce
 * Author: WooCommerce
 * @package woocommerce-blocks-test-locale-hide-country
 */

add_filter(
	'woocommerce_get_country_locale',
	function ( $locales ) {
		foreach ( $locales as $country => $locale ) {
			$locales[ $country ]['country']['hidden']   = true;
			$locales[ $country ]['country']['required']  = false;
			$locales[ $country ]['city']['hidden']       = true;
			$locales[ $country ]['city']['required']     = false;
			$locales[ $country ]['postcode']['hidden']   = true;
			$locales[ $country ]['postcode']['required'] = false;
			$locales[ $country ]['address_1']['hidden']   = true;
			$locales[ $country ]['address_1']['required'] = false;
			$locales[ $country ]['address_2']['hidden']   = true;
			$locales[ $country ]['address_2']['required'] = false;
			$locales[ $country ]['state']['hidden']       = true;
			$locales[ $country ]['state']['required']     = false;
			$locales[ $country ]['phone']['hidden']       = true;
			$locales[ $country ]['phone']['required']     = false;
		}
		return $locales;
	}
);
