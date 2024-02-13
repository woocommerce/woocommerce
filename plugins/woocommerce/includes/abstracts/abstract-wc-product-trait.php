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
	 * The product.
	 *
	 * @var WC_Product
	 */
	protected $product;

	/**
	 * Constructor
	 */
	public function __construct( $product ) {
		$this->product = $product;
	}

	/**
	 * Get product name.
	 *
	 * @return string
	 */
	abstract public static function get_name();

	/**
	 * Get product trait slug.
	 *
	 * @return string
	 */
	abstract public static function get_slug();

	/**
	 * Get compatible traits.
	 *
	 * @return array
	 */
	public static function get_compatible_traits() {
		return WC()->product_traits()->get_simple_product_traits();
	}

	/**
	 * Get incompatible traits.
	 *
	 * @return array
	 */
	public static function get_incompatible_traits() {
		return array_diff(
			array_keys( WC()->product_traits()->get_all_traits() ),
			self::get_compatible_traits(),
		);
	}

}
