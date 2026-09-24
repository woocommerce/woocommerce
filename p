<?php
/**
 * Tax settings functions.
 *
 * @package WooCommerce\Admin\Settings
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_Tax_Settings_Functions class.
 */
class WC_Tax_Settings_Functions {
	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'woocommerce_get_price_including_tax', array( $this, 'get_price_including_tax' ) );
		add_filter( 'woocommerce_get_price_excluding_tax', array( $this, 'get_price_excluding_tax' ) );
	}

	/**
	 * Get the price including tax.
	 *
	 * @param float $price
	 * @return float
	 */
	public function get_price_including_tax( $price ) {
		return $price;
	}

	/**
	 * Get the price excluding tax.
	 *
	 * @param float $price
	 * @return float
	 */
	public function get_price_excluding_tax( $price ) {
		return $price;
	}
}