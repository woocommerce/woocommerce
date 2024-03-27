<?php
/**
 * WooCommerce Product Modules
 *
 * Loads the product modules via hooks for use in the store.
 *
 * @version 2.2.0
 * @package WooCommerce\Classes\Products
 */

defined( 'ABSPATH' ) || exit;

/**
 * Product modules class.
 */
class WC_Product_Modules {

	/**
	 * Product modules classes.
	 *
	 * @var array
	 */
	public $product_modules = array();

	/**
	 * The single instance of the class.
	 *
	 * @var WC_Product_modules
	 */
	protected static $_instance = null;

	/**
	 * Main WC_Product_modules Instance.
	 *
	 * @return WC_Product_modules Main instance
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Initialize product modules.
	 */
	public function __construct() {
		$this->init();
	}

    /**
	 * Default modules for all products.
	 *
	 * @var array
	 */
	public function get_base_product_modules() {
        return apply_filters(
            'woocommerce_base_product_modules',
            array(
                WC_Product_Module_Downloadable::get_slug(),
                WC_Product_Module_Shippable::get_slug(),
                WC_Product_Module_Virtual::get_slug(),
            )
        );
    }

	/**
	 * Load gateways and hook in functions.
	 */
	public function init() {
		include_once WC_ABSPATH . 'includes/products/modules/class-wc-product-module-variable.php';

		$product_modules = array(
            WC_Product_Module_Variable::class,
		);

		$product_modules = apply_filters( 'woocommerce_product_modules', $product_modules );

		foreach ( $product_modules as $module ) {
			if ( is_string( $module ) && class_exists( $module ) ) {
				$module::init();
				$this->product_modules[ $module::get_slug() ] = $module;
			}
        }
	}

    /**
     * Get a module by slug.
     *
     * @param string $slug Module slug.
     * @return WC_Product_Module
     */
    public function get_module( $slug ) {
        if ( isset( $this->product_modules[ $slug ] ) ) {
            return $this->product_modules[ $slug ];
        }

        return null;
    }

	/**
	 * Get all product modules.
	 *
	 * @return array
	 */
	public function get_all_modules() {
		return $this->product_modules;
	}

}