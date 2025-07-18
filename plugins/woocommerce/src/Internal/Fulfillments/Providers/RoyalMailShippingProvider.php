<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\Fulfillments\Providers;

use Automattic\WooCommerce\Internal\Fulfillments\FulfillmentUtils;

/**
 * Royal Mail Shipping Provider class.
 *
 * Provides Royal Mail tracking number validation, supported countries, and tracking URL generation.
 */
class RoyalMailShippingProvider extends AbstractShippingProvider {

	/**
	 * Royal Mail tracking number patterns with enhanced service detection.
	 *
	 * @var array<string, array{patterns: array<int, string>, confidence: int}>
	 */
	private const TRACKING_PATTERNS = array(
		'GB' => array( // United Kingdom.
			'patterns'   => array(
				// UPU S10 international formats.
				'/^[A-Z]{2}\d{9}GB$/',        // International format: XX#########GB.
				'/^[A-Z]{2}\d{7}GB$/',        // Alternative international format: XX#######GB.

				// Domestic tracking formats.
				'/^[A-Z]{1}\d{9}[A-Z]{1}$/',  // Domestic format: X#########X.
				'/^[A-Z]{2}\d{8}[A-Z]{2}$/',  // Standard format: XX########XX.
				'/^[A-Z]{2}\d{6}[A-Z]{2}$/',  // Compact format: XX######XX.

				// Service-specific patterns.
				'/^[A-Z]{4}\d{10}$/',         // Special delivery format: XXXX##########.
				'/^SD\d{8,12}$/',             // Signed For service.
				'/^SF\d{8,12}$/',             // Special Delivery.
				'/^RM\d{8,12}$/',             // Royal Mail standard.

				// Digital tracking formats.
				'/^\d{16}$/',                 // 16-digit returns label or digital.
				'/^\d{14}$/',                 // 14-digit returns label.
				'/^\d{13}$/',                 // 13-digit domestic/international tracking.
				'/^\d{12}$/',                 // 12-digit domestic tracking.
				'/^\d{11}$/',                 // 11-digit domestic tracking.
				'/^\d{10}$/',                 // 10-digit legacy Parcelforce/RM.
				'/^\d{9}$/',                  // 9-digit legacy Parcelforce/RM.

				// Parcelforce (Royal Mail Group).
				'/^PF\d{8,12}$/',             // Parcelforce Express.
				'/^[A-Z]{2}\d{8}PF$/',        // Parcelforce International.
				'/^\d{13}$/',                 // Parcelforce Worldwide numeric.

				// International tracked services.
				'/^IT\d{9}GB$/',              // International Tracked.
				'/^IE\d{9}GB$/',              // International Economy.
				'/^IS\d{9}GB$/',              // International Standard.

				// Business services.
				'/^BF\d{8,12}$/',             // Business services.
				'/^[A-Z]{3}\d{8,12}$/',       // Three-letter business codes.

				// Legacy formats.
				'/^[A-Z]{1}\d{8}[A-Z]{2}$/',  // Legacy format: X########XX.
				'/^[0-9]{9}[A-Z]{3}$/',       // 9 digits + 3 letters.
			),
			'confidence' => 80,
		),
	);

	/**
	 * Get the key of the shipping provider.
	 *
	 * @return string
	 */
	public function get_key(): string {
		return 'royal-mail';
	}

