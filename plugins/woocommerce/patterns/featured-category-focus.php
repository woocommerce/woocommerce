<?php
/**
 * Title: Featured Category Focus
 * Slug: woocommerce-blocks/featured-category-focus
 * Categories: WooCommerce
 */

use Automattic\WooCommerce\Blocks\Patterns\PatternsHelper;

$category_title = $content['titles'][0]['default'] ?? '';

$button = $content['buttons'][0]['default'] ?? '';
?>

<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|70","right":"var:preset|spacing|70","bottom":"var:preset|spacing|70","left":"var:preset|spacing|70"}}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"center","flexWrap":"wrap"}} -->
<div class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--70);padding-right:var(--wp--preset--spacing--70);padding-bottom:var(--wp--preset--spacing--70);padding-left:var(--wp--preset--spacing--70)">
	<!-- wp:image {"id":1,"width":"469px","height":"348px","sizeSlug":"full","linkDestination":"none", "scale":"cover"} -->
	<figure class="wp-block-image size-full is-resized">
		<img src="<?php echo esc_url( PatternsHelper::get_image_url( $images, 0, 'assets/images/pattern-placeholders/white-black-black-and-white-photograph-monochrome-photography.jpg' ) ); ?>" alt="<?php esc_attr_e( 'Placeholder image used to represent products being showcased in a featured category section.', 'woocommerce' ); ?>" class="wp-image-1"  style="object-fit:cover;width:469px;height:348px"/>
	</figure>
	<!-- /wp:image -->

	<!-- wp:paragraph {"align":"center","style":{"color":{"text":"#000000"}},"fontSize":"large"} -->
	<p class="has-text-align-center has-text-color has-large-font-size" style="color:#000000"><?php echo esc_html( $category_title ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:buttons -->
	<div class="wp-block-buttons">
		<!-- wp:button {"style":{"color":{"text":"#ffffff","background":"#000000"},"border":{"width":"0px","style":"none"}}} -->
		<div class="wp-block-button">
			<a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" class="wp-block-button__link has-text-color has-background wp-element-button" style="border-style:none;border-width:0px;color:#ffffff;background-color:#000000"><?php echo esc_html( $button ); ?></a>
		</div>
		<!-- /wp:button -->
	</div>
	<!-- /wp:buttons -->
</div>
<!-- /wp:group -->
