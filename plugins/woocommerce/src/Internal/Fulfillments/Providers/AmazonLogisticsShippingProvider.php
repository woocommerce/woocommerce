<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\Fulfillments\Providers;

/**
 * Amazon Logistics Shipping Provider class.
 */
class AmazonLogisticsShippingProvider extends AbstractShippingProvider {
	/**
	 * Get the key of the shipping provider.
	 *
	 * @return string
	 */
	public function get_key(): string {
		return 'amazon-logistics';
	}

	/**
	 * Get the name of the shipping provider.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'Amazon Logistics';
	}

	/**
	 * Get the icon of the shipping provider.
	 *
	 * @return string
	 */
	public function get_icon(): string {
		return esc_url( WC()->plugin_url() ) . '/assets/images/shipping_providers/amazon-logistics.png';
	}

	/**
	 * Get the countries from which the shipping provider can ship.
	 *
	 * @return array An array of country codes.
	 */
	public function get_shipping_from_countries(): array {
		return array( 'US', 'CA', 'GB', 'DE', 'IT', 'IN', 'MX' );
	}

	/**
	 * Get the countries to which the shipping provider can ship.
	 *
	 * @return array An array of country codes.
	 */
	public function get_shipping_to_countries(): array {
		return $this->get_shipping_from_countries();
	}

	/**
	 * Check if the shipping provider can ship from a specific country to another.
	 *
	 * @param string $shipping_from The country code from which the shipment is sent.
	 * @param string $shipping_to The country code to which the shipment is sent.
	 * @return bool True if the provider can ship from the source to the destination, false otherwise.
	 */
	public function can_ship_from_to( string $shipping_from, string $shipping_to ): bool {
		return in_array( $shipping_from, $this->get_shipping_from_countries(), true ) &&
				in_array( $shipping_to, $this->get_shipping_to_countries(), true );
	}

	/**
	 * Get the tracking URL for a given tracking number.
	 *
	 * @param string $tracking_number The tracking number.
	 * @return string The tracking URL.
	 */
	public function get_tracking_url( string $tracking_number ): string {
		return 'https://www.amazon.com/progress-tracker/package/ref=ppx_yo_dt_b_track_package_o0?_=' . rawurlencode( $tracking_number );
	}

	/**
	 * Try to parse the tracking number.
	 *
	 * @param string $tracking_number The tracking number to validate.
	 * @param string $shipping_from   The country code from which the shipment is sent.
	 * @param string $shipping_to     The country code to which the shipment is sent.
	 *
	 * @return array|null An array with 'url' and 'ambiguity_score' if valid, null otherwise.
	 */
	public function try_parse_tracking_number( string $tracking_number, string $shipping_from, string $shipping_to ): ?array {
		if ( empty( $tracking_number ) || empty( $shipping_from ) || empty( $shipping_to ) || ! $this->can_ship_from_to( $shipping_from, $shipping_to ) ) {
			return null;
		}

		$tracking_number = strtoupper( $tracking_number );

		$is_tba = preg_match( '/^TBA[0-9A-Z]{10,}$/', $tracking_number );
		$is_tbc = preg_match( '/^TBC[0-9A-Z]{10,}$/', $tracking_number );
		$is_tbm = preg_match( '/^TBM[0-9A-Z]{10,}$/', $tracking_number );

		if ( $is_tba ) {
			$ambiguity_score = 100;
		} elseif ( $is_tbc ) {
			$ambiguity_score = in_array( $shipping_from, array( 'CA', 'GB' ), true ) ? 90 : 60;
		} elseif ( $is_tbm ) {
			$ambiguity_score = in_array( $shipping_from, array( 'IN', 'MX' ), true ) ? 85 : 50;
		} else {
			return null;
		}

		return array(
			'url'             => $this->get_tracking_url( $tracking_number ),
			'ambiguity_score' => $ambiguity_score,
		);
	}
}
