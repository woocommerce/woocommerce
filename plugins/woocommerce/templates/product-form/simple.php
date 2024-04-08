<?php
/**
 * Simple Product Form
 *
 * Title: Simple
 * Slug: simple
 * Description: This is the template description.
 * Product Types: simple, variable
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!-- wp:woocommerce/product-section {"title":"<?php _e( 'Basic details', 'woocommerce' ); ?>"} -->
<div data-block-name="woocommerce/product-section" class="wp-block-woocommerce-product-section" data-title="<?php _e( 'Basic details', 'woocommerce' ); ?>">
    <div>
        <!-- wp:woocommerce/product-regular-price-field -->
        <div data-block-name="woocommerce/product-regular-price-field" class="wp-block-woocommerce-product-regular-price-field"></div>
        <!-- /wp:woocommerce/product-regular-price-field -->
        <!-- wp:woocommerce/product-checkbox-field {"label":"<?php _e( 'Translatable Label', 'woocommerce' ); ?>","property":"testproperty"} -->
        <div data-block-name="woocommerce/product-checkbox-field" class="wp-block-woocommerce-product-checkbox-field" data-label="<?php _e( 'Translatable Label', 'woocommerce' ); ?>"></div>
        <!-- /wp:woocommerce/product-checkbox-field -->
    </div>
</div>
<!-- /wp:woocommerce/product-section -->
