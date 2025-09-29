<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\Fulfillments\Providers;

use Automattic\WooCommerce\Internal\Fulfillments\FulfillmentUtils;

/**
 * Australia Post Shipping Provider class.
 *
 * Provides Australia Post tracking number validation, supported countries, and tracking URL generation.
 */
class AustraliaPostShippingProvider extends AbstractShippingProvider {

	/**
	 * Australia Post tracking number patterns with enhanced service detection.
	 *
	 * @var array<string, array{patterns: array<int, string>, confidence: int}>
	 */
	private const TRACKING_PATTERNS = array(
		'AU' => array( // Australia.
			'patterns'   => array(
				// International UPU S10 format with validation.
				'/^[A-Z]{2}\d{9}AU$/',       // XX#########AU.
				'/^[A-Z]{2}\d{7}AU$/',       // Alternative international format: XX#######AU.

				// Domestic numeric tracking formats.
				'/^\d{13}$/',                // 13-digit domestic tracking.
				'/^\d{12}$/',                // 12-digit domestic tracking.
				'/^\d{11}$/',                // 11-digit domestic tracking.

				// Standard alphanumeric formats.
				'/^[A-Z]{2}\d{8}[A-Z]{2}$/', // Standard format: XX########XX.
				'/^[A-Z]{1}\d{10}[A-Z]{1}$/', // Domestic format: X##########X.

				// Service-specific patterns.
				'/^[A-Z]{4}\d{8}$/',         // Express Post format: XXXX########.
				'/^EP\d{10}$/',              // Express Post specific.
				'/^ST\d{10}$/',              // StarTrack (freight).
				'/^MB\d{10}$/',              // MyPost Business.
				'/^PO\d{10}$/',              // Post Office Box.

				// MyPost Digital formats.
				'/^MP\d{10,12}$/',           // MyPost tracking.
				'/^DG\d{10,12}$/',           // Digital tracking.

				// Parcel numeric formats.
				'/^7\d{15}$/',               // 16-digit format starting with 7.
				'/^3\d{15}$/',               // 16-digit format starting with 3.
				'/^8\d{15}$/',               // 16-digit format starting with 8.

				// eParcel formats.
				'/^[A-Z]{3}\d{8,12}$/',      // Three-letter prefix.
				'/^33\d?[A-Z]{2}\d{18,20}$/', // StarTrack eParcel.
				'/^AP\d{10,13}$/',           // Australia Post eParcel.

				// Legacy and alternative formats.
				'/^[0-9]{10}[A-Z]{2}$/',     // 10 digits + 2 letters.
				'/^[A-Z]{1}\d{8}[A-Z]{3}$/', // Alternative format.
			),
			'confidence' => 90,
		),
	);

	/**
	 * Get the unique key for this shipping provider.
	 *
	 * @return string Unique key.
	 */
	public function get_key(): string {
		return 'australia-post';
	}

	/**
	 * Get the name of this shipping provider.
	 *
	 * @return string Name of the shipping provider.
	 */
	public function get_name(): string {
		return 'Australia Post';
	}

	/**
	 * Get the icon URL for this shipping provider.
	 *
	 * @return string URL of the shipping provider icon.
	 */
	public function get_icon(): string {
		return esc_url( WC()->plugin_url() ) . '/assets/images/shipping_providers/australia-post.png';
	}

	/**
	 * Get the countries this shipping provider can ship from.
	 *
	 * @return array List of country codes.
	 */
	public function get_shipping_from_countries(): array {
		return array_keys( self::TRACKING_PATTERNS );
	}

	/**
	 * Get the countries this shipping provider can ship to.
	 *
	 * Australia Post ships internationally, so we return a comprehensive list.
	 *
	 * @return array List of country codes.
	 */
	public function get_shipping_to_countries(): array {
		return array( 'AD', 'AE', 'AF', 'AG', 'AI', 'AL', 'AM', 'AO', 'AR', 'AS', 'AT', 'AU', 'AW', 'AZ', 'BA', 'BB', 'BD', 'BE', 'BF', 'BG', 'BH', 'BI', 'BJ', 'BM', 'BN', 'BO', 'BR', 'BS', 'BT', 'BW', 'BY', 'BZ', 'CA', 'CC', 'CD', 'CF', 'CG', 'CH', 'CI', 'CK', 'CL', 'CM', 'CN', 'CO', 'CR', 'CU', 'CV', 'CW', 'CY', 'CZ', 'DE', 'DJ', 'DK', 'DM', 'DO', 'DZ', 'EC', 'EE', 'EG', 'ER', 'ES', 'ET', 'FI', 'FJ', 'FK', 'FM', 'FO', 'FR', 'GA', 'GB', 'GD', 'GE', 'GF', 'GG', 'GH', 'GI', 'GL', 'GM', 'GN', 'GP', 'GQ', 'GR', 'GS', 'GT', 'GU', 'GW', 'GY', 'HK', 'HN', 'HR', 'HT', 'HU', 'ID', 'IE', 'IL', 'IM', 'IN', 'IO', 'IQ', 'IR', 'IS', 'IT', 'JE', 'JM', 'JO', 'JP', 'KE', 'KG', 'KH', 'KI', 'KP', 'KR', 'KW', 'KY', 'KZ', 'LA', 'LB', 'LC', 'LI', 'LK', 'LR', 'LS', 'LT', 'LU', 'LV', 'LY', 'MA', 'MC', 'MD', 'ME', 'MF', 'MG', 'MH', 'MK', 'ML', 'MM', 'MN', 'MO', 'MP', 'MQ', 'MR', 'MS', 'MT', 'MU', 'MV', 'MW', 'MX', 'MY', 'MZ', 'NA', 'NC', 'NE', 'NF', 'NG', 'NI', 'NL', 'NO', 'NP', 'NR', 'NU', 'NZ', 'OM', 'PA', 'PE', 'PF', 'PG', 'PH', 'PK', 'PL', 'PM', 'PN', 'PR', 'PS', 'PT', 'PW', 'PY', 'QA', 'RE', 'RO', 'RS', 'RU', 'RW', 'SA', 'SB', 'SC', 'SD', 'SE', 'SG', 'SH', 'SI', 'SK', 'SL', 'SM', 'SN', 'SO', 'SR', 'SS', 'ST', 'SV', 'SX', 'SY', 'SZ', 'TC', 'TD', 'TF', 'TG', 'TH', 'TJ', 'TK', 'TL', 'TM', 'TN', 'TO', 'TR', 'TT', 'TV', 'TW', 'TZ', 'UA', 'UG', 'UM', 'US', 'UY', 'UZ', 'VA', 'VC', 'VE', 'VG', 'VI', 'VN', 'VU', 'WF', 'WS', 'YE', 'YT', 'ZA', 'ZM', 'ZW' );
	}

