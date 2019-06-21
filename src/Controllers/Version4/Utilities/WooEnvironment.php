<?php
/**
 * WC information for status report.
 *
 * @package Automattic/WooCommerce/Utilities
 */

namespace Automattic\WooCommerce\RestApi\Controllers\Version4\Utilities;

/**
 * WooEnvironment class.
 */
class WooEnvironment {

	/**
	 * Get some setting values for the site that are useful for debugging
	 * purposes. For full settings access, use the settings api.
	 *
	 * @return array
	 */
	public function get_settings() {
		// Get a list of terms used for product/order taxonomies.
		$term_response = array();
		$terms         = get_terms( 'product_type', array( 'hide_empty' => 0 ) );
		foreach ( $terms as $term ) {
			$term_response[ $term->slug ] = strtolower( $term->name );
		}

		// Get a list of terms used for product visibility.
		$product_visibility_terms = array();
		$terms                    = get_terms( 'product_visibility', array( 'hide_empty' => 0 ) );
		foreach ( $terms as $term ) {
			$product_visibility_terms[ $term->slug ] = strtolower( $term->name );
		}

		// Check if WooCommerce.com account is connected.
		$woo_com_connected = 'no';
		$helper_options    = get_option( 'woocommerce_helper_data', array() );
		if ( array_key_exists( 'auth', $helper_options ) && ! empty( $helper_options['auth'] ) ) {
			$woo_com_connected = 'yes';
		}

		// Return array of useful settings for debugging.
		return array(
			'api_enabled'               => 'yes' === get_option( 'woocommerce_api_enabled' ),
			'force_ssl'                 => 'yes' === get_option( 'woocommerce_force_ssl_checkout' ),
			'currency'                  => get_woocommerce_currency(),
			'currency_symbol'           => get_woocommerce_currency_symbol(),
			'currency_position'         => get_option( 'woocommerce_currency_pos' ),
			'thousand_separator'        => wc_get_price_thousand_separator(),
			'decimal_separator'         => wc_get_price_decimal_separator(),
			'number_of_decimals'        => wc_get_price_decimals(),
			'geolocation_enabled'       => in_array( get_option( 'woocommerce_default_customer_address' ), array( 'geolocation_ajax', 'geolocation' ) ),
			'taxonomies'                => $term_response,
			'product_visibility_terms'  => $product_visibility_terms,
			'woocommerce_com_connected' => $woo_com_connected,
		);
	}
}
