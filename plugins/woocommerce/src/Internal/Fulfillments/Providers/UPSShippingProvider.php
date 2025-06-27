<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\Fulfillments\Providers;

/**
 * UPS Shipping Provider class.
 */
class UPSShippingProvider extends AbstractShippingProvider {
	/**
	 * List of international shipping countries.
	 *
	 * @var array
	 */
	public array $international_shipping_countries = array( 'AD', 'AE', 'AF', 'AG', 'AI', 'AL', 'AM', 'AN', 'AO', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AW', 'AX', 'AZ', 'BA', 'BB', 'BD', 'BE', 'BF', 'BG', 'BH', 'BI', 'BJ', 'BM', 'BN', 'BO', 'BR', 'BS', 'BT', 'BV', 'BW', 'BY', 'BZ', 'CA', 'CC', 'CD', 'CF', 'CG', 'CH', 'CI', 'CK', 'CL', 'CM', 'CN', 'CO', 'CR', 'CU', 'CV', 'CY', 'CZ', 'DE', 'DJ', 'DK', 'DM', 'DO', 'DZ', 'EC', 'EE', 'EG', 'ER', 'ES', 'ET', 'FI', 'FJ', 'FK', 'FM', 'FO', 'FR', 'GA', 'GB', 'GD', 'GE', 'GF', 'GG', 'GH', 'GI', 'GL', 'GM', 'GN', 'GP', 'GQ', 'GR', 'GS', 'GT', 'GU', 'GW', 'GY', 'HK', 'HM', 'HN', 'HR', 'HT', 'HU', 'ID', 'IE', 'IL', 'IM', 'IN', 'IO', 'IQ', 'IR', 'IS', 'IT', 'JE', 'JM', 'JO', 'JP', 'KE', 'KG', 'KH', 'KI', 'KM', 'KN', 'KP', 'KR', 'KW', 'KY', 'KZ', 'LA', 'LB', 'LC', 'LI', 'LK', 'LR', 'LS', 'LT', 'LU', 'LV', 'LY', 'MA', 'MC', 'MD', 'ME', 'MF', 'MG', 'MH', 'MK', 'ML', 'MM', 'MN', 'MO', 'MP', 'MQ', 'MR', 'MS', 'MT', 'MU', 'MV', 'MW', 'MX', 'MY', 'MZ', 'NA', 'NC', 'NE', 'NF', 'NG', 'NI', 'NL', 'NO', 'NP', 'NR', 'NU', 'NZ', 'OM', 'PA', 'PE', 'PF', 'PG', 'PH', 'PK', 'PL', 'PM', 'PN', 'PR', 'PS', 'PT', 'PW', 'PY', 'QA', 'RE', 'RO', 'RS', 'RU', 'RW', 'SA', 'SB', 'SC', 'SD', 'SE', 'SG', 'SH', 'SI', 'SJ', 'SK', 'SL', 'SM', 'SN', 'SO', 'SR', 'SS', 'ST', 'SV', 'SX', 'SY', 'SZ', 'TC', 'TD', 'TF', 'TG', 'TH', 'TJ', 'TK', 'TL', 'TM', 'TN', 'TO', 'TR', 'TT', 'TV', 'TW', 'TZ', 'UA', 'UG', 'UM', 'US', 'UY', 'UZ', 'VA', 'VC', 'VE', 'VG', 'VI', 'VN', 'VU', 'WF', 'WS', 'YE', 'YT', 'ZA', 'ZM', 'ZW' );

	/**
	 * List of domestic shipping countries.
	 *
	 * @var array
	 */
	public array $domestic_shipping_countries = array( 'US', 'CA' );

	/**
	 * Get the key of the shipping provider.
	 *
	 * @return string
	 */
	public function get_key(): string {
		return 'ups';
	}

	/**
	 * Get the name of the shipping provider.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'UPS';
	}

	/**
	 * Get the icon of the shipping provider.
	 *
	 * @return string
	 */
	public function get_icon(): string {
		return esc_url( WC()->plugin_url() ) . '/assets/images/shipping_providers/ups.png';
	}

	/**
	 * Get the tracking URL for a given tracking number.
	 *
	 * @param string $tracking_number The tracking number.
	 * @return string The tracking URL.
	 */
	public function get_tracking_url( string $tracking_number ): string {
		return 'https://www.ups.com/track?tracknum=' . rawurlencode( $tracking_number );
	}

	/**
	 * Get the countries from which this provider can ship.
	 *
	 * @return array An array of country codes.
	 */
	public function get_shipping_from_countries(): array {
		return $this->international_shipping_countries;
	}

	/**
	 * Get the countries to which this provider can ship.
	 *
	 * @return array An array of country codes.
	 */
	public function get_shipping_to_countries(): array {
		return $this->international_shipping_countries;
	}

	/**
	 * Check if this provider can ship from a specific country.
	 *
	 * @param string $shipping_from The country code from which the shipment is sent.
	 * @param string $shipping_to The country code to which the shipment is sent.
	 *
	 * @return bool True if this provider can ship from the country, false otherwise.
	 */
	public function can_ship_from_to( string $shipping_from, string $shipping_to ): bool {
		if ( $shipping_from === $shipping_to ) {
			return in_array( $shipping_from, $this->domestic_shipping_countries, true );
		} else {
			return in_array( $shipping_from, $this->international_shipping_countries, true ) &&
				in_array( $shipping_to, $this->international_shipping_countries, true );
		}
	}

	/**
	 * Try to parse the tracking number with additional parameters.
	 *
	 * @param string $tracking_number The tracking number.
	 * @param string $shipping_from The country code from which the shipment is sent.
	 * @param string $shipping_to The country code to which the shipment is sent.
	 *
	 * @return array|null The tracking URL with ambiguity score, or null if parsing fails.
	 */
	public function try_parse_tracking_number( string $tracking_number, string $shipping_from, string $shipping_to ): ?array {
		if ( empty( $tracking_number ) || empty( $shipping_from ) || empty( $shipping_to ) || ! $this->can_ship_from_to( $shipping_from, $shipping_to ) ) {
			return null;
		}

		// UPS tracking numbers typically start with "1Z" followed by 16 alphanumeric characters.
		$is_1z_format = preg_match( '/^1Z[0-9A-Z]{16}$/i', $tracking_number );

		// T and H formats are used for domestic shipments.
		$is_t_format          = preg_match( '/^T\d{10}$/i', $tracking_number );
		$is_h_format          = preg_match( '/^H\d{10}$/i', $tracking_number );
		$is_domestic_shipping = $shipping_from === $shipping_to;
		$is_9x_format         = preg_match( '/^9\d{21,34}$/i', $tracking_number );

		$match = false;

		if ( $is_1z_format ) {
			$match           = true;
			$ambiguity_score = 100;
		} elseif ( ( $is_t_format || $is_h_format ) && 'US' === $shipping_from && $is_domestic_shipping ) {
			$match           = true;
			$ambiguity_score = 75;
		} elseif ( $is_9x_format ) {
			$match           = true;
			$ambiguity_score = 'CA' === $shipping_from ? 60 : 40;
		}

		return $match ? array(
			'url'             => $this->get_tracking_url( $tracking_number ),
			'ambiguity_score' => $ambiguity_score,
		) : null;
	}
}
