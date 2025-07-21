<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\Fulfillments\Helpers;

use Automattic\WooCommerce\Internal\Fulfillments\Providers\AbstractShippingProvider;

/**
 * ShippingProviderMock class.
 *
 * This class is a mock implementation of the AbstractShippingProvider for testing purposes.
 *
 * @since 10.1.0
 * @package WooCommerce\Tests\Internal\Fulfillments
 */
class ShippingProviderMock extends AbstractShippingProvider {
	/**
	 * Get the key of the shipping provider.
	 *
	 * @return string
	 */
	public function get_key(): string {
		return 'mock_shipping_provider';
	}

	/**
	 * Get the name of the shipping provider.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'Mock Shipping Provider';
	}

	/**
	 * Get the icon of the shipping provider.
	 *
	 * @return string
	 */
	public function get_icon(): string {
		return 'https://example.com/icon.png';
	}

	/**
	 * Get the tracking URL for a given tracking number.
	 *
	 * @param string $tracking_number The tracking number.
	 * @return string The tracking URL.
	 */
	public function get_tracking_url( string $tracking_number ): string {
		return 'https://example.com/track?number=' . rawurlencode( $tracking_number );
	}
}
