<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\Fulfillments\Providers;

/**
 * FedEx Shipping Provider implementation.
 *
 * Handles FedEx tracking number detection and validation for all FedEx services.
 */
class FedExShippingProvider extends AbstractShippingProvider {
	/**
	 * List of countries where FedEx has significant operations.
	 *
	 * @var array<string>
	 */
	private array $supported_countries = array( 'US', 'CA', 'GB', 'DE', 'FR', 'AU', 'JP', 'MX', 'CN', 'IN', 'IT', 'ES', 'NL', 'BE', 'CH', 'AT', 'BR', 'SG' );

	/**
	 * Gets the unique provider key.
	 *
	 * @return string The provider key 'fedex'.
	 */
	public function get_key(): string {
		return 'fedex';
	}

	/**
	 * Gets the display name of the provider.
	 *
	 * @return string The provider name 'FedEx'.
	 */
	public function get_name(): string {
		return 'FedEx';
	}

	/**
	 * Gets the path to the provider's icon.
	 *
	 * @return string URL to the FedEx logo image.
	 */
	public function get_icon(): string {
		return esc_url( WC()->plugin_url() ) . '/assets/images/shipping_providers/fedex.png';
	}

	/**
	 * Generates the tracking URL for a given tracking number.
	 *
	 * @param string $tracking_number The tracking number to generate URL for.
	 * @return string The complete tracking URL.
	 */
	public function get_tracking_url( string $tracking_number ): string {
		return 'https://www.fedex.com/fedextrack/?tracknumbers=' . rawurlencode( $tracking_number );
	}

	/**
	 * Gets the list of origin countries supported by FedEx.
	 *
	 * @return array<string> Array of country codes.
	 */
	public function get_shipping_from_countries(): array {
		return $this->supported_countries;
	}

	/**
	 * Gets the list of destination countries supported by FedEx.
	 *
	 * @return array<string> Array of country codes.
	 */
	public function get_shipping_to_countries(): array {
		return $this->supported_countries;
	}

	/**
	 * Checks if FedEx can ship between two countries.
	 *
	 * @param string $shipping_from Origin country code.
	 * @param string $shipping_to Destination country code.
	 * @return bool True if shipping route is supported.
	 */
	public function can_ship_from_to( string $shipping_from, string $shipping_to ): bool {
		return in_array( $shipping_from, $this->supported_countries, true ) &&
				in_array( $shipping_to, $this->supported_countries, true );
	}

	/**
	 * Validates and parses a FedEx tracking number.
	 *
	 * @param string $tracking_number The tracking number to validate.
	 * @param string $shipping_from Origin country code.
	 * @param string $shipping_to Destination country code.
	 * @return array|null Array with tracking URL and score, or null if invalid.
	 */
	public function try_parse_tracking_number( string $tracking_number, string $shipping_from, string $shipping_to ): ?array {
		if ( empty( $tracking_number ) || ! $this->can_ship_from_to( $shipping_from, $shipping_to ) ) {
			return null;
		}

		$tracking_number  = strtoupper( preg_replace( '/\s+/', '', $tracking_number ) );
		$is_north_america = in_array( $shipping_from, array( 'US', 'CA' ), true );

		// Service-specific patterns with their base scores.
		$patterns = array(
			// FedEx Custom Critical (highest confidence).
			'/^0[01]\d{13,23}$/' => 98,

			// FedEx SmartPost (very specific).
			'/^023\d{17}$/'      => 96,
			'/^58\d{17,19}$/'    => 96,

			// FedEx Express 3x patterns.
			'/^3\d{10,14}$/'     => 96,

			// FedEx Freight (must come before generic digit patterns).
			'/^97\d{13,23}$/'    => 93,

			// FedEx Ground.
			'/^96\d{18,20}$/'    => $is_north_america ? 95 : 60,
			'/^7\d{11,20}$/'     => $is_north_america ? 90 : 75,

			// FedEx Express digit patterns (ordered by specificity).
			'/^\d{12}$/'         => $is_north_america ? 95 : 80,  // Reduced for EU.
			'/^\d{14}$/'         => $is_north_america ? 95 : 80,  // Reduced for EU.
			'/^\d{15}$/'         => $is_north_america ? 90 : 75,  // Reduced for EU.

			// Fallback patterns.
			'/^\d{20}$/'         => 70,
		);

		foreach ( $patterns as $pattern => $base_score ) {
			if ( preg_match( $pattern, $tracking_number ) ) {
				return array(
					'url'             => $this->get_tracking_url( $tracking_number ),
					'ambiguity_score' => is_callable( $base_score ) ? $base_score() : $base_score,
				);
			}
		}

		return null;
	}
}
