<?php
/**
 * Title: Hero Product Split
 * Slug: woocommerce-blocks/hero-product-split
 * Categories: WooCommerce
 */

use Automattic\WooCommerce\Blocks\Patterns\PatternsHelper;
$content = PatternsHelper::get_pattern_content( 'woocommerce-blocks/hero-product-split' );
$images  = PatternsHelper::get_pattern_images( 'woocommerce-blocks/hero-product-split' );
?>

<!-- wp:media-text {"align":"full","mediaPosition":"right","mediaType":"image","mediaSizeSlug":"full","imageFill":false} -->
<div class="wp-block-media-text alignfull has-media-on-the-right is-stacked-on-mobile">
	<div class="wp-block-media-text__content">
		<!-- wp:heading -->
		<h2 class="wp-block-heading"><?php echo esc_html( $content['titles'][0]['default'] ); ?></h2>
		<!-- /wp:heading -->

		<!-- wp:buttons {"style":{"spacing":{"margin":{"bottom":"var:preset|spacing|40"}}}} -->
		<div class="wp-block-buttons" style="margin-bottom:var(--wp--preset--spacing--40)">
		<!-- wp:button -->
		<div class="wp-block-button">
			<a href="<?php echo esc_url( get_permalink( wc_get_page_id( 'shop' ) ) ); ?>" class="wp-block-button__link wp-element-button"><?php esc_html_e( 'Shop now', 'woo-gutenberg-products-block' ); ?></a>
		</div>
		<!-- /wp:button -->
		</div>
		<!-- /wp:buttons -->
	</div>

	<figure class="wp-block-media-text__media">
		<img src="<?php echo esc_url( PatternsHelper::get_image_url( $images, 0, 'images/pattern-placeholders/pattern-fashion-clothing-outerwear-wool-scarf.png' ) ); ?>" alt="<?php esc_attr_e( 'Placeholder image used to represent a product being showcased in a hero section.', 'woo-gutenberg-products-block' ); ?>" />
	</figure>
</div>
<!-- /wp:media-text -->
