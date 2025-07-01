<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\Fulfillments\Providers;

/**
 * Canada Post Shipping Provider class.
 *
 * Provides Canada Post tracking number validation, supported countries, and tracking URL generation.
 */
class CanadaPostShippingProvider extends AbstractShippingProvider {

	/**
	 * Canada Post tracking number patterns.
	 *
	 * @var array<string, array{patterns: array<int, string>, confidence: int}>
	 */
	private const TRACKING_PATTERNS = array(
		'CA' => array( // Canada.
			'patterns'   => array(
				'/^[A-Z]{2}\d{9}CA$/', // Standard format: XX#########CA.
				'/^\d{16}$/', // 16-digit domestic tracking.
				'/^\d{12}$/', // 12-digit domestic tracking.
				'/^[A-Z]{2}\d{7}[A-Z]{2}$/', // International format: XX#######XX.
				'/^[A-Z]{1}\d{9}[A-Z]{1}$/', // Some domestic formats: X#########X.
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
		return 'canada-post';
	}

	/**
	 * Get the name of this shipping provider.
	 *
	 * @return string Name of the shipping provider.
	 */
	public function get_name(): string {
		return 'Canada Post';
	}

	/**
	 * Get the icon URL for this shipping provider.
	 *
	 * @return string URL of the shipping provider icon.
	 */
	public function get_icon(): string {
		return esc_url( WC()->plugin_url() ) . '/assets/images/shipping_providers/canada-post.png';
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
	 * Canada Post ships internationally, so we return a comprehensive list.
	 *
	 * @return array List of country codes.
	 */
	public function get_shipping_to_countries(): array {
		return array( 'AD', 'AE', 'AF', 'AG', 'AI', 'AL', 'AM', 'AO', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AW', 'AX', 'AZ', 'BA', 'BB', 'BD', 'BE', 'BF', 'BG', 'BH', 'BI', 'BJ', 'BL', 'BM', 'BN', 'BO', 'BQ', 'BR', 'BS', 'BT', 'BV', 'BW', 'BY', 'BZ', 'CA', 'CC', 'CD', 'CF', 'CG', 'CH', 'CI', 'CK', 'CL', 'CM', 'CN', 'CO', 'CR', 'CU', 'CV', 'CW', 'CX', 'CY', 'CZ', 'DE', 'DJ', 'DK', 'DM', 'DO', 'DZ', 'EC', 'EE', 'EG', 'EH', 'ER', 'ES', 'ET', 'FI', 'FJ', 'FK', 'FM', 'FO', 'FR', 'GA', 'GB', 'GD', 'GE', 'GF', 'GG', 'GH', 'GI', 'GL', 'GM', 'GN', 'GP', 'GQ', 'GR', 'GS', 'GT', 'GU', 'GW', 'GY', 'HK', 'HM', 'HN', 'HR', 'HT', 'HU', 'ID', 'IE', 'IL', 'IM', 'IN', 'IO', 'IQ', 'IR', 'IS', 'IT', 'JE', 'JM', 'JO', 'JP', 'KE', 'KG', 'KH', 'KI', 'KM', 'KN', 'KP', 'KR', 'KW', 'KY', 'KZ', 'LA', 'LB', 'LC', 'LI', 'LK', 'LR', 'LS', 'LT', 'LU', 'LV', 'LY', 'MA', 'MC', 'MD', 'ME', 'MF', 'MG', 'MH', 'MK', 'ML', 'MM', 'MN', 'MO', 'MP', 'MQ', 'MR', 'MS', 'MT', 'MU', 'MV', 'MW', 'MX', 'MY', 'MZ', 'NA', 'NC', 'NE', 'NF', 'NG', 'NI', 'NL', 'NO', 'NP', 'NR', 'NU', 'NZ', 'OM', 'PA', 'PE', 'PF', 'PG', 'PH', 'PK', 'PL', 'PM', 'PN', 'PR', 'PS', 'PT', 'PW', 'PY', 'QA', 'RE', 'RO', 'RS', 'RU', 'RW', 'SA', 'SB', 'SC', 'SD', 'SE', 'SG', 'SH', 'SI', 'SJ', 'SK', 'SL', 'SM', 'SN', 'SO', 'SR', 'SS', 'ST', 'SV', 'SX', 'SY', 'SZ', 'TC', 'TD', 'TF', 'TG', 'TH', 'TJ', 'TK', 'TL', 'TM', 'TN', 'TO', 'TR', 'TT', 'TV', 'TW', 'TZ', 'UA', 'UG', 'UM', 'US', 'UY', 'UZ', 'VA', 'VC', 'VE', 'VG', 'VI', 'VN', 'VU', 'WF', 'WS', 'YE', 'YT', 'ZA', 'ZM', 'ZW' );
	}

	/**
	 * Get the tracking URL for a given tracking number.
	 *
	 * @param string $tracking_number The tracking number to generate the URL for.
	 * @return string The tracking URL.
	 */
	public function get_tracking_url( string $tracking_number ): string {
		return 'https://www.canadapost-postescanada.ca/track-reperage/en#/search?searchFor=' . rawurlencode( $tracking_number );
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
	 * Try to parse a Canada Post tracking number.
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

		// Check if shipping from Canada.
		if ( 'CA' !== $shipping_from ) {
			return null;
		}

		// Check country-specific patterns.
		if ( $this->validate_country_pattern( $normalized, $shipping_from ) ) {
			$confidence = self::TRACKING_PATTERNS[ $shipping_from ]['confidence'];

			// Boost confidence for domestic shipments.
			if ( 'CA' === $shipping_to ) {
				$confidence = min( 95, $confidence + 5 );
			}

			return array(
				'url'             => $this->get_tracking_url( $normalized ),
				'ambiguity_score' => $confidence,
			);
		}

		return null;
	}
}
