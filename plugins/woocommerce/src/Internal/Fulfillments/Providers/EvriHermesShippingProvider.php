<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\Fulfillments\Providers;

/**
 * Evri (Hermes) Shipping Provider class.
 *
 * Provides Evri tracking number validation, supported countries, and tracking URL generation.
 */
class EvriHermesShippingProvider extends AbstractShippingProvider {

	/**
	 * Main Evri/Hermes tracking number patterns.
	 */
	private const MAIN_PATTERNS = array(
		'/^\d{16}$/',                              // 16-digit numeric (official Evri/Hermes format).
		'/^[A-Z]{1,2}\d{14,15}$/',                 // H, E, HM, EV, HH, MH + 14-15 digits (legacy/retail).
		'/^MH\d{16}$/',                            // MH + 16 digits (Hermes Germany legacy)[3].
		'/^(?:[A-Z]\d{2}[A-Z0-9]{13}|\d{16})$/',   // Newer Evri format.
	);

	/**
	 * Calling card pattern.
	 */
	private const CALLING_CARD_PATTERN = '/^\d{8}$/'; // 8-digit calling card number[1][5].

	/**
	 * Legacy and fallback patterns.
	 */
	private const LEGACY_PATTERNS = array(
		'/^\d{13,15}$/',              // 13-15 digit numeric (rare, legacy).
	);

	/**
	 * Get the unique key for this shipping provider.
	 *
	 * @return string Unique key.
	 */
	public function get_key(): string {
		return 'evri-hermes';
	}

	/**
	 * Get the name of this shipping provider.
	 *
	 * @return string Name of the shipping provider.
	 */
	public function get_name(): string {
		return 'Evri (Hermes)';
	}

	/**
	 * Get the icon URL for this shipping provider.
	 *
	 * @return string URL of the shipping provider icon.
	 */
	public function get_icon(): string {
		return esc_url( WC()->plugin_url() ) . '/assets/images/shipping_providers/evri-hermes.png';
	}

	/**
	 * Get the countries this shipping provider can ship from.
	 *
	 * @return array List of country codes.
	 */
	public function get_shipping_from_countries(): array {
		// Evri (formerly Hermes UK) primarily operates from the UK only.
		return array( 'GB' );
	}

	/**
	 * Get the countries this shipping provider can ship to.
	 *
	 * @return array List of country codes.
	 */
	public function get_shipping_to_countries(): array {
		// Evri ships from UK to these exact destinations as listed on their website dropdown.
		// This list is based on the actual options in their destination choice select.
		return array( 'GB', 'AL', 'DZ', 'AS', 'AD', 'AO', 'AI', 'AG', 'AR', 'AM', 'AW', 'AU', 'AT', 'AZ', 'PT', 'BS', 'BH', 'ES', 'BD', 'BB', 'BE', 'BZ', 'BJ', 'BM', 'BT', 'BO', 'BQ', 'BA', 'BA', 'BW', 'BR', 'VG', 'BN', 'BG', 'BF', 'BI', 'KH', 'CM', 'CA', 'ES', 'CV', 'KY', 'CF', 'TD', 'JE', 'CL', 'CN', 'CO', 'KM', 'CG', 'CK', 'CR', 'GR', 'HR', 'CW', 'CY', 'CZ', 'CD', 'DK', 'DJ', 'DM', 'DO', 'TL', 'EC', 'EG', 'SV', 'GQ', 'ER', 'EE', 'SZ', 'ET', 'FK', 'FO', 'FJ', 'FI', 'FR', 'GF', 'PF', 'GA', 'GM', 'GE', 'DE', 'GI', 'GR', 'GL', 'GD', 'GP', 'GU', 'GT', 'GG', 'GN', 'GW', 'GY', 'HT', 'HN', 'HK', 'HU', 'ES', 'IS', 'IN', 'ID', 'IQ', 'IE', 'IL', 'IT', 'JM', 'JP', 'JE', 'JO', 'KZ', 'KE', 'KI', 'KW', 'LA', 'LV', 'LB', 'LS', 'LR', 'LY', 'LI', 'LT', 'LU', 'MO', 'MG', 'ES', 'MW', 'MY', 'MV', 'ML', 'MT', 'MH', 'MQ', 'MR', 'MU', 'YT', 'ES', 'MX', 'FM', 'MD', 'MC', 'MN', 'ME', 'MS', 'MA', 'MZ', 'NA', 'NR', 'NP', 'NL', 'AN', 'NC', 'NZ', 'NI', 'NE', 'MK', 'GB', 'NO', 'OM', 'PK', 'PW', 'PS', 'PA', 'PG', 'PY', 'PE', 'PH', 'PL', 'PT', 'PR', 'QA', 'RE', 'RO', 'RW', 'MP', 'WS', 'SM', 'SA', 'SN', 'RS', 'SC', 'SL', 'SG', 'SK', 'SI', 'SB', 'KR', 'ES', 'LK', 'BL', 'BQ', 'KN', 'LC', 'SX', 'VC', 'SR', 'SE', 'CH', 'TW', 'TJ', 'TZ', 'TH', 'TG', 'TO', 'TT', 'TN', 'TR', 'TM', 'TC', 'TV', 'UG', 'GB', 'UA', 'AE', 'UY', 'US', 'UZ', 'VU', 'VA', 'VN', 'VI', 'WF', 'YE', 'ZM', 'ZW' );
	}

	/**
	 * Get the tracking URL for a given tracking number.
	 *
	 * @param string $tracking_number The tracking number to generate the URL for.
	 * @return string The tracking URL.
	 */
	public function get_tracking_url( string $tracking_number ): string {
		return 'https://www.evri.com/track/' . rawurlencode( $tracking_number );
	}

	/**
	 * Try to parse an Evri tracking number.
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

		// Check if this provider can handle this shipping route.
		if ( ! $this->can_ship_from_to( $shipping_from, $shipping_to ) ) {
			return null;
		}

		$normalized = strtoupper( preg_replace( '/\s+/', '', $tracking_number ) );
		if ( empty( $normalized ) ) {
			return null;
		}

		// 1. Check for main 16-digit and legacy Evri/Hermes formats.
		foreach ( self::MAIN_PATTERNS as $pattern ) {
			if ( preg_match( $pattern, $normalized ) ) {
				$confidence = 90;
				// Boost for UK shipments.
				if ( 'GB' === $shipping_from ) {
					$confidence = min( 98, $confidence + 2 );
				}
				return array(
					'url'             => $this->get_tracking_url( $normalized ),
					'ambiguity_score' => $confidence,
				);
			}
		}

		// 2. Check for 8-digit calling card number.
		if ( preg_match( self::CALLING_CARD_PATTERN, $normalized ) ) {
			return array(
				'url'             => $this->get_tracking_url( $normalized ),
				'ambiguity_score' => 80,
			);
		}

		// 3. Check for legacy/fallback patterns (lower confidence).
		foreach ( self::LEGACY_PATTERNS as $pattern ) {
			if ( preg_match( $pattern, $normalized ) ) {
				$confidence = 75;
				// Boost for UK shipments.
				if ( 'GB' === $shipping_from ) {
					$confidence = min( 95, $confidence + 15 );
				}
				return array(
					'url'             => $this->get_tracking_url( $normalized ),
					'ambiguity_score' => $confidence,
				);
			}
		}

		return null;
	}
}
