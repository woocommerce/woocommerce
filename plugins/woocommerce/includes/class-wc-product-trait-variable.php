<?php
/**
 * Variable product trait.
 *
 * @package WooCommerce\Classes\Products
 */

defined( 'ABSPATH' ) || exit;

/**
 * Variable product trait class.
 */
class WC_Product_Trait_Variable extends WC_Product_Trait {

	/**
	 * Get the name.
	 *
	 * @return string
	 */
	public static function get_name() {
		return __( 'Variable', 'woocommerce' );
	}

    /**
	 * Get the slug.
	 *
	 * @return string
	 */
	public static function get_slug() {
		return 'variable';
	}

	/**
	 * Get the deprecated product type.
	 */
	public static function get_deprecated_product_type() {
		return 'variable';
	}

	/**
	 * Get compatible traits.
	 */
	public static function get_compatible_traits() {
		return array_diff(
			array_keys( WC()->product_traits()->get_all_traits() ),
			array(
                'downloadable',
                'pricing',
                'virtual'
            )
		);
	}

	public function get_something() {
		return $this->product->get_name();
	}

	/**
	 * Add hooks for this trait.
	 */
	public static function add_hooks() {
		add_filter( 'woocommerce_product_add_to_cart_text', array( __CLASS__, 'add_to_cart_text' ), 10, 2 );
	}

	/**
	 * Get the add to cart button text.
	 *
	 * @param string     $text Add to cart text.
	 * @param WC_Product $this Product
	 * @return string
	 */
	public static function add_to_cart_text( $text, $product ) {
		if ( $product->has_trait( 'variable' ) ) {
			return $product->is_purchasable() ? __( 'Select options', 'woocommerce' ) : __( 'Read more', 'woocommerce' );
		}

		return $text;
	}

}
