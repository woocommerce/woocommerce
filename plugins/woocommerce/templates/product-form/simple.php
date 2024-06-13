<?php
/**
 * Simple Product Form
 *
 * Title: Simple
 * Slug: simple
 * Description: A single physical or virtual product, e.g. a t-shirt or an eBook
 * Product Types: simple, variable
 *
 * @package WooCommerce\Templates
 * @version 9.1.0-beta.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<!-- wp:woocommerce/product-section {"title":"<?php esc_attr_e( 'Basic details', 'woocommerce' ); ?>"} -->
<div data-block-name="woocommerce/product-section" class="wp-block-woocommerce-product-section" data-title="<?php esc_attr_e( 'Basic details', 'woocommerce' ); ?>">
	<div>
		<!-- wp:woocommerce/product-regular-price-field -->
		<div data-block-name="woocommerce/product-regular-price-field" class="wp-block-woocommerce-product-regular-price-field"></div>
		<!-- /wp:woocommerce/product-regular-price-field -->

		<!-- wp:woocommerce/product-checkbox-field {"label":"<?php esc_attr_e( 'Translatable Label', 'woocommerce' ); ?>","property":"testproperty"} -->
		<div data-block-name="woocommerce/product-checkbox-field" class="wp-block-woocommerce-product-checkbox-field" data-label="<?php esc_attr_e( 'Translatable Label', 'woocommerce' ); ?>"></div>
		<!-- /wp:woocommerce/product-checkbox-field -->
	</div>
</div>
<!-- /wp:woocommerce/product-section -->
