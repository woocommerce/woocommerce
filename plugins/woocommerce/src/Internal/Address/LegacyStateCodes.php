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
		/**
		 * Filters legacy state codes accepted for persisted addresses.
		 *
		 * This compatibility list is not used to populate state selectors. Returning
		 * an empty array disables legacy-code compatibility for the country.
		 *
		 * @since 11.1.0
		 *
		 * @param array<string, string> $states       State names indexed by legacy state code.
		 * @param string                $country_code Country code.
		 */
		return (array) apply_filters( 'woocommerce_legacy_state_codes', self::get_known_states( $country_code ), $country_code );
	}

	/**
	 * Get all legacy state codes known to WooCommerce for a country.
	 *
	 * This unfiltered list is used to detect stale operational configuration even
	 * when an extension disables legacy-address compatibility.
	 *
	 * @param string $country_code Country code.
	 * @return array<string, string> State names indexed by legacy state code.
	 *
	 * @since 11.1.0
	 */
	public static function get_known_states( string $country_code ): array {
		return 'NP' === $country_code ? array(
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
		) : array();
	}

	/**
	 * Add legacy aliases to a non-empty list of current states.
	 *
	 * An empty current-state list means the country accepts free-form state input,
	 * so legacy aliases must not turn it back into a fixed list.
	 *
	 * @param string                $country_code  Country code.
	 * @param array<string, string> $current_states Current state names indexed by state code.
	 * @return array<string, string> State names accepted for persisted addresses.
	 *
	 * @since 11.1.0
	 */
	public static function add_to_current_states( string $country_code, array $current_states ): array {
		if ( empty( $current_states ) ) {
			return $current_states;
		}

		return array_merge( self::get_states( $country_code ), $current_states );
	}

	/**
	 * Resolve a current or legacy state code to its display name.
	 *
	 * @param string                $country_code  Country code.
	 * @param string                $state_code    State code.
	 * @param array<string, string> $current_states Current state names indexed by state code.
	 * @return string State display name, or the original code when it is unknown.
	 *
	 * @since 11.1.0
	 */
	public static function get_state_name( string $country_code, string $state_code, array $current_states ): string {
		$states = self::add_to_current_states( $country_code, $current_states );

		return $states[ $state_code ] ?? $state_code;
	}
}
