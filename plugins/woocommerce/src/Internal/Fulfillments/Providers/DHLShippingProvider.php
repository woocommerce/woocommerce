<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\Fulfillments\Providers;

/**
 * DHL Shipping Provider implementation.
 *
 * Handles DHL tracking number detection and validation for all DHL services.
 */
class DHLShippingProvider extends AbstractShippingProvider {
	/**
	 * List of countries where DHL has significant operations.
	 *
	 * @var array<string>
	 */
	private array $major_operation_countries = array( 'DE', 'US', 'CA', 'GB', 'SG', 'JP', 'HK', 'NL', 'FR', 'IT', 'AU', 'CN', 'IN', 'ES', 'BE', 'CH', 'AT', 'SE', 'DK', 'NO' );

	/**
	 * Gets the unique provider key.
	 *
	 * @return string The provider key 'dhl'.
	 */
	public function get_key(): string {
		return 'dhl';
	}

	/**
	 * Gets the display name of the provider.
	 *
	 * @return string The provider name 'DHL'.
	 */
	public function get_name(): string {
		return 'DHL';
	}

	/**
	 * Gets the path to the provider's icon.
	 *
	 * @return string URL to the DHL logo image.
	 */
	public function get_icon(): string {
		return esc_url( WC()->plugin_url() ) . '/assets/images/shipping_providers/dhl.png';
	}

	/**
	 * Generates the appropriate tracking URL based on DHL service type.
	 *
	 * @param string $tracking_number The tracking number to generate URL for.
	 * @return string The complete tracking URL.
	 */
	public function get_tracking_url( string $tracking_number ): string {
		$tracking_number = strtoupper( $tracking_number );

		// DHL Global Mail services.
		if ( preg_match( '/^(GM|LX|RX|AU|TH)/', $tracking_number ) ) {
			return 'https://webtrack.dhlglobalmail.com/?trackingnumber=' . rawurlencode( $tracking_number );
		}

		// Standard DHL Express tracking.
		return 'https://www.dhl.com/en/express/tracking.html?AWB=' . rawurlencode( $tracking_number );
	}

	/**
	 * Gets the list of origin countries supported by DHL.
	 *
	 * @return array<string> Array of country codes.
	 */
	public function get_shipping_from_countries(): array {
		return $this->major_operation_countries;
	}

	/**
	 * Gets the list of destination countries supported by DHL.
	 *
	 * @return array<string> Array of country codes.
	 */
	public function get_shipping_to_countries(): array {
		return array_keys( wc()->countries->get_countries() );
	}

	/**
	 * Validates and parses a DHL tracking number.
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
		$is_major_country = in_array( $shipping_from, $this->major_operation_countries, true );

		// Service-specific patterns with their base scores.
		$patterns = array(
			// DHL Express (highest confidence).
			'/^JJD\d{10}$/'         => 98,  // JJD format.
			'/^JVGL\d{10}$/'        => 98, // JVGL format.
			'/^\d{11}$/'            => 95,     // Air Waybill.
			'/^\d{10}$/'            => $is_major_country ? 85 : 70, // 10-digit.

			// DHL eCommerce.
			'/^GM\d{16,20}$/'       => in_array( $shipping_from, array( 'US', 'CA' ), true ) ? 95 : 80,
			'/^LX\d{9}[A-Z]{2}$/'   => 90,
			'/^RX\d{9}[A-Z]{2}$/'   => 90,
			'/^\d{14}$/'            => 'GB' === $shipping_from ? 85 : 0, // UK eCommerce.

			// DHL Parcel.
			'/^3S[A-Z0-9]{8,12}$/'  => in_array( $shipping_from, array( 'DE', 'NL', 'BE', 'FR', 'GB' ), true ) ? 95 : 85,

			// DHL Global Forwarding.
			'/^\d[A-Z]{2}\d{4,6}$/' => 90,
			'/^[A-Z]{3,4}\d{4,8}$/' => 90,

			// DHL Piece Numbers.
			'/^JD\d{11}$/'          => 88,
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

		return null;
	}
}
