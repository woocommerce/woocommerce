<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\Fulfillments\Providers;

/**
 * FedEx Shipping Provider class.
 */
class FedExShippingProvider extends AbstractShippingProvider {
	/**
	 * Get the key of the shipping provider.
	 */
	public function get_key(): string {
		return 'fedex';
	}

	/**
	 * Get the name of the shipping provider.
	 */
	public function get_name(): string {
		return 'FedEx';
	}

	/**
	 * Get the icon of the shipping provider.
	 */
	public function get_icon(): string {
		return esc_url( WC()->plugin_url() ) . '/assets/images/shipping_providers/fedex.png';
	}

	/**
	 * Get the tracking URL for a given tracking number.
	 *
	 * @param string $tracking_number The tracking number.
	 *
	 * @return string The tracking URL.
	 */
	public function get_tracking_url( string $tracking_number ): string {
		return 'https://www.fedex.com/fedextrack/?tracknumbers=' . rawurlencode( $tracking_number );
	}

	/**
	 * Get the countries from which this provider can ship.
	 */
	public function get_shipping_from_countries(): array {
		// FedEx ships globally.
		return array( 'US', 'CA', 'GB', 'DE', 'FR', 'AU', 'JP', 'MX', 'CN', 'IN' );
	}

	/**
	 * Get the countries to which this provider can ship.
	 */
	public function get_shipping_to_countries(): array {
		return $this->get_shipping_from_countries();
	}

	/**
	 * Check if this provider can ship from a specific country.
	 *
	 * @param string $shipping_from The country code from which the shipment is sent.
	 * @param string $shipping_to   The country code to which the shipment is sent.
	 *
	 * @return bool True if this provider can ship from the country, false otherwise.
	 */
	public function can_ship_from_to( string $shipping_from, string $shipping_to ): bool {
		return in_array( $shipping_from, $this->get_shipping_from_countries(), true ) &&
				in_array( $shipping_to, $this->get_shipping_to_countries(), true );
	}

	/**
	 * Try to parse the tracking number with additional parameters.
	 *
	 * @param string $tracking_number The tracking number to parse.
	 * @param string $shipping_from   The country code from which the shipment is sent.
	 * @param string $shipping_to     The country code to which the shipment is sent.
	 *
	 * @return array|null Returns an array with 'url' and 'ambiguity_score' if the tracking number is valid, null otherwise.
	 */
	public function try_parse_tracking_number( string $tracking_number, string $shipping_from, string $shipping_to ): ?array {
		if ( empty( $tracking_number ) || empty( $shipping_from ) || empty( $shipping_to ) || ! $this->can_ship_from_to( $shipping_from, $shipping_to ) ) {
			return null;
		}

		$tracking_number = strtoupper( $tracking_number );

		$is_12_digit = preg_match( '/^\d{12}$/', $tracking_number );
		$is_15_digit = preg_match( '/^\d{15}$/', $tracking_number );
		$is_20_digit = preg_match( '/^\d{20}$/', $tracking_number );

		$match = false;

		if ( $is_12_digit ) {
			$match           = true;
			$ambiguity_score = 100; // Most common and unique to FedEx.
		} elseif ( $is_15_digit ) {
			$match           = true;
			$ambiguity_score = 85; // Less common but still used.
		} elseif ( $is_20_digit ) {
			$match           = true;
			$ambiguity_score = 60; // Shared by other services.
		}

		return $match ? array(
			'url'             => $this->get_tracking_url( $tracking_number ),
			'ambiguity_score' => $ambiguity_score,
		) : null;
	}
}