	/**
	 * Get the tracking URL for a given tracking number.
	 *
	 * @param string $tracking_number The tracking number to generate the URL for.
	 * @return string The tracking URL.
	 */
	public function get_tracking_url( string $tracking_number ): string {
		return 'https://auspost.com.au/mypost/track/details/' . rawurlencode( $tracking_number );
	}

	/**
	 * Validate tracking number against country-specific patterns.
	 *
	 * @param string $tracking_number The tracking number to validate.
	 * @param string $country_code The country code for the shipment.
	 * @return bool True if valid, false otherwise.
	 */
	private function validate_country_pattern( string $tracking_number, string $country_code ): bool {
		if ( ! isset( self::TRACKING_PATTERNS[ $country_code ] ) ) {
			return false;
		}

		foreach ( self::TRACKING_PATTERNS[ $country_code ]['patterns'] as $pattern ) {
			if ( preg_match( $pattern, $tracking_number ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Try to parse an Australia Post tracking number.
	 *
	 * @param string $tracking_number The tracking number to parse.
	 * @param string $shipping_from The country code of the shipping origin.
	 * @param string $shipping_to The country code of the shipping destination.
	 * @return array|null An array with 'url' and 'ambiguity_score' if valid, null otherwise.
	 */
	public function try_parse_tracking_number(
		string $tracking_number,
		string $shipping_from,
		string $shipping_to
	): ?array {
		if ( empty( $tracking_number ) || empty( $shipping_from ) || empty( $shipping_to ) ) {
			return null;
		}

		$normalized = strtoupper( preg_replace( '/\s+/', '', $tracking_number ) );
		if ( empty( $normalized ) ) {
			return null;
		}

		$shipping_from = strtoupper( $shipping_from );
		$shipping_to   = strtoupper( $shipping_to );

		// Australia Post ships only from Australia.
		if ( 'AU' !== $shipping_from ) {
			return null;
		}

		if ( $this->validate_country_pattern( $normalized, $shipping_from ) ) {
			$confidence = self::TRACKING_PATTERNS[ $shipping_from ]['confidence'];

			// Check digit validation for numeric formats.
			if ( preg_match( '/^\d{11,13}$/', $normalized ) ) {
				if ( FulfillmentUtils::validate_mod10_check_digit( $normalized ) ) {
					$confidence = min( 98, $confidence + 8 );
				}
			}

			// UPU S10 validation for international formats.
			if ( preg_match( '/^[A-Z]{2}\d{7,9}AU$/', $normalized ) ) {
				if ( FulfillmentUtils::check_s10_upu_format( $normalized ) ) {
					$confidence = min( 98, $confidence + 8 );
				}
			}

			// Service-specific confidence boosts.
			if ( preg_match( '/^(EP|ST|MB)\d+/', $normalized ) ) {
				$confidence = min( 95, $confidence + 5 );
			}

			// Boost confidence for domestic shipments.
			if ( 'AU' === $shipping_to ) {
				$confidence = min( 98, $confidence + 5 );
			}

			// Boost confidence for Asia-Pacific destinations.
			$apac_destinations = array( 'NZ', 'SG', 'HK', 'JP', 'KR', 'TH', 'MY', 'ID', 'PH', 'VN', 'IN' );
			if ( in_array( $shipping_to, $apac_destinations, true ) ) {
				$confidence = min( 95, $confidence + 3 );
			}

			// Boost confidence for common destinations.
			$common_destinations = array( 'US', 'GB', 'CA', 'DE', 'FR' );
			if ( in_array( $shipping_to, $common_destinations, true ) ) {
				$confidence = min( 93, $confidence + 2 );
			}

			return array(
				'url'             => $this->get_tracking_url( $normalized ),
				'ambiguity_score' => $confidence,
			);
		}

		return null;
	}
}
