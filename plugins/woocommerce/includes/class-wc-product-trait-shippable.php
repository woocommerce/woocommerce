<?php
/**
 * Shippable product trait.
 *
 * @package WooCommerce\Classes\Products
 */

defined( 'ABSPATH' ) || exit;

/**
 * Shippable product trait class.
 */
class WC_Product_Trait_Shippable extends WC_Product_Trait {

	/**
	 * Get the name.
	 *
	 * @return string
	 */
	public static function get_name() {
		return 'Shippable';
	}

    /**
	 * Get the slug.
	 *
	 * @return string
	 */
	public static function get_slug() {
		return 'shippable';
	}

	/**
	 * Get compatible traits.
	 */
	public static function get_compatible_traits() {
		return array_diff(
			array_keys( WC()->product_traits()->get_all_traits() ),
			array( 'virtual' )
		);
	}

}
