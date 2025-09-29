<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\Fulfillments\Providers;

use Automattic\WooCommerce\Internal\Fulfillments\FulfillmentUtils;

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
	private array $major_operation_countries = array( 'DE', 'US', 'CA', 'GB', 'SG', 'JP', 'HK', 'NL', 'FR', 'IT', 'AU', 'CN', 'IN', 'ES', 'BE', 'CH', 'AT', 'SE', 'DK', 'NO', 'PL', 'CZ', 'FI', 'IE', 'PT', 'GR', 'HU', 'RO', 'BG', 'HR', 'SK', 'SI', 'LT', 'LV', 'EE', 'CY', 'MT', 'LU' );

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
		$tracking_number = strtoupper( $tracking_number ); // Uppercase for consistency.

		// DHL Global Mail and eCommerce prefixes.
		if ( preg_match( '/^(GM|LX|RX|CN|SG|MY|HK|AU|TH|420)/', $tracking_number ) ) {
			return 'https://webtrack.dhlglobalmail.com/?trackingnumber=' . rawurlencode( $tracking_number );
		}

		// DHL Paket Germany (3S...).
		if ( preg_match( '/^3S[A-Z0-9]{8,12}$/', $tracking_number ) ) {
			return 'https://www.dhl.de/en/privatkunden/dhl-sendungsverfolgung.html?piececode=' . rawurlencode( $tracking_number );
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
	 * Checks if DHL can ship between two countries.
	 *
	 * @param string $shipping_from Origin country code.
	 * @param string $shipping_to Destination country code.
	 * @return bool True if shipping route is supported.
	 */
	public function can_ship_from_to( string $shipping_from, string $shipping_to ): bool {
		return in_array( $shipping_from, $this->get_shipping_from_countries(), true ) &&
			in_array( $shipping_to, $this->get_shipping_to_countries(), true );
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

		$tracking_number  = strtoupper( preg_replace( '/\s+/', '', $tracking_number ) ); // Remove spaces and uppercase for consistency.
		$is_major_country = in_array( $shipping_from, $this->major_operation_countries, true ); // Major operation region flag.

		// DHL tracking number patterns with enhanced validation and comments.
		$patterns = array(
			// DHL Express Air Waybill: 10 or 11 digits, with check digit validation.
			'/^\d{10}$/'                                 => function () use ( $tracking_number ) {
				return FulfillmentUtils::validate_mod11_check_digit( $tracking_number ) ? 98 : 90;
			},
			'/^\d{11}$/'                                 => function () use ( $tracking_number ) {
				return FulfillmentUtils::validate_mod11_check_digit( $tracking_number ) ? 98 : 90;
			},

			// DHL Express JJD and JVGL formats.
			'/^JJD\d{10}$/'                              => 98,
			'/^JVGL\d{10}$/'                             => 98,

			// DHL Paket Germany: 12, 14, or 20 digits.
			// Only match 12/14-digit numeric for DHL if both from and to are DE (Germany).
			'/^\d{12}$/'                                 => function () use ( $shipping_from, $shipping_to ) {
				return ( 'DE' === $shipping_from && 'DE' === $shipping_to ) ? 92 : 60;
			},
			'/^\d{14}$/'                                 => function () use ( $shipping_from, $shipping_to ) {
				return ( 'DE' === $shipping_from && 'DE' === $shipping_to ) ? 92 : 60;
			},
			'/^\d{20}$/'                                 => 90,

			// DHL Paket Germany: 3S + 8–12 alphanumeric.
			'/^3S[A-Z0-9]{8,12}$/'                       => 95,

			// DHL eCommerce North America: GM + 16–20 digits.
			'/^GM\d{16,20}$/'                            => function () use ( $shipping_from ) {
				return in_array( $shipping_from, array( 'US', 'CA' ), true ) ? 95 : 80;
			},

			// DHL eCommerce Asia-Pacific: LX, RX, CN, SG, MY, HK, AU, TH + 9 digits + 2 letters.
			'/^(LX|RX|CN|SG|MY|HK|AU|TH)\d{9}[A-Z]{2}$/' => 92,

			// DHL eCommerce US consolidator: 420 + 27–31 digits.
			'/^420\d{23,31}$/'                           => 90,

			// DHL Global Forwarding: 7, 8, or 9 digits (numeric only).
			'/^\d{7,9}$/'                                => 88,

			// DHL Global Forwarding: 1 digit + 2 letters + 4–6 digits.
			'/^\d[A-Z]{2}\d{4,6}$/'                      => 90,

			// DHL Global Forwarding: 3–4 letters + 4–8 digits.
			'/^[A-Z]{3,4}\d{4,8}$/'                      => 88,

			// DHL Same Day: DSD + 8–12 digits.
			'/^DSD\d{8,12}$/'                            => 92,

			// DHL Piece Numbers: JD + 11 digits.
			'/^JD\d{11}$/'                               => 90,

			// DHL Supply Chain: DSC + 10–15 digits.
			'/^DSC\d{10,15}$/'                           => 85,

			// S10/UPU format: 2 letters + 9 digits + 2 letters (used for DHL eCommerce and Packet International).
			'/^[A-Z]{2}\d{9}[A-Z]{2}$/'                  => function () use ( $tracking_number ) {
				return FulfillmentUtils::check_s10_upu_format( $tracking_number ) ? 88 : 75;
			},

			// Fallback: 22 digit numeric (legacy/rare).
			'/^\d{22}$/'                                 => 70,
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
