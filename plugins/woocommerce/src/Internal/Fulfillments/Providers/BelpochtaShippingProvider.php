<?php declare(strict_types=1);

namespace WooCommerce\Internal\Fulfillments\Providers;

/**
 * Belpochta Shipping Provider class.
 */
class BelpochtaShippingProvider extends AbstractShippingProvider {
	/**
	 * List of international shipping countries.
	 *
	 * @var array
	 */
	public array $international_shipping_countries = array();

	/**
	 * List of domestic shipping countries.
	 *
	 * @var array
	 */
	public array $domestic_shipping_countries = array();

	/**
	 * Get the key of the shipping provider.
	 *
	 * @return string
	 */
	public function get_key(): string {
		return 'belpochta';
	}

	/**
	 * Get the name of the shipping provider.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'Belpochta';
	}

	/**
	 * Get the icon of the shipping provider.
	 *
	 * @return string
	 */
	public function get_icon(): string {
		return __DIR__ . '../../../belpochta.png';
	}

	/**
	 * Get the tracking URL for a given tracking number.
	 *
	 * @param string $tracking_number The tracking number.
	 * @return string The tracking URL.
	 */
	public function get_tracking_url( string $tracking_number ): string {
		return 'https://www.belpost.by/track/' . $tracking_number;
	}

	/**
	 * Get the countries from which UPS can ship.
	 *
	 * @return array An array of country codes.
	 */
	public function get_shipping_from_countries(): array {
		return $this->international_shipping_countries;
	}

	/**
	 * Get the countries to which UPS can ship.
	 *
	 * @return array An array of country codes.
	 */
	public function get_shipping_to_countries(): array {
		return $this->international_shipping_countries;
	}

	/**
	 * Check if UPS can ship from a specific country.
	 *
	 * @param string $shipping_from The country code from which the shipment is sent.
	 * @param string $shipping_to The country code to which the shipment is sent.
	 *
	 * @return bool True if UPS can ship from the country, false otherwise.
	 */
	public function can_ship_from_to( string $shipping_from, string $shipping_to ): bool {
		return true;
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
		return null;
	}
}
