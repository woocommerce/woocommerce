<?php
/**
 * WooCommerce product trait base class.
 *
 * @package WooCommerce\Abstracts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Abstract Product Trait Class
 *
 * The WooCommerce product class handles individual product data.
 *
 * @package WooCommerce\Abstracts
 */
abstract class WC_Product_Trait {

	/**
	 * Constructor
	 */
	public function __construct() {}

	/**
	 * Get product name.
	 *
	 * @return string
	 */
	abstract public function get_name();

	/**
	 * Get product trait slug.
	 *
	 * @return string
	 */
	abstract public function get_slug();

	/**
	 * Get compatible traits.
	 *
	 * @return array
	 */
	public function get_compatible_traits() {
		return WC()->product_traits()->get_all_traits();
	}

	/**
	 * Get incompatible traits.
	 *
	 * @return array
	 */
	public function get_incompatible_traits() {
		return array_diff(
			array_keys( WC()->product_traits()->get_all_traits() ),
			$this->get_compatible_traits(),
		);
	}

}
