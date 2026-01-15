<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\Fulfillments\Providers;

/**
 * Abstract class for shipping providers.
 *
 * This class defines the basic structure and methods that all shipping providers must implement.
 */
abstract class AbstractShippingProvider {
	/**
	 * Get the key of the shipping provider.
	 *
	 * @return string
	 */
	abstract public function get_key(): string;

	/**
	 * Get the name of the shipping provider.
	 *
	 * @return string
	 */
	abstract public function get_name(): string;

	/**
	 * Get the path of the icon of the shipping provider.
	 *
	 * @return string
	 */
	abstract public function get_icon(): string;

	/**
	 * Get the tracking URL for a given tracking number.
	 *
	 * @param string $tracking_number The tracking number.
	 * @return string The tracking URL.
	 */
	abstract public function get_tracking_url( string $tracking_number ): string;

	/**
	 * Get the countries from which the shipping provider can ship.
	 *
	 * @return array An array of country codes.
	 */
	public function get_shipping_from_countries(): array {
		return array();
	}

	/**
	 * Get the countries to which the shipping provider can ship.
	 *
	 * @return array An array of country codes.
	 */
	public function get_shipping_to_countries(): array {
		return array();
	}

	/**
	 * Check if the shipping provider can ship from a specific country.
	 *
	 * @param string $country_code The country code to check.
	 * @return bool True if the provider can ship from the country, false otherwise.
	 */
	public function can_ship_from( string $country_code ): bool {
		return in_array( $country_code, $this->get_shipping_from_countries(), true );
	}

	/**
	 * Check if the shipping provider can ship to a specific country.
	 *
	 * @param string $country_code The country code to check.
	 * @return bool True if the provider can ship to the country, false otherwise.
	 */
	public function can_ship_to( string $country_code ): bool {
		return in_array( $country_code, $this->get_shipping_to_countries(), true );
	}

	/**
	 * Check if the shipping provider can ship from a specific country to another.
	 *
	 * @param string $shipping_from The country code from which the shipment is sent.
	 * @param string $shipping_to The country code to which the shipment is sent.
	 * @return bool True if the provider can ship from the source to the destination, false otherwise.
	 */
	public function can_ship_from_to( string $shipping_from, string $shipping_to ): bool {
		return $this->can_ship_from( $shipping_from ) && $this->can_ship_to( $shipping_to );
	}

	/**
	 * Get the tracking URL for a given tracking number with additional parameters.
	 *
	 * @param string $tracking_number The tracking number.
	 * @param string $shipping_from The country code from which the shipment is sent.
	 * @param string $shipping_to The country code to which the shipment is sent.
	 *
	 * @return array|null The tracking URL with ambiguity score, or null if parsing fails.
	 *
	 * phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter
	 */
	public function try_parse_tracking_number( string $tracking_number, string $shipping_from, string $shipping_to ): ?array {
		return null; // Default implementation returns null, subclasses should override this method.
	}
}
