<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Address;

/**
 * Provides legacy state codes that remain valid for persisted addresses.
 */
final class LegacyStateCodes {

	/**
	 * Get legacy state codes for a country.
	 *
	 * @param string $country_code Country code.
	 * @return array<string, string> State names indexed by legacy state code.
	 *
	 * @since 11.1.0
	 */
	public static function get_states( string $country_code ): array {
		if ( 'NP' !== $country_code ) {
			return array();
		}

		return array(
			'BAG' => __( 'Bagmati', 'woocommerce' ),
			'BHE' => __( 'Bheri', 'woocommerce' ),
			'DHA' => __( 'Dhaulagiri', 'woocommerce' ),
			'GAN' => __( 'Gandaki', 'woocommerce' ),
			'JAN' => __( 'Janakpur', 'woocommerce' ),
			'KAR' => __( 'Karnali', 'woocommerce' ),
			'KOS' => __( 'Koshi', 'woocommerce' ),
			'LUM' => __( 'Lumbini', 'woocommerce' ),
			'MAH' => __( 'Mahakali', 'woocommerce' ),
			'MEC' => __( 'Mechi', 'woocommerce' ),
			'NAR' => __( 'Narayani', 'woocommerce' ),
			'RAP' => __( 'Rapti', 'woocommerce' ),
			'SAG' => __( 'Sagarmatha', 'woocommerce' ),
			'SET' => __( 'Seti', 'woocommerce' ),
		);
	}
}
