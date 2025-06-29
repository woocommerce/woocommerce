<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\Fulfillments\Providers;

/**
 * DPD Shipping Provider class.
 *
 * Provides DPD tracking number validation, supported countries, and tracking URL generation.
 */
class DPDShippingProvider extends AbstractShippingProvider {

	/**
	 * DPD tracking number patterns by country.
	 *
	 * @var array<string, array{patterns: array<int, string>, confidence: int}>
	 */
	private const TRACKING_PATTERNS = array(
		'DE' => array(
			'patterns'   => array(
				'/^\d{14}$/',
				'/^\d{12}$/',
				'/^\d{10,14}[A-Z]?$/',
			),
			'confidence' => 95,
		),
		'GB' => array(
			'patterns'   => array(
				'/^\d{12}$/',
				'/^\d{14}$/',
				'/^[A-Z]{12}$/',
				'/^[A-Z]{14}$/',
				'/^[A-Z]{2}\d{10,12}$/',
				'/^\d{13}[A-Z]$/',
			),
			'confidence' => 95,
		),
		'FR' => array(
			'patterns'   => array(
				'/^\d{12,14}$/',
				'/^\d{14}$/',
				'/^\d{12}$/',
			),
			'confidence' => 95,
		),
		'NL' => array(
			'patterns'   => array(
				'/^\d{14}$/',
				'/^\d{12}$/',
				'/^\d{10,14}[A-Z]?$/',
			),
			'confidence' => 95,
		),
		'BE' => array(
			'patterns'   => array(
				'/^\d{14}$/',
				'/^\d{12}$/',
				'/^\d{10,14}$/',
			),
			'confidence' => 90,
		),
		'PL' => array(
			'patterns'   => array(
				'/^\d{14}$/',
				'/^\d{12}$/',
				'/^[A-Z]{2}\d{10,12}$/',
			),
			'confidence' => 90,
		),
		'IE' => array(
			'patterns'   => array(
				'/^\d{12,14}$/',
				'/^[A-Z]{12,14}$/',
				'/^[A-Z]{2}\d{10,12}$/',
				'/^\d{13}[A-Z]$/',
			),
			'confidence' => 85,
		),
		'AT' => array(
			'patterns'   => array(
				'/^\d{14}$/',
				'/^\d{12}$/',
				'/^\d{10,14}[A-Z]?$/',
			),
			'confidence' => 80,
		),
		'CH' => array(
			'patterns'   => array(
				'/^\d{12,14}$/',
				'/^[A-Z]{12,14}$/',
				'/^[A-Z]{2}\d{10,12}$/',
				'/^\d{13}[A-Z]$/',
			),
			'confidence' => 75,
		),
		'ES' => array(
			'patterns'   => array(
				'/^\d{12,14}$/',
				'/^[A-Z]{2}\d{10,12}$/',
			),
			'confidence' => 75,
		),
		'IT' => array(
			'patterns'   => array(
				'/^\d{12,14}$/',
				'/^[A-Z]{2}\d{10,12}$/',
			),
			'confidence' => 75,
		),
		'PT' => array(
			'patterns'   => array(
				'/^\d{12,14}$/',
				'/^[A-Z]{2}\d{10,12}$/',
			),
			'confidence' => 70,
		),
		'CZ' => array(
			'patterns'   => array(
				'/^\d{12,14}$/',
				'/^[A-Z]{2}\d{10,12}$/',
			),
			'confidence' => 70,
		),
		'SK' => array(
			'patterns'   => array(
				'/^\d{12,14}$/',
				'/^[A-Z]{2}\d{10,12}$/',
			),
			'confidence' => 70,
		),
		'HU' => array(
			'patterns'   => array(
				'/^\d{12,14}$/',
				'/^[A-Z]{2}\d{10,12}$/',
			),
			'confidence' => 70,
		),
		'SE' => array(
			'patterns'   => array(
				'/^\d{12,14}$/',
				'/^[A-Z]{2}\d{10,12}$/',
			),
			'confidence' => 65,
		),
		'DK' => array(
			'patterns'   => array(
				'/^\d{12,14}$/',
				'/^[A-Z]{2}\d{10,12}$/',
			),
			'confidence' => 65,
		),
		'NO' => array(
			'patterns'   => array(
				'/^\d{12,14}$/',
				'/^[A-Z]{2}\d{10,12}$/',
			),
			'confidence' => 65,
		),
	);

	/**
	 * Extended tracking patterns for special cases.
	 *
	 * @var array<int, string>
	 */
	private const EXTENDED_PATTERNS = array(
		'/^\d{4}\s\d{3}\s\d{4}\s\d{4}\s\d{4}\s\d{2}\s\d{3}\s\d{3}\s[A-Z]$/', // 28 with spaces + letter
		'/^\d{28}$/', // 28 digits, no spaces
	);

	/**
	 * Get the key of the shipping provider.
	 *
	 * @return string The provider key.
	 */
	public function get_key(): string {
		return 'dpd';
	}

	/**
	 * Get the name of the shipping provider.
	 *
	 * @return string The provider name.
	 */
	public function get_name(): string {
		return 'DPD';
	}

	/**
	 * Get the icon URL for the shipping provider.
	 *
	 * @return string The URL to the provider icon.
	 */
	public function get_icon(): string {
		return esc_url( WC()->plugin_url() ) . '/assets/images/shipping_providers/dpd.png';
	}

