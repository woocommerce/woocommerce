<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\Fulfillments\Providers;

use Automattic\WooCommerce\Internal\Fulfillments\FulfillmentUtils;

/**
 * UPS Shipping Provider class.
 */
class UPSShippingProvider extends AbstractShippingProvider {
	/**
	 * Countries that support international UPS shipping.
	 *
	 * @var array
	 */
	private array $international_shipping_countries = array( 'AF', 'AX', 'AL', 'DZ', 'AS', 'AD', 'AO', 'AI', 'AQ', 'AG', 'AM', 'AW', 'AU', 'AT', 'AZ', 'BS', 'BH', 'BD', 'BB', 'BY', 'BE', 'BZ', 'BJ', 'BM', 'BT', 'BO', 'BQ', 'BA', 'BW', 'BV', 'BR', 'IO', 'BN', 'BG', 'BF', 'BI', 'CV', 'KH', 'CM', 'CA', 'KY', 'CF', 'TD', 'CL', 'CN', 'CX', 'CC', 'CO', 'KM', 'CD', 'CG', 'CK', 'CR', 'CI', 'HR', 'CU', 'CW', 'CY', 'CZ', 'DK', 'DJ', 'DM', 'DO', 'EC', 'EG', 'SV', 'GQ', 'ER', 'EE', 'SZ', 'ET', 'FK', 'FO', 'FJ', 'FI', 'FR', 'GF', 'PF', 'TF', 'GA', 'GM', 'GE', 'DE', 'GH', 'GI', 'GR', 'GL', 'GD', 'GP', 'GU', 'GT', 'GG', 'GN', 'GW', 'GY', 'HT', 'HM', 'VA', 'HN', 'HK', 'HU', 'IS', 'IN', 'ID', 'IR', 'IQ', 'IE', 'IM', 'IL', 'IT', 'JM', 'JP', 'JE', 'JO', 'KZ', 'KE', 'KI', 'KP', 'KR', 'KW', 'KG', 'LA', 'LV', 'LB', 'LS', 'LR', 'LY', 'LI', 'LT', 'LU', 'MO', 'MG', 'MW', 'MY', 'MV', 'ML', 'MT', 'MH', 'MQ', 'MR', 'MU', 'YT', 'MX', 'FM', 'MD', 'MC', 'MN', 'ME', 'MS', 'MA', 'MZ', 'MM', 'NA', 'NR', 'NP', 'NL', 'NC', 'NZ', 'NI', 'NE', 'NG', 'NU', 'NF', 'MK', 'MP', 'NO', 'OM', 'PK', 'PW', 'PS', 'PA', 'PG', 'PY', 'PE', 'PH', 'PN', 'PL', 'PT', 'PR', 'QA', 'RE', 'RO', 'RU', 'RW', 'BL', 'SH', 'KN', 'LC', 'MF', 'PM', 'VC', 'WS', 'SM', 'ST', 'SA', 'SN', 'RS', 'SC', 'SL', 'SG', 'SX', 'SK', 'SI', 'SB', 'SO', 'ZA', 'GS', 'SS', 'ES', 'LK', 'SD', 'SR', 'SJ', 'SE', 'CH', 'SY', 'TW', 'TJ', 'TZ', 'TH', 'TL', 'TG', 'TK', 'TO', 'TT', 'TN', 'TR', 'TM', 'TC', 'TV', 'UG', 'UA', 'AE', 'GB', 'US', 'UM', 'UY', 'UZ', 'VU', 'VE', 'VN', 'VG', 'VI', 'WF', 'EH', 'YE', 'ZM', 'ZW' );

	/**
	 * Countries that support domestic UPS shipping.
	 *
	 * @var array
	 */
	private array $domestic_shipping_countries = array( 'US', 'CA', 'MX', 'BR', 'AR', 'CL', 'CO', 'PE', 'CR', 'PR', 'DE', 'GB', 'FR', 'IT', 'ES', 'NL', 'BE', 'PL', 'SE', 'DK', 'AT', 'CH', 'PT', 'IE', 'CZ', 'HU', 'FI', 'NO', 'CN', 'HK', 'IN', 'JP', 'KR', 'SG', 'MY', 'TH', 'VN', 'PH', 'AU', 'NZ', 'AE', 'SA', 'ZA', 'TR', 'IL', 'KE', 'NG' );

	/**
	 * Countries that support UPS domestic shipping but use international tracking formats.
	 *
	 * @var array
	 */
	private array $domestic_but_international_tracking = array( 'IN', 'ZA', 'VN', 'NG', 'PR', 'HK', 'MO', 'CN', 'BR', 'KE' );

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
	 * Get the path of the icon of the shipping provider.
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
			return in_array( $shipping_from, $this->domestic_shipping_countries, true ) ||
				in_array( $shipping_from, $this->domestic_but_international_tracking, true );
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

		$tracking_number      = strtoupper( $tracking_number );
		$is_domestic_shipping = $shipping_from === $shipping_to;

		// UPS tracking number patterns (ordered by confidence).
		$patterns = array(
			// 1Z format (standard UPS) - 18 chars, check digit validation.
			'/^1Z[0-9A-Z]{16}$/'        => function () use ( $tracking_number ) {
				return FulfillmentUtils::validate_ups_1z_check_digit( $tracking_number ) ? 100 : 95;
			},

			// Numeric only: 12 digits (common for UPS Air/Ground, with mod10 check digit).
			'/^\d{12}$/'                => function () use ( $tracking_number ) {
				return FulfillmentUtils::validate_mod10_check_digit( $tracking_number ) ? 90 : 80;
			},

			// Numeric only: 9 or 10 digits (legacy/freight).
			'/^\d{10}$/'                => 75,
			'/^\d{9}$/'                 => 70,

			// T, H, or V prefix + 10 digits (special international/freight).
			'/^[THV]\d{10}$/'           => 85,

			// UPS InfoNotice (J + 10 digits).
			'/^J\d{10}$/'               => 80,

			// UPS Mail Innovations Parcel ID (MI + 6 digits + up to 22 alphanum).
			'/^MI\d{6}[A-Z0-9]{6,22}$/' => 80,

			// USPS Delivery Confirmation (Mail Innovations, 22–34 digits).
			'/^9\d{21,33}$/'            => function () use ( $shipping_from ) {
				return in_array( $shipping_from, array( 'US', 'CA' ), true ) ? 85 : 70;
			},

			// UPU S10 format (international, e.g. 'AA123456789CC').
			'/^[A-Z]{2}\d{9}[A-Z]{2}$/' => function () use ( $shipping_from ) {
				return in_array( $shipping_from, $this->domestic_but_international_tracking, true ) ? 80 : 65;
			},

			// Long mail format (22 digits).
			'/^\d{22}$/'                => 60,
		);

		$match           = false;
		$ambiguity_score = 0;

		foreach ( $patterns as $pattern => $score ) {
			if ( preg_match( $pattern, $tracking_number ) ) {
				$match           = true;
				$ambiguity_score = is_callable( $score ) ? $score() : $score;
				break;
			}
		}

		// Boost score for domestic-but-international-tracking countries.
		if ( $match && $is_domestic_shipping && in_array( $shipping_from, $this->domestic_but_international_tracking, true ) ) {
			$ambiguity_score += 5;
		}

		return $match ? array(
			'url'             => $this->get_tracking_url( $tracking_number ),
			'ambiguity_score' => $ambiguity_score,
		) : null;
	}
}
