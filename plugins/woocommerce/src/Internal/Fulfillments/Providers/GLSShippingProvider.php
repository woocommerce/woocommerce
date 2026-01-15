<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\Fulfillments\Providers;

/**
 * GLS Shipping Provider class.
 */
class GLSShippingProvider extends AbstractShippingProvider {
	/**
	 * Get the key of the shipping provider.
	 *
	 * @return string
	 */
	public function get_key(): string {
		return 'gls';
	}

	/**
	 * Get the name of the shipping provider.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'GLS';
	}

	/**
	 * Get the icon of the shipping provider.
	 *
	 * @return string
	 */
	public function get_icon(): string {
		return esc_url( WC()->plugin_url() ) . '/assets/images/shipping_providers/gls.png';
	}

	/**
	 * Get the tracking URL for a given tracking number.
	 *
	 * @param string $tracking_number The tracking number.
	 * @return string The tracking URL.
	 */
	public function get_tracking_url( string $tracking_number ): string {
		return 'https://gls-group.eu/EU/en/parcel-tracking/' . $tracking_number;
	}
}
