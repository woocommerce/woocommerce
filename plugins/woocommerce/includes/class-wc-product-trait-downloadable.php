<?php
/**
 * Downloadable product trait.
 *
 * @package WooCommerce\Classes\Products
 */

defined( 'ABSPATH' ) || exit;

/**
 * Downloadable product trait class.
 */
class WC_Product_Trait_Downloadable extends WC_Product_Trait {

	/**
	 * Get the name.
	 *
	 * @return string
	 */
	public static function get_name() {
		return 'Downloadable';
	}

    /**
	 * Get the slug.
	 *
	 * @return string
	 */
	public static function get_slug() {
		return 'downloadable';
	}

}
