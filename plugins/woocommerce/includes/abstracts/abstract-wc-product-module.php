<?php
/**
 * WooCommerce product module base class.
 *
 * @package WooCommerce\Abstracts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Abstract Product Module Class
 *
 * The WooCommerce product class handles individual product data.
 *
 * @package WooCommerce\Abstracts
 */
abstract class WC_Product_Module {

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
	 * Get product module slug.
	 *
	 * @return string
	 */
	abstract public static function get_slug();

	/**
	 * Get the product property that determines if this module is enabled or not.
	 * Defaults to the module slug.
	 *
	 * @return string
	 */
	public static function get_product_property() {
		return static::get_slug();
	}

	/**
	 * Get the value the product property should be when enabled.
	 *
	 * @return string
	 */
	public static function get_product_property_enabled_value() {
		return 'yes';
	}

	/**
	 * Get compatible modules.
	 *
	 * @return array
	 */
	public static function get_compatible_modules() {
		return WC()->product_modules()->get_simple_product_modules();
	}

	/**
	 * Get incompatible modules.
	 *
	 * @return array
	 */
	public static function get_incompatible_modules() {
		return array_diff(
			array_keys( WC()->product_modules()->get_all_modules() ),
			static::get_compatible_modules(),
		);
	}

	public static function add_hooks() {}

}
