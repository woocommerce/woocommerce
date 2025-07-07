<?php
/**
 * Title: Coming Soon
 * Slug: purple/coming-soon
 * Categories: woo-commerce, banner, media
 * Block Types: core/post-content
 * Post Types: page, wp_template
 * Viewport width: 1400
 * Description: A coming soon page pattern.
 */
?>
<!-- wp:columns {"lock":{"move":false,"remove":false},"metadata":{"name":"Coming Soon: Split layout with image","categories":["Coming","Soon","Pages"],"patternName":"purple/coming-soon"},"align":"full","className":"is-style-default","style":{"spacing":{"blockGap":{"top":"0","left":"0"},"margin":{"top":"0","bottom":"0"}}}} -->
<div class="wp-block-columns alignfull is-style-default" style="margin-top:0;margin-bottom:0"><!-- wp:column {"verticalAlignment":"center","className":"is-style-default","style":{"spacing":{"padding":{"right":"var:preset|spacing|50","left":"var:preset|spacing|50","top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}}} -->
<div class="wp-block-column is-vertically-aligned-center is-style-default" style="padding-top:var(--wp--preset--spacing--50);padding-right:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50);padding-left:var(--wp--preset--spacing--50)"><!-- wp:group {"layout":{"type":"default"}} -->
<div class="wp-block-group"><!-- wp:heading {"level":1} -->
<h1><?php esc_html_e('Coming soon', 'purple');?></h1>
<!-- /wp:heading -->

<!-- wp:paragraph {"style":{"typography":{"lineHeight":"1.3"},"spacing":{"margin":{"right":"var:preset|spacing|80"}},"elements":{"link":{"color":{"text":"var:preset|color|theme-4"}}}},"textColor":"theme-4"} -->
<p class="has-theme-4-color has-text-color has-link-color" style="margin-right:var(--wp--preset--spacing--80);line-height:1.3"><?php esc_html_e('Our store is in the works and will be launching soon.', 'purple');?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:column -->

<!-- wp:column {"width":"50%"} -->
<div class="wp-block-column" style="flex-basis:50%"><!-- wp:cover {"url":"<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/pattern-placeholder.webp","alt":"<?php esc_html_e('Placeholder image', 'purple');?>","dimRatio":40,"isUserOverlayColor":true,"minHeight":100,"minHeightUnit":"vh","contentPosition":"bottom right","style":{"layout":{"selfStretch":"fit","flexSize":null},"spacing":{"padding":{"right":"var:preset|spacing|30","left":"var:preset|spacing|30","top":"var:preset|spacing|30","bottom":"var:preset|spacing|30"}},"color":[]},"layout":{"type":"default"}} -->
<div class="wp-block-cover has-custom-content-position is-position-bottom-right" style="padding-top:var(--wp--preset--spacing--30);padding-right:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--30);padding-left:var(--wp--preset--spacing--30);min-height:100vh"><img class="wp-block-cover__image-background " alt="<?php esc_attr_e('Placeholder image', 'purple');?>" src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/pattern-placeholder.webp" data-object-fit="cover"/><span aria-hidden="true" class="wp-block-cover__background has-background-dim-40 has-background-dim"></span><div class="wp-block-cover__inner-container"><!-- wp:paragraph {"style":{"elements":{"link":{"color":{"text":"var:preset|color|theme-1"}}}}} -->
<p class="has-link-color"><?php /* Translators: 1. is the start of a 'a' HTML element, 2. is the end of a 'a' HTML element */ 
echo sprintf( esc_html__( 'Built with %1$sWooCommerce%2$s', 'purple' ), '<a href="' . esc_url( 'https://woocommerce.com/' ) . '">', '</a>' ); ?></p>
<!-- /wp:paragraph --></div></div>
<!-- /wp:cover --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->