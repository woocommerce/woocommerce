<?php
/**
 * Title: Hero Product Split
 * Slug: woocommerce-blocks/hero-product-split
 * Categories: WooCommerce
 */

use Automattic\WooCommerce\Blocks\Patterns\PatternsHelper;

$hero_title = $content['titles'][0]['default'] ?? '';
?>

<!-- wp:media-text {"align":"full","mediaPosition":"right","mediaType":"image","mediaSizeSlug":"full","imageFill":false,"style":{"spacing":{"margin":{"bottom":"80px"}}}} -->
<div class="wp-block-media-text alignfull has-media-on-the-right is-stacked-on-mobile" style="margin-bottom:80px">
	<div class="wp-block-media-text__content">
		<!-- wp:heading {"level":3} -->
		<h3 class="wp-block-heading"><?php echo esc_html( $hero_title ); ?></h3>
		<!-- /wp:heading -->

		<!-- wp:buttons {"style":{"spacing":{"margin":{"bottom":"var:preset|spacing|40"}}}} -->
		<div class="wp-block-buttons" style="margin-bottom:var(--wp--preset--spacing--40)">
		<!-- wp:button -->
		<div class="wp-block-button">
			<a href="<?php echo esc_url( get_permalink( wc_get_page_id( 'shop' ) ) ); ?>" class="wp-block-button__link wp-element-button"><?php esc_html_e( 'Shop the sale', 'woocommerce' ); ?></a>
		</div>
		<!-- /wp:button -->
		</div>
		<!-- /wp:buttons -->
	</div>

	<figure class="wp-block-media-text__media">
		<img src="<?php echo esc_url( PatternsHelper::get_image_url( $images, 0, 'assets/images/pattern-placeholders/man-person-winter-photography-statue-coat.png' ) ); ?>" alt="<?php esc_attr_e( 'Placeholder image used to represent a product being showcased in a hero section.', 'woocommerce' ); ?>" />
	</figure>
</div>
<!-- /wp:media-text -->
