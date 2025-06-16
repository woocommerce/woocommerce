<?php
/**
 * Title: Two columns full width categories
 * Slug: woostarter/two-columns-full-width-categories
 * Categories: Columns, Gallery
 */
?>
<!-- wp:group {"metadata":{"name":"Two Columns Fullwidth","categories":["Columns","Gallery"],"patternName":"woostarter/two-columns-full-width-categories"},"align":"full","style":{"spacing":{"margin":{"top":"0","bottom":"0"}}},"layout":{"type":"default"}} -->
<div class="wp-block-group alignfull" style="margin-top:0;margin-bottom:0"><!-- wp:columns {"align":"wide","className":"is-style-section-1","style":{"spacing":{"blockGap":{"left":"0"}}}} -->
<div class="wp-block-columns alignwide is-style-section-1"><!-- wp:column {"verticalAlignment":"center","width":"50%"} -->
<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:50%"><!-- wp:cover {"url":"<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/long-sleeve-a-1.webp","dimRatio":20,"overlayColor":"theme-2","isUserOverlayColor":true,"isDark":false,"sizeSlug":"full","style":{"dimensions":{"aspectRatio":"1"},"elements":{"link":{"color":{"text":"var:preset|color|theme-1"}}}},"textColor":"theme-1","layout":{"type":"constrained"}} -->
<div class="wp-block-cover is-light has-theme-1-color has-text-color has-link-color"><img class="wp-block-cover__image-background  size-full" alt="" src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/long-sleeve-a-1.webp" data-object-fit="cover"/><span aria-hidden="true" class="wp-block-cover__background has-theme-2-background-color has-background-dim-20 has-background-dim"></span><div class="wp-block-cover__inner-container"><!-- wp:paragraph {"align":"center","placeholder":"Write title…","fontSize":"large"} -->
<p class="has-text-align-center has-large-font-size"><?php /* Translators: 1. is the start of a 'a' HTML element, 2. is the end of a 'a' HTML element */ 
echo sprintf( esc_html__( '%1$sShop Women%2$s', 'woostarter' ), '<a href="' . esc_url( '#' ) . '" data-type="product_cat" data-id="19">', '</a>' ); ?></p>
<!-- /wp:paragraph --></div></div>
<!-- /wp:cover --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"bottom","width":"50%","style":{"spacing":{"padding":{"top":"0","bottom":"0","left":"0","right":"0"},"blockGap":"0"}},"layout":{"type":"default"}} -->
<div class="wp-block-column is-vertically-aligned-bottom" style="padding-top:0;padding-right:0;padding-bottom:0;padding-left:0;flex-basis:50%"><!-- wp:cover {"url":"<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/beanie-b-1.webp","dimRatio":20,"overlayColor":"theme-2","isUserOverlayColor":true,"isDark":false,"sizeSlug":"full","style":{"dimensions":{"aspectRatio":"1"},"elements":{"link":{"color":{"text":"var:preset|color|theme-1"}}}},"textColor":"theme-1","layout":{"type":"constrained"}} -->
<div class="wp-block-cover is-light has-theme-1-color has-text-color has-link-color"><img class="wp-block-cover__image-background  size-full" alt="" src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/beanie-b-1.webp" data-object-fit="cover"/><span aria-hidden="true" class="wp-block-cover__background has-theme-2-background-color has-background-dim-20 has-background-dim"></span><div class="wp-block-cover__inner-container"><!-- wp:paragraph {"align":"center","placeholder":"Write title…","fontSize":"large"} -->
<p class="has-text-align-center has-large-font-size"><?php /* Translators: 1. is the start of a 'a' HTML element, 2. is the end of a 'a' HTML element */ 
echo sprintf( esc_html__( '%1$sShop Men%2$s', 'woostarter' ), '<a href="' . esc_url( '#' ) . '" data-type="attachment" data-id="160">', '</a>' ); ?></p>
<!-- /wp:paragraph --></div></div>
<!-- /wp:cover --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->