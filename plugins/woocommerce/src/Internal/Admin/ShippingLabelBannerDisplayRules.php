<?php
/**
 * WooCommerce Shipping Label Banner Display Rules.
 */

namespace Automattic\WooCommerce\Internal\Admin;

/**
 * Determines whether the Shipping Label Banner should be displayed
 */
class ShippingLabelBannerDisplayRules {

	/**
	 * Whether the site is connected to wordpress.com.
	 *
	 * @var bool
	 */
	private $dotcom_connected;

	/**
	 * Whether installed plugins are incompatible with the banner.
	 *
	 * @var bool
	 */
	private $no_incompatible_plugins_installed;

	/**
	 * Holds the installed WooCommerce Shipping & Tax version.
	 *
	 * @var string
	 */
	private $wcs_version;

	/**
	 * Supported countries by USPS, see: https://webpmt.usps.gov/pmt010.cfm
	 *
	 * @var array
	 */
	private $supported_countries = array( 'US', 'AS', 'PR', 'VI', 'GU', 'MP', 'UM', 'FM', 'MH' );

	/**
	 * Array of supported currency codes.
	 *
	 * @var array
	 */
	private $supported_currencies = array( 'USD' );


	/**
	 * Constructor.
	 *
	 * @param bool        $dotcom_connected Is site connected to wordpress.com?.
	 * @param string|null $wcs_version Installed WooCommerce Shipping version to check, null if not installed.
	 * @param bool        $incompatible_plugins_installed Are there any incompatible plugins installed?.
	 */
	public function __construct( $dotcom_connected, $wcs_version, $incompatible_plugins_installed ) {
		$this->dotcom_connected                  = $dotcom_connected;
		$this->wcs_version                       = $wcs_version;
		$this->no_incompatible_plugins_installed = ! $incompatible_plugins_installed;
	}

	/**
	 * Determines whether banner is eligible for display (does not include a/b logic).
	 */
	public function should_display_banner() {
		return $this->banner_not_dismissed() &&
			$this->dotcom_connected &&
			$this->no_incompatible_plugins_installed &&
			$this->order_has_shippable_products() &&
			$this->store_in_us_and_usd() &&
			$this->wcs_not_installed();
	}

	/**
	 * Checks if the banner was not dismissed by the user.
	 *
	 * @return bool
	 */
	private function banner_not_dismissed() {
		$dismissed_timestamp_ms = get_option( 'woocommerce_shipping_dismissed_timestamp' );

		if ( ! is_numeric( $dismissed_timestamp_ms ) ) {
			return true;
		}
		$dismissed_timestamp_ms = intval( $dismissed_timestamp_ms );
		$dismissed_timestamp    = intval( round( $dismissed_timestamp_ms / 1000 ) );
		$expired_timestamp      = $dismissed_timestamp + 24 * 60 * 60; // 24 hours from click time

		$dismissed_for_good = -1 === $dismissed_timestamp_ms;
		$dismissed_24h      = time() < $expired_timestamp;

		return ! $dismissed_for_good && ! $dismissed_24h;
	}

	/**
	 * Checks if there's a shippable product in the current order.
	 *
	 * @return bool
	 */
	private function order_has_shippable_products() {
		$order = wc_get_order();

		if ( ! $order ) {
			return false;
		}
		// At this point (no packaging data), only show if there's at least one existing and shippable product.
		foreach ( $order->get_items() as $item ) {
			if ( $item instanceof \WC_Order_Item_Product ) {
				$product = $item->get_product();

				if ( $product && $product->needs_shipping() ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Checks if the store is in the US and has its default currency set to USD.
	 *
	 * @return bool
	 */
	private function store_in_us_and_usd() {
		$base_currency = get_woocommerce_currency();
		$base_location = wc_get_base_location();

		return in_array( $base_currency, $this->supported_currencies, true ) && in_array( $base_location['country'], $this->supported_countries, true );
	}

	/**
	 * Checks if WooCommerce Shipping & Tax is not installed.
	 *
	 * @return bool
	 */
	private function wcs_not_installed() {
		return ! $this->wcs_version;
	}
}
