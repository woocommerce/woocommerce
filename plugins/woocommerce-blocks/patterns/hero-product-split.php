<?php
/**
 * Title: Hero Product - Split
 * Slug: woocommerce-blocks/hero-product-split
 * Categories: WooCommerce
 */
?>

<!-- wp:media-text {"align":"full","mediaPosition":"right","mediaType":"image","mediaSizeSlug":"full","imageFill":false,"style":{"color":{"background":"#000000","text":"#ffffff"}}} -->
<div class="wp-block-media-text alignfull has-media-on-the-right is-stacked-on-mobile has-text-color has-background" style="color:#ffffff;background-color:#000000">
	<div class="wp-block-media-text__content">
		<!-- wp:heading {"style":{"color":{"text":"#ffffff"}}} -->
		<h2 class="wp-block-heading has-text-color" style="color:#ffffff;"><?php esc_html_e( 'Get cozy this fall with knit sweaters', 'woo-gutenberg-products-block' ); ?></h2>
		<!-- /wp:heading -->

		<!-- wp:buttons {"style":{"spacing":{"margin":{"bottom":"var:preset|spacing|40"}}}} -->
		<div class="wp-block-buttons" style="margin-bottom:var(--wp--preset--spacing--40)">
		<!-- wp:button {"style":{"color":{"text":"#000000","background":"#ffffff"}}} -->
		<div class="wp-block-button">
			<a href="<?php echo esc_url( get_permalink( wc_get_page_id( 'shop' ) ) ); ?>" class="wp-block-button__link has-text-color has-background wp-element-button" style="color:#000000;background-color:#ffffff;"><?php esc_html_e( 'Shop now', 'woo-gutenberg-products-block' ); ?></a>
		</div>
		<!-- /wp:button -->
		</div>
		<!-- /wp:buttons -->
	</div>

	<figure class="wp-block-media-text__media">
		<img src="<?php echo esc_url( plugins_url( 'images/pattern-placeholders/pattern-fashion-clothing-outerwear-wool-scarf.png', dirname( __FILE__ ) ) ); ?>" alt="<?php esc_attr_e( 'Placeholder image used to represent a product being showcased in a hero section.', 'woo-gutenberg-products-block' ); ?>" />
	</figure>
</div>
<!-- /wp:media-text -->
