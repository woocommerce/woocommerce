<?php
/**
 * WooCommerce Modular Products
 */

namespace Automattic\WooCommerce\Admin\Features;

/**
 * Modular products.
 */
class ModularProducts {

	/**
	 * Hook into WooCommerce.
	 */
	public function __construct() {
        add_filter( 'woocommerce_product_class', array( $this, 'maybe_revert_to_simple_product' ) );
	}

    /**
     * Revert to the simple product class in legacy experiences.
     *
     * @param string $classname Classname.
     * @return string Classname.
     */
    public function maybe_revert_to_simple_product( $classname ) {
        if ( isset( $_GET['action'] ) && 'edit' === $_GET['action'] ) {
            return 'WC_Product_Simple';
        }

        return $classname;
    }

}
