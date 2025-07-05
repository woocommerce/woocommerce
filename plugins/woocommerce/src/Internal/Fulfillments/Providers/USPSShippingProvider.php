<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\Fulfillments\Providers;

/**
 * USPS Shipping Provider implementation.
 *
 * Handles USPS tracking number detection and validation for both domestic and international shipments.
 */
class USPSShippingProvider extends AbstractShippingProvider {
	/**
	 * List of countries/territories where USPS offers domestic service.
	 *
	 * @var array<string>
	 */
	private array $domestic_countries = array(
		'US',
		'PR',
		'GU',
		'AS',
		'VI',
		'MP',
		'FM',
		'MH',
		'PW',
	);

	/**
	 * Gets the unique provider key.
	 *
	 * @return string The provider key 'usps'.
	 */
	public function get_key(): string {
		return 'usps';
	}

	/**
	 * Gets the display name of the provider.
	 *
	 * @return string The provider name 'USPS'.
	 */
	public function get_name(): string {
		return 'USPS';
	}

	/**
	 * Gets the path to the provider's icon.
	 *
	 * @return string URL to the USPS logo image.
	 */
	public function get_icon(): string {
		return esc_url( WC()->plugin_url() ) . '/assets/images/shipping_providers/usps.png';
	}

	/**
	 * Generates the tracking URL for a given tracking number.
	 *
	 * @param string $tracking_number The tracking number to generate URL for.
	 * @return string The complete tracking URL.
	 */
	public function get_tracking_url( string $tracking_number ): string {
		return 'https://tools.usps.com/go/TrackConfirmAction?tLabels=' . rawurlencode( $tracking_number );
	}

	/**
	 * Gets the list of origin countries supported by USPS.
	 *
	 * @return array<string> Array of country codes (only 'US').
	 */
	public function get_shipping_from_countries(): array {
		return array( 'US' ); // USPS only ships from the United States.
	}

	/**
	 * Gets the list of destination countries supported by USPS.
	 *
	 * @return array<string> Array of country codes including domestic and international.
	 */
	public function get_shipping_to_countries(): array {
		return array_merge(
			$this->domestic_countries,
			explode( ' ', 'AD AE AF AG AI AL AM AO AR AT AU AW AZ BA BB BD BE BF BG BH BI BJ BM BN BO BR BS BT BW BY BZ CA CD CF CG CH CI CL CM CN CO CR CU CV CY CZ DE DJ DK DM DO DZ EC EE EG ER ES ET FI FJ FR GA GB GD GE GH GI GM GN GQ GR GT GW GY HK HN HR HT HU ID IE IL IN IQ IR IS IT JM JO JP KE KG KH KI KM KN KP KR KW KZ LA LB LC LK LR LS LT LU LV LY MA MC MD ME MG MK ML MM MN MO MR MT MU MV MW MX MY MZ NA NE NG NI NL NO NP NZ OM PA PE PG PH PK PL PT PY QA RO RS RU RW SA SB SC SD SE SG SI SK SL SM SN SO SR ST SV SY SZ TD TG TH TJ TL TM TN TO TR TT TV TW TZ UA UG UK UY UZ VC VE VN VU WS YE ZA ZM ZW' )
		);
	}

	/**
	 * Attempts to parse and validate a USPS tracking number.
	 *
	 * @param string $tracking_number The tracking number to validate.
	 * @param string $shipping_from Origin country code.
	 * @param string $shipping_to Destination country code.
	 * @return array|null Array with tracking URL and score, or null if invalid.
	 */
	public function try_parse_tracking_number( string $tracking_number, string $shipping_from, string $shipping_to ): ?array {
		if ( empty( $tracking_number ) || ! $this->can_ship_from_to( $shipping_from, $shipping_to ) ) {
			return null; // Invalid input or route.
		}

		$tracking_number = strtoupper( preg_replace( '/\s+/', '', $tracking_number ) );
		$is_domestic     = in_array( $shipping_to, $this->domestic_countries, true );

		// Format patterns with their corresponding base scores.
		$patterns = array(
			// Certified/Registered Mail (highest confidence).
			'/^94(07|08)\d{16,20}$/' => 100,     // Certified/Registered.
			'/^7\d{19}$/'            => 100,     // Certified legacy format.
			'/^92(08|11)\d{16,20}$/' => 100,     // Registered Mail.
			'/^[A-Z]{2}\d{9}US$/'    => 100,     // UPU S10 format (highest confidence international).

			// Priority Mail and standard tracking.
			'/^92(05|20)\d{16,20}$/' => 95,      // Priority Mail.
			'/^9400\d{16,20}$/'      => 95,      // Standard USPS Tracking.

			// Express/Global Mail International.
			'/^E[ACL]\d{9}US$/'      => 90,      // Express Mail International.
			'/^EC\d{9}US$/'          => 90,      // Global Express Guaranteed.

			// Other international services.
			'/^[CLR]\d{8,9}US$/'     => 85,      // Registered/Certified International.

			// Other service patterns.
			'/^91\d{18,20}$/'        => 85,      // GS1-128 format.
			'/^030[67]\d{16,20}$/'   => 85,      // Delivery Confirmation.
		);

		foreach ( $patterns as $pattern => $base_score ) {
			if ( preg_match( $pattern, $tracking_number ) ) {
				return array(
					'url'             => $this->get_tracking_url( $tracking_number ),
					'ambiguity_score' => $base_score,
				);
			}
		}

		// Fallback patterns that consider domestic status.
		if ( $is_domestic ) {
			if ( preg_match( '/^\d{20}$/', $tracking_number ) ) {
				return array(
					'url'             => $this->get_tracking_url( $tracking_number ),
					'ambiguity_score' => 70,  // 20-digit domestic.
				);
			}

			if ( preg_match( '/^9\d{21,34}$/', $tracking_number ) ) {
				return array(
					'url'             => $this->get_tracking_url( $tracking_number ),
					'ambiguity_score' => in_array( 'US', array( $shipping_from, $shipping_to ), true ) ? 90 : 60,  // Fallback 9x domestic.
				);
			}
		}

		return null; // No matching pattern found.
	}
}