	/**
	 * Get the countries from which this provider can ship.
	 *
	 * @return array<int, string> List of ISO country codes.
	 */
	public function get_shipping_from_countries(): array {
		return array_keys( self::TRACKING_PATTERNS );
	}

	/**
	 * Get the countries to which this provider can ship.
	 *
	 * @return array<int, string> List of ISO country codes.
	 */
	public function get_shipping_to_countries(): array {
		return $this->get_shipping_from_countries();
	}

	/**
	 * Get the tracking URL for a given tracking number.
	 *
	 * @param string $tracking_number The tracking number.
	 * @return string The tracking URL.
	 */
	public function get_tracking_url( string $tracking_number ): string {
		return 'https://www.dpd.com/tracking/' . $tracking_number;
	}

	/**
	 * Validate if a tracking number matches DPD patterns for a specific country.
	 *
	 * @param string $tracking_number The normalized tracking number.
	 * @param string $country_code    The country code to check patterns for.
	 * @return bool True if the tracking number matches the country's DPD patterns.
	 */
	private function validate_tracking_number_for_country( string $tracking_number, string $country_code ): bool {
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
	 * Check if tracking number matches extended DPD patterns.
	 *
	 * @param string $tracking_number The tracking number (with or without spaces).
	 * @return bool True if matches extended patterns.
	 */
	private function validate_extended_tracking_number( string $tracking_number ): bool {
		foreach ( self::EXTENDED_PATTERNS as $pattern ) {
			if ( preg_match( $pattern, $tracking_number ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Find all possible countries that match the tracking number pattern.
	 *
	 * @param string $normalized_tracking_number The normalized tracking number.
	 * @return array<int, array{country: string, confidence: int}> Array of matching countries with confidence.
	 */
	private function find_matching_countries( string $normalized_tracking_number ): array {
		$matches = array();
		foreach ( self::TRACKING_PATTERNS as $country => $config ) {
			if ( $this->validate_tracking_number_for_country( $normalized_tracking_number, $country ) ) {
				$matches[] = array(
					'country'    => $country,
					'confidence' => $config['confidence'],
				);
			}
		}
		usort(
			$matches,
			function ( $a, $b ) {
				return $b['confidence'] - $a['confidence'];
			}
		);
		return $matches;
	}

	/**
	 * Try to parse the tracking number with additional parameters.
	 *
	 * @param string $tracking_number The tracking number.
	 * @param string $shipping_from   The origin country code.
	 * @param string $shipping_to     The destination country code.
	 *
	 * @return array{url: string, ambiguity_score: int}|null The tracking URL with ambiguity score, or null if parsing fails.
	 */
	public function try_parse_tracking_number( string $tracking_number, string $shipping_from, string $shipping_to ): ?array {
		if ( empty( $tracking_number ) || empty( $shipping_from ) || empty( $shipping_to ) ) {
			return null;
		}

		// Normalize tracking number - remove spaces and convert to uppercase.
		$normalized = strtoupper( preg_replace( '/\s+/', '', $tracking_number ) );
		if ( empty( $normalized ) ) {
			return null;
		}

		// Check extended patterns first (highest confidence for these special formats).
		if ( $this->validate_extended_tracking_number( $tracking_number ) ||
			$this->validate_extended_tracking_number( $normalized ) ) {
			return array(
				'url'             => $this->get_tracking_url( $normalized ),
				'ambiguity_score' => 95,
			);
		}

		$shipping_from = strtoupper( $shipping_from );
		$shipping_to   = strtoupper( $shipping_to );

		// First, check if the tracking number matches the origin country pattern.
		if ( $this->validate_tracking_number_for_country( $normalized, $shipping_from ) ) {
			$confidence = self::TRACKING_PATTERNS[ $shipping_from ]['confidence'] ?? 50;
			// Boost confidence if destination is also a DPD country.
			if ( in_array( $shipping_to, $this->get_shipping_to_countries(), true ) ) {
				$confidence = min( 98, $confidence + 5 );
			}
			return array(
				'url'             => $this->get_tracking_url( $normalized ),
				'ambiguity_score' => $confidence,
			);
		}

		// If origin country doesn't match, check all possible countries.
		$matching_countries = $this->find_matching_countries( $normalized );
		if ( empty( $matching_countries ) ) {
			return null; // No valid DPD pattern found.
		}

		// Calculate ambiguity score based on matches.
		$base_confidence = $matching_countries[0]['confidence'];
		$match_count     = count( $matching_countries );

		// Reduce confidence based on ambiguity (more matches = less certain).
		if ( $match_count > 1 ) {
			$ambiguity_penalty = min( 30, ( $match_count - 1 ) * 10 );
			$base_confidence  -= $ambiguity_penalty;
		}

		// Further reduce if origin country is not in our supported list.
		if ( ! in_array( $shipping_from, $this->get_shipping_from_countries(), true ) ) {
			$base_confidence -= 15;
		}

		// Ensure minimum confidence threshold.
		if ( $base_confidence < 40 ) {
			return null;
		}

		return array(
			'url'             => $this->get_tracking_url( $normalized ),
			'ambiguity_score' => max( 40, min( 95, $base_confidence ) ),
		);
	}
}
