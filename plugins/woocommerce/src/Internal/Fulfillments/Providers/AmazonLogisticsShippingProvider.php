<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\Fulfillments\Providers;

/**
 * Amazon Logistics Shipping Provider implementation.
 *
 * Handles Amazon Logistics tracking number detection and validation.
 */
class AmazonLogisticsShippingProvider extends AbstractShippingProvider {
	/**
	 * Countries where Amazon Logistics operates.
	 *
	 * @var array<string>
	 */
	private array $operating_countries = array( 'US', 'CA', 'GB', 'DE', 'FR', 'BE', 'NL', 'IT', 'IN', 'MX', 'JP', 'AU', 'ES', 'CN', 'HK', 'SG', 'GG', 'JE', 'IM', 'GI', 'AT', 'CH', 'PL', 'SE', 'DK', 'NO', 'FI', 'IE', 'PT', 'CZ', 'HU', 'RO', 'BG', 'HR', 'SK', 'SI', 'EE', 'LV', 'LT', 'CY', 'MT', 'LU', 'GR', 'BR', 'TR', 'AE', 'SA', 'EG', 'KW', 'IL', 'ZA', 'KR', 'TW', 'TH', 'MY', 'ID', 'PH', 'VN', 'NZ' );

	/**
	 * Gets the unique provider key.
	 *
	 * @return string The provider key 'amazon-logistics'.
	 */
	public function get_key(): string {
		return 'amazon-logistics';
	}

	/**
	 * Gets the display name of the provider.
	 *
	 * @return string The provider name 'Amazon Logistics'.
	 */
	public function get_name(): string {
		return 'Amazon Logistics';
	}

	/**
	 * Gets the path to the provider's icon.
	 *
	 * @return string URL to the Amazon Logistics logo image.
	 */
	public function get_icon(): string {
		return esc_url( WC()->plugin_url() ) . '/assets/images/shipping_providers/amazon-logistics.png';
	}

	/**
	 * Gets the list of origin countries supported by Amazon Logistics.
	 *
	 * @return array<string> Array of country codes.
	 */
	public function get_shipping_from_countries(): array {
		return $this->operating_countries;
	}

	/**
	 * Gets the list of destination countries supported by Amazon Logistics.
	 *
	 * @return array<string> Array of country codes.
	 */
	public function get_shipping_to_countries(): array {
		return $this->operating_countries;
	}

	/**
	 * Checks if Amazon Logistics can ship between two countries.
	 *
	 * @param string $shipping_from Origin country code.
	 * @param string $shipping_to Destination country code.
	 * @return bool True if shipping route is supported.
	 */
	public function can_ship_from_to( string $shipping_from, string $shipping_to ): bool {
		return in_array( $shipping_from, $this->operating_countries, true ) &&
			in_array( $shipping_to, $this->operating_countries, true );
	}

	/**
	 * Generates the tracking URL for an Amazon Logistics tracking number.
	 *
	 * @param string $tracking_number The tracking number to generate URL for.
	 * @return string The complete tracking URL.
	 */
	public function get_tracking_url( string $tracking_number ): string {
		return 'https://www.amazon.com/progress-tracker/package/ref=ppx_yo_dt_b_track_package_o0?_=' .
			strtoupper( rawurlencode( $tracking_number ) );
	}

