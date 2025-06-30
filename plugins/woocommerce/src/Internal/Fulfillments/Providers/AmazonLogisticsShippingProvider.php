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
	private array $operating_countries = array( 'US', 'CA', 'GB', 'DE', 'FR', 'BE', 'NL', 'IT', 'IN', 'MX', 'JP', 'AU', 'ES', 'CN', 'HK' );

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

		// Amazon Logistics tracking number patterns.
		$patterns = array(
			'/^TBA\d{12}$/'          => fn() => 'US' === $shipping_from ? 100 : 95,  // US standard format.
			'/^TBC\d{12}$/'          => fn() => 'CA' === $shipping_from ? 100 : 90,  // Canada standard format.
			'/^TBM\d{12}$/'          => fn() => 'MX' === $shipping_from ? 100 : 85,  // Mexico standard format.
			'/^CC\d{12}$/'           => fn() => in_array( $shipping_from, array( 'FR', 'BE', 'NL', 'DE' ), true ) ? 95 : 80,  // Europe.
			'/^GBA\d{12}$/'          => fn() => 'GB' === $shipping_from ? 100 : 85,  // United Kingdom.
			'/^RB\d{12}$/'           => fn() => in_array( $shipping_from, array( 'CN', 'HK' ), true ) ? 95 : 75,  // China/Hong Kong.
			'/^ZZ\d{12}$/'           => fn() => 'AU' === $shipping_from ? 100 : 80,  // Australia.
			'/^ZX\d{12}$/'           => fn() => 'IN' === $shipping_from ? 100 : 85,  // India.
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
