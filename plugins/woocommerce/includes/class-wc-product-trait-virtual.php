<?php
/**
 * Virtual product trait.
 *
 * @package WooCommerce\Classes\Products
 */

defined( 'ABSPATH' ) || exit;

/**
 * Virtual product trait class.
 */
class WC_Product_Trait_Virtual extends WC_Product_Trait {

	/**
	 * Get the name.
	 *
	 * @return string
	 */
	public static function get_name() {
		return 'Virtual';
	}

    /**
	 * Get the slug.
	 *
	 * @return string
	 */
	public static function get_slug() {
		return 'virtual';
	}

}