	/**
	 * Get the name of the shipping provider.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'Royal Mail';
	}

	/**
	 * Get the icon of the shipping provider.
	 *
	 * @return string
	 */
	public function get_icon(): string {
		return esc_url( WC()->plugin_url() ) . '/assets/images/shipping_providers/royal-mail.png';
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
	 * Royal Mail ships internationally, so we return a comprehensive list.
	 *
	 * @return array List of country codes.
	 */
	public function get_shipping_to_countries(): array {
		return array( 'AD', 'AE', 'AF', 'AG', 'AI', 'AL', 'AM', 'AO', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AW', 'AX', 'AZ', 'BA', 'BB', 'BD', 'BE', 'BF', 'BG', 'BH', 'BI', 'BJ', 'BL', 'BM', 'BN', 'BO', 'BQ', 'BR', 'BS', 'BT', 'BV', 'BW', 'BY', 'BZ', 'CA', 'CC', 'CD', 'CF', 'CG', 'CH', 'CI', 'CK', 'CL', 'CM', 'CN', 'CO', 'CR', 'CU', 'CV', 'CW', 'CX', 'CY', 'CZ', 'DE', 'DJ', 'DK', 'DM', 'DO', 'DZ', 'EC', 'EE', 'EG', 'EH', 'ER', 'ES', 'ET', 'FI', 'FJ', 'FK', 'FM', 'FO', 'FR', 'GA', 'GB', 'GD', 'GE', 'GF', 'GG', 'GH', 'GI', 'GL', 'GM', 'GN', 'GP', 'GQ', 'GR', 'GS', 'GT', 'GU', 'GW', 'GY', 'HK', 'HM', 'HN', 'HR', 'HT', 'HU', 'ID', 'IE', 'IL', 'IM', 'IN', 'IO', 'IQ', 'IR', 'IS', 'IT', 'JE', 'JM', 'JO', 'JP', 'KE', 'KG', 'KH', 'KI', 'KM', 'KN', 'KP', 'KR', 'KW', 'KY', 'KZ', 'LA', 'LB', 'LC', 'LI', 'LK', 'LR', 'LS', 'LT', 'LU', 'LV', 'LY', 'MA', 'MC', 'MD', 'ME', 'MF', 'MG', 'MH', 'MK', 'ML', 'MM', 'MN', 'MO', 'MP', 'MQ', 'MR', 'MS', 'MT', 'MU', 'MV', 'MW', 'MX', 'MY', 'MZ', 'NA', 'NC', 'NE', 'NF', 'NG', 'NI', 'NL', 'NO', 'NP', 'NR', 'NU', 'NZ', 'OM', 'PA', 'PE', 'PF', 'PG', 'PH', 'PK', 'PL', 'PM', 'PN', 'PR', 'PS', 'PT', 'PW', 'PY', 'QA', 'RE', 'RO', 'RS', 'RU', 'RW', 'SA', 'SB', 'SC', 'SD', 'SE', 'SG', 'SH', 'SI', 'SJ', 'SK', 'SL', 'SM', 'SN', 'SO', 'SR', 'SS', 'ST', 'SV', 'SX', 'SY', 'SZ', 'TC', 'TD', 'TF', 'TG', 'TH', 'TJ', 'TK', 'TL', 'TM', 'TN', 'TO', 'TR', 'TT', 'TV', 'TW', 'TZ', 'UA', 'UG', 'UM', 'US', 'UY', 'UZ', 'VA', 'VC', 'VE', 'VG', 'VI', 'VN', 'VU', 'WF', 'WS', 'YE', 'YT', 'ZA', 'ZM', 'ZW' );
	}

	/**
	 * Get the tracking URL for a given tracking number.
	 *
	 * @param string $tracking_number The tracking number.
	 * @return string The tracking URL.
	 */
	public function get_tracking_url( string $tracking_number ): string {
		return 'https://www.royalmail.com/track-your-item#/tracking-results/' . rawurlencode( $tracking_number );
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
	 * Try to parse a Royal Mail tracking number.
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

		$normalized = strtoupper( preg_replace( '/\s+/', '', $tracking_number ) ); // Normalize input.
		if ( empty( $normalized ) ) {
			return null;
		}

		$shipping_from = strtoupper( $shipping_from );
		$shipping_to   = strtoupper( $shipping_to );

		// Check if shipping from UK.
		if ( 'GB' !== $shipping_from ) {
			return null;
		}

		// Check country-specific patterns with enhanced validation.
		if ( $this->validate_country_pattern( $normalized, $shipping_from ) ) {
			$confidence = self::TRACKING_PATTERNS[ $shipping_from ]['confidence'];

			// Apply UPU S10 validation for international formats.
			if ( preg_match( '/^[A-Z]{2}\d{7,9}GB$/', $normalized ) ) {
				if ( FulfillmentUtils::check_s10_upu_format( $normalized ) ) {
					$confidence = min( 98, $confidence + 8 ); // Strong boost for valid UPU.
				}
			}

			// Apply check digit validation for numeric formats.
			if ( preg_match( '/^\d{11,16}$/', $normalized ) ) {
				if ( FulfillmentUtils::validate_mod10_check_digit( $normalized ) ) {
					$confidence = min( 95, $confidence + 5 ); // Boost for valid check digit.
				}
			}

			// Service-specific confidence boosts.
			if ( preg_match( '/^(SD|SF)\d+/', $normalized ) ) {
				$confidence = min( 96, $confidence + 6 ); // Special Delivery/Signed For.
			} elseif ( preg_match( '/^PF\d+/', $normalized ) ) {
				$confidence = min( 94, $confidence + 4 ); // Parcelforce.
			} elseif ( preg_match( '/^(IT|IE|IS)\d+GB$/', $normalized ) ) {
				$confidence = min( 95, $confidence + 5 ); // International tracked services.
			} elseif ( preg_match( '/^(RM|BF)\d+/', $normalized ) ) {
				$confidence = min( 92, $confidence + 3 ); // Standard Royal Mail/Business.
			}

			// Boost confidence for domestic shipments.
			if ( 'GB' === $shipping_to ) {
				$confidence = min( 95, $confidence + 8 );
			}

			// Boost confidence for common destinations (Europe).
			$european_destinations = array( 'FR', 'DE', 'ES', 'IT', 'NL', 'BE', 'IE', 'AT', 'CH', 'PT', 'DK', 'SE', 'NO' );
			if ( in_array( $shipping_to, $european_destinations, true ) ) {
				$confidence = min( 95, $confidence + 3 );
			}

			// Boost for other common destinations.
			$common_destinations = array( 'US', 'CA', 'AU', 'NZ', 'JP', 'SG', 'HK' );
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