	/**
	 * Validates and parses an Amazon Logistics tracking number.
	 *
	 * @param string $tracking_number The tracking number to validate.
	 * @param string $shipping_from Origin country code.
	 * @param string $shipping_to Destination country code.
	 * @return array|null Array with tracking URL and score, or null if invalid.
	 */
	public function try_parse_tracking_number(
		string $tracking_number,
		string $shipping_from,
		string $shipping_to
	): ?array {
		if ( empty( $tracking_number ) || ! $this->can_ship_from_to( $shipping_from, $shipping_to ) ) {
			return null;
		}

		$tracking_number = strtoupper( preg_replace( '/\s+/', '', $tracking_number ) );

		// Amazon Logistics tracking number patterns with region/service differentiation.
		$patterns = array(
			// North America patterns.
			'/^TBA\d{12}$/'       => fn() => 'US' === $shipping_from ? 100 : 95, // US standard format.
			'/^TBC\d{12}$/'       => fn() => 'CA' === $shipping_from ? 100 : 90, // Canada standard format.
			'/^TBM\d{12}$/'       => fn() => 'MX' === $shipping_from ? 100 : 85, // Mexico standard format.

			// European patterns.
			'/^CC\d{12}$/'        => fn() => in_array( $shipping_from, array( 'FR', 'BE', 'NL', 'DE' ), true ) ? 95 : 80, // Continental Europe.
			'/^GBA\d{12}$/'       => fn() => 'GB' === $shipping_from ? 100 : 85, // United Kingdom.
			'/^UK\d{10}$/'        => fn() => 'GB' === $shipping_from ? 100 : 85, // United Kingdom.
			'/^W[A-Z]\d{9}GB$/'   => fn() => 'GB' === $shipping_from ? 99 : 85, // Amazon UK specific pattern.
			'/^[A-Z]{2}\d{9}GB$/' => fn() => 'GB' === $shipping_from ? 92 : 75, // United Kingdom.
			'/^AM\d{12}$/'        => fn() => in_array( $shipping_from, array( 'DE', 'FR', 'IT', 'ES' ), true ) ? 95 : 80, // Amazon Europe.
			'/^D\d{13}$/'         => fn() => 'DE' === $shipping_from ? 95 : 75, // Germany specific.

			// Asia-Pacific patterns.
			'/^RB\d{12}$/'        => fn() => in_array( $shipping_from, array( 'CN', 'HK' ), true ) ? 95 : 75, // China/Hong Kong.
			'/^ZZ\d{12}$/'        => fn() => 'AU' === $shipping_from ? 100 : 80, // Australia.
			'/^ZX\d{12}$/'        => fn() => 'IN' === $shipping_from ? 100 : 85, // India.
			'/^JP\d{12}$/'        => fn() => 'JP' === $shipping_from ? 100 : 85, // Japan.
			'/^SG\d{12}$/'        => fn() => 'SG' === $shipping_from ? 100 : 85, // Singapore.

			// Amazon Fresh/Whole Foods.
			'/^AF\d{12}$/'        => fn() => 'US' === $shipping_from ? 98 : 80, // Amazon Fresh US.
			'/^WF\d{12}$/'        => fn() => 'US' === $shipping_from ? 98 : 80, // Whole Foods US.

			// Amazon Business.
			'/^AB\d{12}$/'        => fn() => in_array( $shipping_from, array( 'US', 'GB', 'DE', 'FR' ), true ) ? 95 : 80, // Amazon Business.

			// Legacy and alternative formats.
			'/^TB[A-Z]\d{11}$/'   => fn() => in_array( $shipping_from, array( 'US', 'CA', 'MX' ), true ) ? 90 : 70, // Variable third character.
			'/^AZ\d{12}$/'        => fn() => in_array( $shipping_from, array( 'US', 'GB', 'DE' ), true ) ? 88 : 75, // Alternative format.

			// Amazon Pantry/Subscribe & Save.
			'/^AP\d{12}$/'        => fn() => 'US' === $shipping_from ? 90 : 75, // Pantry US.
			'/^SS\d{12}$/'        => fn() => 'US' === $shipping_from ? 90 : 75, // Subscribe & Save US.

			// Fallback: 15-20 character Amazon codes (future-proof, low confidence).
			'/^[A-Z0-9]{15,20}$/' => fn() => 60,
		);

		foreach ( $patterns as $pattern => $score_callback ) {
			if ( preg_match( $pattern, $tracking_number ) ) {
				return array(
					'url'             => $this->get_tracking_url( $tracking_number ),
					'ambiguity_score' => $score_callback(),
				);
			}
		}

		return null;
	}
}
