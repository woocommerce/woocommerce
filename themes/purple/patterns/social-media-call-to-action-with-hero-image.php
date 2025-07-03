<?php
/**
 * Title: Social media call to action with hero image
 * Slug: woostarter/social-media-call-to-action-with-hero-image
 * Categories: Banners, Call to Action
 */
?>
<!-- wp:group {"metadata":{"name":"Social media call to action with hero image"},"align":"full","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull"><!-- wp:cover {"url":"<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/pattern-placeholder.webp","alt":"<?php esc_html_e('Placeholder image', 'woostarter');?>","dimRatio":50,"customOverlayColor":"#3d3d3e","isUserOverlayColor":false,"minHeight":60,"minHeightUnit":"vh","sizeSlug":"large","align":"full","layout":{"type":"constrained"}} -->
<div class="wp-block-cover alignfull" style="min-height:60vh"><img class="wp-block-cover__image-background  size-large" alt="<?php esc_html_e('Placeholder image', 'woostarter');?>" src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/pattern-placeholder.webp" data-object-fit="cover"/><span aria-hidden="true" class="wp-block-cover__background has-background-dim" style="background-color:#3d3d3e"></span><div class="wp-block-cover__inner-container"><!-- wp:paragraph {"align":"center","placeholder":"Write title…","fontSize":"x-large"} -->
<p class="has-text-align-center has-x-large-font-size"><?php esc_html_e('Follow us on Instagram', 'woostarter');?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"align":"center","style":{"typography":{"fontStyle":"normal","fontWeight":"300"},"elements":{"link":{"color":{"text":"var:preset|color|theme-1"}}}},"textColor":"theme-1","fontSize":"medium"} -->
<p class="has-text-align-center has-theme-1-color has-text-color has-link-color has-medium-font-size" style="font-style:normal;font-weight:300"><?php /* Translators: 1. is the start of a 'a' HTML element, 2. is the end of a 'a' HTML element */ 
echo sprintf( esc_html__( '%1$s@example%2$s', 'woostarter' ), '<a href="#">', '</a>' ); ?></p>
<!-- /wp:paragraph --></div></div>
<!-- /wp:cover --></div>
<!-- /wp:group -->