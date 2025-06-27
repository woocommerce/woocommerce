<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\Fulfillments\Providers;

/**
 * DHL Shipping Provider class.
 */
class DHLShippingProvider extends AbstractShippingProvider {
	/**
	 * Get the key of the shipping provider.
	 *
	 * @return string
	 */
	public function get_key(): string {
		return 'dhl';
	}

	/**
	 * Get the name of the shipping provider.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'DHL';
	}

	/**
	 * Get the icon of the shipping provider.
	 *
	 * @return string
	 */
	public function get_icon(): string {
		return esc_url( WC()->plugin_url() ) . '/assets/images/shipping_providers/dhl.png';
	}

	/**
	 * Get the tracking URL for a given tracking number.
	 *
	 * @param string $tracking_number The tracking number.
	 * @return string The tracking URL.
	 */
	public function get_tracking_url( string $tracking_number ): string {
		return 'https://www.dhl.com/global-en/home/tracking.html?tracking-id=' . rawurlencode( $tracking_number );
	}

	/**
	 * Get the countries from which this provider can ship.
	 *
	 * @return array
	 */
	public function get_shipping_from_countries(): array {
		return array( 'DE', 'US', 'GB', 'CA', 'SG', 'JP', 'HK', 'NL', 'FR', 'IT' ); // common DHL hubs.
	}

	/**
	 * Get the countries to which this provider can ship.
	 *
	 * @return array
	 */
	public function get_shipping_to_countries(): array {
		return array_keys( wc()->countries->get_countries() );
	}

	/**
	 * Try to parse the tracking number.
	 *
	 * @param string $tracking_number Tracking number to validate.
	 * @param string $shipping_from Origin country.
	 * @param string $shipping_to Destination country.
	 * @return array|null
	 */
	public function try_parse_tracking_number( string $tracking_number, string $shipping_from, string $shipping_to ): ?array {
		if ( empty( $tracking_number ) || empty( $shipping_from ) || empty( $shipping_to ) || ! $this->can_ship_from_to( $shipping_from, $shipping_to ) ) {
			return null;
		}

		$tracking_number = strtoupper( $tracking_number );

		$is_10_digit  = preg_match( '/^\d{10}$/', $tracking_number );
		$is_jjd14     = preg_match( '/^JJD\d{10}$/', $tracking_number );
		$is_jjd14_alt = preg_match( '/^JJD\d{12,14}$/', $tracking_number );

		$match = false;

		if ( $is_10_digit ) {
			$match           = true;
			$ambiguity_score = 80;
		} elseif ( $is_jjd14 || $is_jjd14_alt ) {
			$match           = true;
			$ambiguity_score = 95;
		}

		return $match ? array(
			'url'             => $this->get_tracking_url( $tracking_number ),
			'ambiguity_score' => $ambiguity_score,
		) : null;
	}
}
