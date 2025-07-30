<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\Fulfillments\Providers;

use Automattic\WooCommerce\Internal\Fulfillments\FulfillmentUtils;

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
	private array $domestic_countries = array( 'US', 'PR', 'GU', 'AS', 'VI', 'MP', 'FM', 'MH', 'PW' );

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
	 * Checks if USPS can ship from and to the specified countries.
	 *
	 * @param string $shipping_from Origin country code.
	 * @param string $shipping_to Destination country code.
	 * @return bool
	 */
	public function can_ship_from_to( string $shipping_from, string $shipping_to ): bool {
		return in_array( $shipping_from, $this->get_shipping_from_countries(), true )
			&& in_array( $shipping_to, $this->get_shipping_to_countries(), true );
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
			return null;
		}

		// Remove spaces and uppercase for consistency.
		$tracking_number = strtoupper( preg_replace( '/\s+/', '', $tracking_number ) );
		$is_domestic     = in_array( $shipping_to, $this->domestic_countries, true );

		// USPS tracking number patterns (ordered by confidence).
		$patterns = array(
			// 22-digit, 20-digit, and 26-34 digit numeric (domestic and third-party)
			'/^(94|93|92|95|96|94|94|94|94)\d{18,22}$/' => function () use ( $tracking_number ) {
				// Most common domestic, check digit validation.
				return FulfillmentUtils::validate_mod10_check_digit( $tracking_number ) ? 100 : 95;
			},

			// S10/UPU international (2 letters, 9 digits, 2 letters, e.g., EC123456789US).
			'/^[A-Z]{2}\d{9}[A-Z]{2}$/'                 => function () use ( $tracking_number ) {
				return FulfillmentUtils::check_s10_upu_format( $tracking_number ) ? 98 : 90;
			},

			// Global Express Guaranteed (10 or 11 digits, starts with 82).
			'/^82\d{8,9}$/'                             => 95,

			// 26-34 digit numeric (Parcel Pool, third-party, starts with 420)
			'/^420\d{23,31}$/'                          => 90,

			// 20-22 digit numeric (fallback, domestic)
			'/^\d{20,22}$/'                             => 80,

			// 9x... (fallback, 22-34 digits, numeric)
			'/^9\d{21,33}$/'                            => 75,

			// Legacy/Express/other.
			'/^91\d{18,20}$/'                           => function () use ( $tracking_number ) {
				return FulfillmentUtils::validate_mod10_check_digit( $tracking_number ) ? 90 : 80;
			},
			'/^030[67]\d{16,20}$/'                      => function () use ( $tracking_number ) {
				return FulfillmentUtils::validate_mod10_check_digit( $tracking_number ) ? 88 : 80;
			},
		);

		foreach ( $patterns as $pattern => $score ) {
			if ( preg_match( $pattern, $tracking_number ) ) {
				$ambiguity_score = is_callable( $score ) ? $score() : $score;
				return array(
					'url'             => $this->get_tracking_url( $tracking_number ),
					'ambiguity_score' => $ambiguity_score,
				);
			}
		}

		// Fallback: Accept any 13-char S10/UPU format ending with "US".
		if ( preg_match( '/^[A-Z]{2}\d{9}US$/', $tracking_number ) ) {
			return array(
				'url'             => $this->get_tracking_url( $tracking_number ),
				'ambiguity_score' => 80,
			);
		}

		// Fallback: Accept any 20-34 digit numeric string (very low confidence).
		if ( preg_match( '/^\d{20,34}$/', $tracking_number ) ) {
			return array(
				'url'             => $this->get_tracking_url( $tracking_number ),
				'ambiguity_score' => 60,
			);
		}

		return null; // No matching pattern found.
	}
}
