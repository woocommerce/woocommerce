<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\Fulfillments\Providers;

/**
 * Evri (Hermes) Shipping Provider class.
 *
 * Provides Evri tracking number validation, supported countries, and tracking URL generation.
 */
class EvriHermesShippingProvider extends AbstractShippingProvider {

	/**
	 * Common tracking number patterns.
	 */
	private const COMMON_PATTERNS = array(
		'/^\d{16}$/',                 // 16-digit numeric.
		'/^\d{15}$/',                 // 15-digit numeric.
	);

	/**
	 * Evri tracking number patterns by country.
	 *
	 * @var array<string, array{patterns: array<int, string>, confidence: int}>
	 */
	private const TRACKING_PATTERNS = array(
		'GB' => array( // United Kingdom - Primary market.
			'patterns'   => array(
				'/^[A-Z]{2}\d{8}[A-Z]{2}$/', // HH12345678GB format.
				'/^H\d{8}$/',                // H + 8 digits.
				'/^E\d{8}$/',                // E + 8 digits.
			),
			'confidence' => 90,
		),
		'IE' => array( // Ireland - Direct Evri coverage.
			'patterns'   => array(
				'/^[A-Z]{2}\d{8}IE$/',       // Similar to GB format with IE suffix.
			),
			'confidence' => 85,
		),
	);

	/**
	 * Countries with standard patterns and their confidence levels.
	 *
	 * @var array<string, int>
	 */
	private const STANDARD_PATTERN_COUNTRIES = array(
		'FR' => 80, // France - International delivery.
		'DE' => 80, // Germany - International delivery.
		'IT' => 80, // Italy - International delivery.
		'ES' => 80, // Spain - International delivery.
		'NL' => 80, // Netherlands - International delivery.
		'BE' => 80, // Belgium - International delivery.
		'AT' => 80, // Austria - International delivery.
		'PL' => 80, // Poland - International delivery.
		'GR' => 80, // Greece - International delivery.
		'PT' => 80, // Portugal - International delivery.
		'CH' => 80, // Switzerland - International delivery.
		'CZ' => 75, // Czech Republic - International delivery.
		'HU' => 75, // Hungary - International delivery.
		'RO' => 75, // Romania - International delivery.
		'NO' => 75, // Norway - International delivery.
		'SE' => 75, // Sweden - International delivery.
		'DK' => 75, // Denmark - International delivery.
		'FI' => 75, // Finland - International delivery.
		'EE' => 70, // Estonia - International delivery.
		'CY' => 70, // Cyprus - International delivery.
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
		return array_merge( array_keys( self::TRACKING_PATTERNS ), array_keys( self::STANDARD_PATTERN_COUNTRIES ) );
	}

	/**
	 * Get the countries this shipping provider can ship to.
	 *
	 * Evri delivers to 200+ countries worldwide, but tracking patterns are primarily
	 * for European destinations where they have established networks.
	 *
	 * @return array List of country codes.
	 */
	public function get_shipping_to_countries(): array {
		return $this->get_shipping_from_countries();
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
	 * Validate tracking number against country-specific patterns.
	 *
	 * @param string $tracking_number The tracking number to validate.
	 * @param string $country_code The country code for the shipment.
	 * @return bool True if valid, false otherwise.
	 */
	private function validate_country_pattern( string $tracking_number, string $country_code ): bool {
		// Check common patterns for countries with standard patterns.
		if ( isset( self::STANDARD_PATTERN_COUNTRIES[ $country_code ] ) ) {
			foreach ( self::COMMON_PATTERNS as $pattern ) {
				if ( preg_match( $pattern, $tracking_number ) ) {
					return true;
				}
			}
			// Also check country-specific format.
			$country_pattern = '/^[A-Z]{2}\d{8}' . $country_code . '$/';
			if ( preg_match( $country_pattern, $tracking_number ) ) {
				return true;
			}
		}

		// Check specific patterns for countries with unique formats.
		if ( isset( self::TRACKING_PATTERNS[ $country_code ] ) ) {
			foreach ( self::TRACKING_PATTERNS[ $country_code ]['patterns'] as $pattern ) {
				if ( preg_match( $pattern, $tracking_number ) ) {
					return true;
				}
			}
			// Also check common patterns for these countries.
			foreach ( self::COMMON_PATTERNS as $pattern ) {
				if ( preg_match( $pattern, $tracking_number ) ) {
					return true;
				}
			}
		}

		return false;
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

		$normalized = strtoupper( preg_replace( '/\s+/', '', $tracking_number ) );
		if ( empty( $normalized ) ) {
			return null;
		}

		$shipping_from = strtoupper( $shipping_from );
		$shipping_to   = strtoupper( $shipping_to );

		// Check country-specific patterns.
		if ( $this->validate_country_pattern( $normalized, $shipping_from ) ) {
			// Get confidence from either specific patterns or standard patterns.
			if ( isset( self::TRACKING_PATTERNS[ $shipping_from ] ) ) {
				$confidence = self::TRACKING_PATTERNS[ $shipping_from ]['confidence'];
			} elseif ( isset( self::STANDARD_PATTERN_COUNTRIES[ $shipping_from ] ) ) {
				$confidence = self::STANDARD_PATTERN_COUNTRIES[ $shipping_from ];
			} else {
				return null;
			}

			// Boost confidence for intra-Evri shipments.
			if ( in_array( $shipping_to, $this->get_shipping_to_countries(), true ) ) {
				$confidence = min( 98, $confidence + 5 );
			}

			// Extra boost for UK shipments (primary market).
			if ( 'GB' === $shipping_from ) {
				$confidence = min( 98, $confidence + 3 );
			}

			return array(
				'url'             => $this->get_tracking_url( $normalized ),
				'ambiguity_score' => $confidence,
			);
		}

		return null;
	}
}
