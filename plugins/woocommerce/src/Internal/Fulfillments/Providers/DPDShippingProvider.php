<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\Fulfillments\Providers;

/**
 * DPD Shipping Provider class.
 *
 * Provides DPD tracking number validation, supported countries, and tracking URL generation.
 */
class DPDShippingProvider extends AbstractShippingProvider {

	/**
	 * DPD tracking number patterns by country with service differentiation.
	 *
	 * @var array<string, array{patterns: array<int, string>, confidence: int, services?: array<string, int>}>
	 */
	private const TRACKING_PATTERNS = array(
		'DE' => array( // Germany.
			'patterns'   => array(
				'/^\d{14}$/',
				'/^\d{12}$/',
				'/^02\d{12}$/', // DPD Classic.
				'/^05\d{12}$/', // DPD Express.
				'/^09\d{12}$/', // DPD Predict.
				'/^[A-Z]{2}\d{9}[A-Z]{2}$/', // S10/UPU international.
				'/^\d{24}$/', // 24-digit fallback.
			),
			'confidence' => 80,
			'services'   => array(
				'classic' => 80,
				'express' => 85,
				'predict' => 85,
				's10'     => 90,
			),
		),
		'GB' => array( // United Kingdom.
			'patterns'   => array(
				'/^\d{14}$/',
				'/^[A-Z]{2}\d{9}GB$/',
				'/^03\d{12}$/', // DPD Next Day.
				'/^06\d{12}$/', // DPD Express.
				'/^1[56]\d{12}$/', // Predict/Return.
				'/^[A-Z]{2}\d{9}[A-Z]{2}$/', // S10/UPU international.
				'/^\d{24}$/', // 24-digit fallback.
			),
			'confidence' => 90,
			'services'   => array(
				'next_day' => 88,
				'express'  => 88,
				's10'      => 90,
			),
		),
		'FR' => array( // France.
			'patterns'   => array(
				'/^\d{14}$/',
				'/^\d{12}$/',
				'/^02\d{12}$/', // DPD Relais.
				'/^04\d{12}$/', // DPD Predict.
				'/^[A-Z]{2}\d{9}[A-Z]{2}$/', // S10/UPU international.
				'/^\d{24}$/', // 24-digit fallback.
			),
			'confidence' => 78,
			'services'   => array(
				'relais'  => 82,
				'predict' => 82,
				's10'     => 90,
			),
		),
		'NL' => array( // Netherlands.
			'patterns'   => array(
				'/^\d{14}$/',
				'/^\d{12}$/',
				'/^03\d{12}$/', // DPD Classic.
				'/^07\d{12}$/', // DPD Express.
				'/^[A-Z]{2}\d{9}[A-Z]{2}$/', // S10/UPU international.
				'/^\d{24}$/', // 24-digit fallback.
			),
			'confidence' => 78,
			'services'   => array(
				'classic' => 82,
				'express' => 85,
				's10'     => 90,
			),
		),
		'BE' => array( // Belgium.
			'patterns'   => array(
				'/^\d{14}$/',
				'/^\d{12}$/',
				'/^03\d{12}$/', // DPD Classic.
				'/^08\d{12}$/', // DPD Express.
				'/^[A-Z]{2}\d{9}[A-Z]{2}$/', // S10/UPU international.
				'/^\d{24}$/', // 24-digit fallback.
			),
			'confidence' => 78,
			'services'   => array(
				'classic' => 82,
				'express' => 85,
				's10'     => 90,
			),
		),
		'PL' => array( // Poland.
			'patterns'   => array(
				'/^\d{14}$/',
				'/^[A-Z]{2}\d{10}$/',
				'/^[A-Z]{2}\d{9}[A-Z]{2}$/', // S10/UPU international.
				'/^\d{24}$/', // 24-digit fallback.
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
	 * S10/UPU international pattern.
	 */
	private const S10_PATTERN = '/^[A-Z]{2}\d{9}[A-Z]{2}$/';

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
	 * Validate tracking number against country-specific patterns and determine service type.
	 *
	 * @param string $tracking_number The tracking number to validate.
	 * @param string $country_code The country code for the shipment.
	 * @return array|bool Array with service info if valid, false otherwise.
	 */
	private function validate_country_pattern( string $tracking_number, string $country_code ) {
		if ( ! isset( self::TRACKING_PATTERNS[ $country_code ] ) ) {
			return false;
		}

		$country_data     = self::TRACKING_PATTERNS[ $country_code ];
		$detected_service = null;
		$confidence_boost = 0;

		// Check service-specific patterns first.
		if ( isset( $country_data['services'] ) ) {
			if ( preg_match( '/^02\d{12}$/', $tracking_number ) ) {
				$detected_service = 'classic';
				$confidence_boost = $country_data['services']['classic'] ?? 0;
			} elseif ( preg_match( '/^0[34578]\d{12}$/', $tracking_number ) ) {
				$detected_service = 'express';
				$confidence_boost = $country_data['services']['express'] ?? 0;
			} elseif ( preg_match( '/^0[49]\d{12}$/', $tracking_number ) ) {
				$detected_service = 'predict';
				$confidence_boost = $country_data['services']['predict'] ?? 0;
			} elseif ( preg_match( '/^03\d{12}$/', $tracking_number ) && 'GB' === $country_code ) {
				$detected_service = 'next_day';
				$confidence_boost = $country_data['services']['next_day'] ?? 0;
			} elseif ( preg_match( '/^02\d{12}$/', $tracking_number ) && 'FR' === $country_code ) {
				$detected_service = 'relais';
				$confidence_boost = $country_data['services']['relais'] ?? 0;
			} elseif ( preg_match( self::S10_PATTERN, $tracking_number ) ) {
				$detected_service = 's10';
				$confidence_boost = $country_data['services']['s10'] ?? 90;
			}
		}

		// Check all patterns.
		foreach ( $country_data['patterns'] as $pattern ) {
			if ( preg_match( $pattern, $tracking_number ) ) {
				return array(
					'valid'            => true,
					'service'          => $detected_service,
					'confidence_boost' => $confidence_boost,
				);
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

		// 1. Check international 28-digit format first.
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

		// 2. Check S10/UPU format (international DPD).
		if ( preg_match( self::S10_PATTERN, $normalized ) ) {
			return array(
				'url'             => $this->get_tracking_url( $normalized ),
				'ambiguity_score' => 90,
			);
		}

		// 3. Check country-specific patterns.
		$validation_result = $this->validate_country_pattern( $normalized, $shipping_from );
		if ( $validation_result && is_array( $validation_result ) ) {
			$confidence = self::TRACKING_PATTERNS[ $shipping_from ]['confidence'];

			// Apply service-specific confidence boost.
			if ( $validation_result['confidence_boost'] > 0 ) {
				$confidence = min( 95, $validation_result['confidence_boost'] );
			}

			// Boost confidence for intra-DPD shipments.
			if ( in_array( $shipping_to, $this->get_shipping_to_countries(), true ) ) {
				$confidence = min( 98, $confidence + 3 );
			}

			// Additional boost for express services.
			if ( 'express' === $validation_result['service'] ) {
				$confidence = min( 98, $confidence + 2 );
			}

			return array(
				'url'             => $this->get_tracking_url( $normalized ),
				'ambiguity_score' => $confidence,
			);
		}

		// 4. Fallback: 12–24 digit numeric.
		if ( preg_match( '/^\d{12,24}$/', $normalized ) ) {
			return array(
				'url'             => $this->get_tracking_url( $normalized ),
				'ambiguity_score' => 60,
			);
		}

		return null;
	}
}
