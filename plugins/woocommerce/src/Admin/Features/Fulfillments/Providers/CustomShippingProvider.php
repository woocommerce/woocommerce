<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Admin\Features\Fulfillments\Providers;

/**
 * Custom shipping provider loaded from the wc_fulfillment_shipping_provider taxonomy.
 *
 * Unlike built-in providers, custom providers do not support automatic tracking
 * number parsing; they rely on user-supplied tracking URL templates.
 *
 * @since 10.7.0
 */
class CustomShippingProvider extends AbstractShippingProvider {

	/**
	 * The provider key (taxonomy term slug).
	 *
	 * @var string
	 */
	private string $key;

	/**
	 * The provider display name.
	 *
	 * @var string
	 */
	private string $name;

	/**
	 * The provider icon URL.
	 *
	 * @var string
	 */
	private string $icon;

	/**
	 * The tracking URL template containing __PLACEHOLDER__ for the tracking number.
	 *
	 * @var string
	 */
	private string $tracking_url_template;

	/**
	 * Constructor.
	 *
	 * @param string $key                  The provider key (term slug).
	 * @param string $name                 The provider display name.
	 * @param string $icon                 The provider icon URL.
	 * @param string $tracking_url_template The tracking URL template.
	 */
	public function __construct( string $key, string $name, string $icon, string $tracking_url_template ) {
		$this->key                   = $key;
		$this->name                  = $name;
		$this->icon                  = $icon;
		$this->tracking_url_template = $tracking_url_template;
	}

	/**
	 * Get the key of the shipping provider.
	 *
	 * @return string
	 */
	public function get_key(): string {
		return $this->key;
	}

	/**
	 * Get the name of the shipping provider.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return $this->name;
	}

	/**
	 * Get the icon URL of the shipping provider.
	 *
	 * @return string
	 */
	public function get_icon(): string {
		return $this->icon;
	}

	/**
	 * Get the tracking URL for a given tracking number.
	 *
	 * Replaces __PLACEHOLDER__ in the template with the actual tracking number.
	 *
	 * @param string $tracking_number The tracking number.
	 * @return string The tracking URL with the placeholder replaced.
	 */
	public function get_tracking_url( string $tracking_number ): string {
		if ( empty( $this->tracking_url_template ) ) {
			return '';
		}

		return str_replace( '__PLACEHOLDER__', rawurlencode( $tracking_number ), $this->tracking_url_template );
	}

	/**
	 * Custom providers do not support automatic tracking number parsing.
	 *
	 * @param string $tracking_number The tracking number.
	 * @param string $shipping_from The country code from which the shipment is sent.
	 * @param string $shipping_to The country code to which the shipment is sent.
	 * @return array|null Always returns null for custom providers.
	 *
	 * phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter
	 */
	public function try_parse_tracking_number( string $tracking_number, string $shipping_from, string $shipping_to ): ?array {
		return null;
	}
}
