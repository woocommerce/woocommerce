<?php
/**
 * Title: Hero Product Split
 * Slug: woocommerce-blocks/hero-product-split
 * Categories: WooCommerce, Intro
 */


$hero_title = __( 'Keep dry with 50% off rain jackets', 'woocommerce' );
?>

<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"calc( 0.5 * var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal)))","bottom":"calc( 0.5 * var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal)))","left":"var(--wp--style--root--padding-left, var(--wp--custom--gap--horizontal))","right":"var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal))"},"margin":{"top":"0","bottom":"0"}}},"layout":{"type":"constrained","justifyContent":"center"}} -->
<div class="wp-block-group alignfull" style="margin-top:0;margin-bottom:0;padding-top:calc( 0.5 * var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal)));padding-right:var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal));padding-bottom:calc( 0.5 * var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal)));padding-left:var(--wp--style--root--padding-left, var(--wp--custom--gap--horizontal))">
	<!-- wp:spacer {"height":"calc( 0.25 * var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal)))"} -->
	<div style="height:calc( 0.25 * var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal)))" aria-hidden="true" class="wp-block-spacer"></div>
	<!-- /wp:spacer -->

	<!-- wp:media-text {"align":"full","mediaPosition":"right","mediaType":"image","mediaSizeSlug":"full","imageFill":false} -->
	<div class="wp-block-media-text alignfull has-media-on-the-right is-stacked-on-mobile">
		<div class="wp-block-media-text__content">
			<!-- wp:heading {"level":3} -->
			<h3 class="wp-block-heading"><?php echo esc_html( $hero_title ); ?></h3>
			<!-- /wp:heading -->

			<!-- wp:buttons {"style":{"spacing":{"margin":{"bottom":"var:preset|spacing|40"}}}} -->
			<div class="wp-block-buttons" style="margin-bottom:var(--wp--preset--spacing--40)">
				<!-- wp:button -->
				<div class="wp-block-button">
					<a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" class="wp-block-button__link wp-element-button"><?php esc_html_e( 'Shop the sale', 'woocommerce' ); ?></a>
				</div>
				<!-- /wp:button -->
			</div>
			<!-- /wp:buttons -->
		</div>

		<figure class="wp-block-media-text__media">
			<img src="<?php echo esc_url( plugins_url( 'assets/images/pattern-placeholders/man-person-winter-photography-statue-coat.png', WC_PLUGIN_FILE ) ); ?>" alt="<?php esc_attr_e( 'Placeholder image used to represent a product being showcased in a hero section.', 'woocommerce' ); ?>" />
		</figure>
	</div>
	<!-- /wp:media-text -->

	<!-- wp:spacer {"height":"calc( 0.25 * var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal)))"} -->
	<div style="height:calc( 0.25 * var(--wp--style--root--padding-right, var(--wp--custom--gap--horizontal)))" aria-hidden="true" class="wp-block-spacer"></div>
	<!-- /wp:spacer --></div>
<!-- /wp:group -->
