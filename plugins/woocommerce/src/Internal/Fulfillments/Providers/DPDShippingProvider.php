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
		'DE' => array( // Germany.
			'patterns'   => array(
				'/^\d{14}$/',
				'/^\d{12}$/',
			),
			'confidence' => 75, // Reduced: 12/14 digits are very generic.
		),
		'GB' => array( // United Kingdom.
			'patterns'   => array(
				'/^\d{14}$/',
				'/^[A-Z]{2}\d{9}GB$/',
			),
			'confidence' => 85, // Mixed: generic digits + specific GB suffix.
		),
		'FR' => array( // France.
			'patterns'   => array(
				'/^\d{14}$/',
				'/^\d{12}$/',
			),
			'confidence' => 75, // Reduced: very generic patterns.
		),
		'NL' => array( // Netherlands.
			'patterns'   => array(
				'/^\d{14}$/',
				'/^\d{12}$/',
			),
			'confidence' => 75, // Reduced: very generic patterns.
		),
		'BE' => array( // Belgium.
			'patterns'   => array(
				'/^\d{14}$/',
				'/^\d{12}$/',
			),
			'confidence' => 75, // Reduced: very generic patterns.
		),
		'PL' => array( // Poland.
			'patterns'   => array(
				'/^\d{14}$/',
				'/^[A-Z]{2}\d{10}$/',
			),
			'confidence' => 90,
		),
		'IE' => array( // Ireland.
			'patterns'   => array(
				'/^\d{14}$/',
				'/^[A-Z]{2}\d{9}IE$/',
			),
			'confidence' => 85,
		),
		'AT' => array( // Austria.
			'patterns'   => array(
				'/^\d{14}$/',
				'/^\d{12}$/',
			),
			'confidence' => 75, // Reduced: generic patterns.
		),
		'CH' => array( // Switzerland.
			'patterns'   => array(
				'/^\d{14}$/',
				'/^[A-Z]{2}\d{9}CH$/',
			),
			'confidence' => 85,
		),
		'ES' => array( // Spain.
			'patterns'   => array(
				'/^\d{14}$/',
				'/^[A-Z]{2}\d{10}$/',
			),
			'confidence' => 85,
		),
		'IT' => array( // Italy.
			'patterns'   => array(
				'/^\d{14}$/',
				'/^[A-Z]{2}\d{10}$/',
			),
			'confidence' => 85,
		),
		'LU' => array( // Luxembourg.
			'patterns'   => array(
				'/^\d{14}$/',
				'/^\d{12}$/',
			),
			'confidence' => 75, // Reduced: generic patterns.
		),
		'CZ' => array( // Czech Republic.
			'patterns'   => array(
				'/^\d{14}$/',
				'/^[A-Z]{2}\d{10}$/',
			),
			'confidence' => 90,
		),
		'SK' => array( // Slovakia.
			'patterns'   => array(
				'/^\d{14}$/',
				'/^[A-Z]{2}\d{10}$/',
			),
			'confidence' => 90,
		),
		'HU' => array( // Hungary.
			'patterns'   => array(
				'/^\d{14}$/',
				'/^[A-Z]{2}\d{10}$/',
			),
			'confidence' => 90,
		),
		'SI' => array( // Slovenia.
			'patterns'   => array(
				'/^\d{14}$/',
				'/^[A-Z]{2}\d{10}$/',
			),
			'confidence' => 80,
		),
		'HR' => array( // Croatia.
			'patterns'   => array(
				'/^\d{14}$/',
				'/^[A-Z]{2}\d{10}$/',
			),
			'confidence' => 80,
		),
		'RO' => array( // Romania.
			'patterns'   => array(
				'/^\d{14}$/',
				'/^[A-Z]{2}\d{10}$/',
			),
			'confidence' => 75,
		),
		'BG' => array( // Bulgaria.
			'patterns'   => array(
				'/^\d{14}$/',
				'/^[A-Z]{2}\d{10}$/',
			),
			'confidence' => 70,
		),
		'LT' => array( // Lithuania.
			'patterns'   => array(
				'/^\d{14}$/',
				'/^\d{12}$/',
			),
			'confidence' => 70, // Reduced: generic patterns, limited DPD presence.
		),
		'LV' => array( // Latvia.
			'patterns'   => array(
				'/^\d{14}$/',
				'/^\d{12}$/',
			),
			'confidence' => 70, // Reduced: generic patterns, limited DPD presence.
		),
		'EE' => array( // Estonia.
			'patterns'   => array(
				'/^\d{14}$/',
				'/^\d{12}$/',
			),
			'confidence' => 70, // Reduced: generic patterns, limited DPD presence.
		),
		'FI' => array( // Finland.
			'patterns'   => array(
				'/^\d{14}$/',
				'/^\d{12}$/',
			),
			'confidence' => 65, // Reduced: partnership-based, not direct DPD.
		),
		'DK' => array( // Denmark.
			'patterns'   => array(
				'/^\d{14}$/',
				'/^\d{12}$/',
			),
			'confidence' => 65, // Reduced: partnership-based, not direct DPD.
		),
		'SE' => array( // Sweden.
			'patterns'   => array(
				'/^\d{14}$/',
				'/^\d{12}$/',
			),
			'confidence' => 65, // Reduced: partnership-based, not direct DPD.
		),
		'NO' => array( // Norway.
			'patterns'   => array(
				'/^\d{14}$/',
				'/^\d{12}$/',
			),
			'confidence' => 60, // Reduced: limited DPD presence.
		),
		'GR' => array( // Greece.
			'patterns'   => array(
				'/^\d{14}$/',
				'/^[A-Z]{2}\d{10}$/',
			),
			'confidence' => 85,
		),
		'PT' => array( // Portugal.
			'patterns'   => array(
				'/^\d{14}$/',
				'/^[A-Z]{2}\d{10}$/',
			),
			'confidence' => 85,
		),
	);

	/**
	 * International shipment pattern (28 digits)
	 */
	private const INTERNATIONAL_PATTERN = '/^\d{28}$/';

	/**
	 * Get the unique key for this shipping provider.
	 *
	 * @return string Unique key.
	 */
	public function get_key(): string {
		return 'dpd';
	}

	/**
	 * Get the name of this shipping provider.
	 *
	 * @return string Name of the shipping provider.
	 */
	public function get_name(): string {
		return 'DPD';
	}

	/**
	 * Get the icon URL for this shipping provider.
	 *
	 * @return string URL of the shipping provider icon.
	 */
	public function get_icon(): string {
		return esc_url( WC()->plugin_url() ) . '/assets/images/shipping_providers/dpd.png';
	}

	/**
	 * Get the description of this shipping provider.
	 *
	 * @return array Description of the shipping provider.
	 */
	public function get_shipping_from_countries(): array {
		return array_keys( self::TRACKING_PATTERNS );
	}

	/**
	 * Get the countries this shipping provider can ship to.
	 *
	 * DPD typically ships within Europe, so we return the same countries as shipping from.
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
		return 'https://www.dpd.com/tracking/' . rawurlencode( $tracking_number );
	}

	/**
	 * Get the tracking URL for a given tracking number and country code.
	 *
	 * @param string $tracking_number The tracking number to generate the URL for.
	 * @param string $country_code The country code for the shipment.
	 * @return string The tracking URL.
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
	 * Try to parse a DPD tracking number.
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

		// 1. Check international format first.
		if ( preg_match( self::INTERNATIONAL_PATTERN, $normalized ) ) {
			if ( in_array( $shipping_from, $this->get_shipping_from_countries(), true ) &&
				in_array( $shipping_to, $this->get_shipping_to_countries(), true ) ) {
				return array(
					'url'             => $this->get_tracking_url( $normalized ),
					'ambiguity_score' => 95,
				);
			}
			return null;
		}

		// 2. Check country-specific patterns.
		if ( $this->validate_country_pattern( $normalized, $shipping_from ) ) {
			$confidence = self::TRACKING_PATTERNS[ $shipping_from ]['confidence'];

			// Boost confidence for intra-DPD shipments.
			if ( in_array( $shipping_to, $this->get_shipping_to_countries(), true ) ) {
				$confidence = min( 98, $confidence + 5 );
			}

			return array(
				'url'             => $this->get_tracking_url( $normalized ),
				'ambiguity_score' => $confidence,
			);
		}

		return null;
	}
}
