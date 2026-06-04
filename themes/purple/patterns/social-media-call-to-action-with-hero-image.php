<?php
/**
 * Title: Social media call to action with hero image
 * Slug: purple/social-media-call-to-action-with-hero-image
 * Categories: purple
 * Keywords: banner, call-to-action, media
 * Description: A social media call to action with a hero image.
 */
?>
<!-- wp:group {"metadata":{"name":"Social media call to action with hero image"},"align":"full","className":"is-style-section-1","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull is-style-section-1"><!-- wp:cover {"url":"<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/pattern-placeholder.webp","alt":"<?php esc_html_e('Placeholder image', 'purple');?>","dimRatio":40,"overlayColor":"theme-2","isUserOverlayColor":true,"minHeight":60,"minHeightUnit":"vh","sizeSlug":"large","align":"full","layout":{"type":"constrained"}} -->
<div class="wp-block-cover alignfull" style="min-height:60vh"><img class="wp-block-cover__image-background  size-large" alt="<?php esc_attr_e('Placeholder image', 'purple');?>" src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/pattern-placeholder.webp" data-object-fit="cover"/><span aria-hidden="true" class="wp-block-cover__background has-theme-2-background-color has-background-dim-40 has-background-dim"></span><div class="wp-block-cover__inner-container"><!-- wp:heading {"textAlign":"center"} -->
<h2 class="wp-block-heading has-text-align-center"><?php esc_html_e('Follow us on Instagram', 'purple');?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center","style":{"typography":{"fontStyle":"normal","fontWeight":"300"}}} -->
<p class="has-text-align-center" style="font-style:normal;font-weight:300"><?php /* Translators: 1. is the start of a 'a' HTML element, 2. is the end of a 'a' HTML element */ 
echo sprintf( esc_html__( '%1$s@example%2$s', 'purple' ), '<a href="#">', '</a>' ); ?></p>
<!-- /wp:paragraph --></div></div>
<!-- /wp:cover --></div>
<!-- /wp:group -->