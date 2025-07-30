<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\Fulfillments\Providers;

use Automattic\WooCommerce\Internal\Fulfillments\FulfillmentUtils;

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

		$tracking_number  = strtoupper( preg_replace( '/\s+/', '', $tracking_number ) ); // Remove spaces and uppercase for consistency.
		$is_north_america = in_array( $shipping_from, array( 'US', 'CA' ), true ); // North America flag for scoring.
		$is_us_domestic   = 'US' === $shipping_from && 'US' === $shipping_to; // US domestic flag for scoring.

		// FedEx tracking number patterns with enhanced validation and comments.
		$patterns = array(
			// FedEx Door Tag: DT + 12 digits (US/CA only).
			'/^DT\d{12}$/'       => $is_north_america ? 90 : 0,

			// FedEx Custom Critical: 0 or 1 followed by 13-23 digits (very rare, highest confidence).
			'/^0[01]\d{13,23}$/' => 98,

			// FedEx SmartPost: 023 + 17 digits (US only, SmartPost).
			'/^023\d{17}$/'      => 97,

			// FedEx SmartPost: 58 + 17-19 digits (older SmartPost).
			'/^58\d{17,19}$/'    => 96,

			// FedEx Express: 12 digits (most common, with check digit validation).
			'/^\d{12}$/'         => function () use ( $tracking_number, $is_north_america, $is_us_domestic ) {
				if ( FulfillmentUtils::validate_fedex_check_digit( $tracking_number ) ) {
					return $is_north_america || $is_us_domestic ? 98 : 85; // High confidence if check digit valid.
				}
				return $is_north_america ? ( $is_us_domestic ? 98 : 85 ) : 70; // Lower if check digit invalid.
			},

			// FedEx Express: 15 digits (less common, with check digit validation).
			'/^\d{15}$/'         => function () use ( $tracking_number, $is_north_america ) {
				if ( FulfillmentUtils::validate_fedex_check_digit( $tracking_number ) ) {
					return $is_north_america ? 96 : 80; // High confidence if check digit valid.
				}
				return $is_north_america ? 80 : 65; // Lower if check digit invalid.
			},

			// FedEx Express: 14 digits (with check digit validation).
			'/^\d{14}$/'         => function () use ( $tracking_number, $is_north_america ) {
				if ( FulfillmentUtils::validate_fedex_check_digit( $tracking_number ) ) {
					return $is_north_america ? 95 : 78; // High confidence if check digit valid.
				}
				return $is_north_america ? 78 : 60; // Lower if check digit invalid.
			},

			// FedEx Express: 34 digits (rare, international bulk shipments).
			'/^\d{34}$/'         => 90,

			// FedEx Ground: 96 + 18-20 digits (US/CA only).
			'/^96\d{18,20}$/'    => $is_north_america ? 95 : 60,

			// FedEx Ground: 7 + 11-20 digits (US/CA only, legacy).
			'/^7\d{11,20}$/'     => $is_north_america ? 90 : 75,

			// FedEx Freight: 97 + 13-23 digits (Freight/LTL).
			'/^97\d{13,23}$/'    => 93,

			// FedEx Express International: 3 + 10-14 digits (Europe/Asia).
			'/^3\d{10,14}$/'     => 92,

			// FedEx International Priority: 8 + 8-14 digits (Europe/Asia).
			'/^8\d{8,14}$/'      => function () use ( $shipping_from ) {
				return in_array( $shipping_from, array( 'GB', 'DE', 'FR', 'IT', 'ES', 'NL' ), true ) ? 93 : 75;
			},

			// FedEx Express Next Flight Out: NFO + 10-15 digits.
			'/^NFO\d{10,15}$/'   => 92,

			// FedEx SameDay: SD + 10-15 digits.
			'/^SD\d{10,15}$/'    => 90,

			// Fallback: 20 digit numeric (used by some international and legacy services).
			'/^\d{20}$/'         => 70,

			// Fallback: 22 digit numeric (rare, legacy).
			'/^\d{22}$/'         => 65,
		);

		foreach ( $patterns as $pattern => $base_score ) {
			if ( preg_match( $pattern, $tracking_number ) ) {
				$score = is_callable( $base_score ) ? $base_score() : $base_score;
				if ( $score > 0 ) {
					return array(
						'url'             => $this->get_tracking_url( $tracking_number ),
						'ambiguity_score' => $score,
					);
				}
			}
		}

		return null; // No matching pattern found.
	}
}
