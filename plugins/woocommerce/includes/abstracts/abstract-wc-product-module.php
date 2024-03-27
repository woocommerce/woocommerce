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
	 * Stores product data.
	 *
	 * @var array
	 */
	protected $extra_data = array();

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
	 * Get pass through methods.
	 *
	 * @return string
	 */
	public static function get_passthrough_methods() {
		return array();
	}

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

	/**
	 * Get extra store data.
	 *
	 * @return array
	 */
	public function get_extra_data() {
		return $this->extra_data;
	}

	/**
	 * Check if this module is active in the product.
	 *
	 * @return bool
	 */
	private function is_active() {
		$this->product->is_module_active( self::get_slug() );
	}

	public static function add_hooks() {}

}
