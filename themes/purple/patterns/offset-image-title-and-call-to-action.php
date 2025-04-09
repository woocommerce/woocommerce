<?php
/**
 * Title: Offset image, title and call to action
 * Slug: woostarter/offset-image-title-and-call-to-action
 * Categories: Gallery
 */
?>
<!-- wp:group {"metadata":{"name":"Offset image, title and call to action"},"align":"wide","style":{"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignwide" style="padding-top:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--40)"><!-- wp:columns {"align":"wide"} -->
<div class="wp-block-columns alignwide"><!-- wp:column {"width":"15%"} -->
<div class="wp-block-column" style="flex-basis:15%"></div>
<!-- /wp:column -->

<!-- wp:column {"width":"85%"} -->
<div class="wp-block-column" style="flex-basis:85%"><!-- wp:image {"sizeSlug":"full","linkDestination":"none"} -->
<figure class="wp-block-image size-full"><img src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/pattern-japanese-artisanal-bowls.webp" alt="" class=""/></figure>
<!-- /wp:image --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->

<!-- wp:group {"align":"wide","style":{"spacing":{"margin":{"top":"var:preset|spacing|40"}}},"layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"space-between"}} -->
<div class="wp-block-group alignwide" style="margin-top:var(--wp--preset--spacing--40)"><!-- wp:paragraph {"fontSize":"x-large"} -->
<p class="has-x-large-font-size"><?php esc_html_e('Ceramics', 'woostarter');?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"fontSize":"x-large"} -->
<p class="has-x-large-font-size"><?php /* Translators: 1. is the start of a 'a' HTML element, 2. is the end of a 'a' HTML element */ 
echo sprintf( esc_html__( '%1$sShop now%2$s', 'woostarter' ), '<a href="' . esc_url( '#' ) . '">', '</a>' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->