<?php
/**
 * WooCommerce Product Traits
 *
 * Loads the product traits via hooks for use in the store.
 *
 * @version 2.2.0
 * @package WooCommerce\Classes\Products
 */

defined( 'ABSPATH' ) || exit;

/**
 * Product traits class.
 */
class WC_Product_Traits {

	/**
	 * Product traits classes.
	 *
	 * @var array
	 */
	public $product_traits = array();

	/**
	 * The single instance of the class.
	 *
	 * @var WC_Product_Traits
	 */
	protected static $_instance = null;

	/**
	 * Main WC_Product_Traits Instance.
	 *
	 * @return WC_Product_Traits Main instance
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Initialize product traits.
	 */
	public function __construct() {
		$this->init();
	}

    /**
	 * Default traits for all products.
	 *
	 * @var array
	 */
	public function get_simple_product_traits() {
        return apply_filters(
            'woocommerce_simple_product_traits',
            array(
                WC_Product_Trait_Downloadable::get_slug(),
                WC_Product_Trait_Shippable::get_slug(),
                WC_Product_Trait_Virtual::get_slug(),
            )
        );
    }

	/**
	 * Load gateways and hook in functions.
	 */
	public function init() {
		$product_traits = array(
			'WC_Product_Trait_Downloadable',
			'WC_Product_Trait_Shippable',
			'WC_Product_Trait_Virtual',
		);

		$product_traits = apply_filters( 'woocommerce_product_traits', $product_traits );

		foreach ( $product_traits as $trait ) {
			if ( is_string( $trait ) && class_exists( $trait ) ) {
				$this->product_traits[ $trait::get_slug() ] = $trait;
			}
        }
	}

    /**
     * Get a trait by slug.
     *
     * @param string $slug Trait slug.
     * @return WC_Product_Trait
     */
    public function get_trait( $slug ) {
        if ( isset( $this->product_traits[ $slug ] ) ) {
            return $this->product_traits[ $slug ];
        }

        return null;
    }

	/**
	 * Get all product traits.
	 *
	 * @return array
	 */
	public function get_all_traits() {
		return $this->product_traits;
	}

}
