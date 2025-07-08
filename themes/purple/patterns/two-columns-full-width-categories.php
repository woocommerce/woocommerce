<?php
/**
 * Title: Two columns full width categories
 * Slug: purple/two-columns-full-width-categories
 * Categories: columns, gallery, media, featured
 * Keywords: columns, gallery, media, featured
 * Description: A section with a heading and a 2 column product grid.
 */
?>
<!-- wp:group {"metadata":{"name":"Two Columns Fullwidth","categories":["Columns","Gallery"],"patternName":"purple/two-columns-full-width-categories"},"align":"full","style":{"spacing":{"margin":{"top":"0","bottom":"0"}}},"layout":{"type":"default"}} -->
<div class="wp-block-group alignfull" style="margin-top:0;margin-bottom:0"><!-- wp:columns {"align":"wide","className":"is-style-section-1","style":{"spacing":{"blockGap":{"top":"0","left":"0"}}}} -->
<div class="wp-block-columns alignwide is-style-section-1"><!-- wp:column {"verticalAlignment":"center","width":"50%"} -->
<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:50%"><!-- wp:cover {"url":"<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/product-view3.webp","alt":"<?php esc_html_e('Illustration of a folded shirt', 'purple');?>","dimRatio":20,"overlayColor":"theme-2","isUserOverlayColor":true,"isDark":false,"sizeSlug":"full","style":{"dimensions":{"aspectRatio":"1"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-cover is-light"><img class="wp-block-cover__image-background  size-full" alt="<?php esc_attr_e('Illustration of a folded shirt', 'purple');?>" src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/product-view3.webp" data-object-fit="cover"/><span aria-hidden="true" class="wp-block-cover__background has-theme-2-background-color has-background-dim-20 has-background-dim"></span><div class="wp-block-cover__inner-container"><!-- wp:paragraph {"align":"center","placeholder":"Write title…"} -->
<p class="has-text-align-center"><?php /* Translators: 1. is the start of a 'a' HTML element, 2. is the end of a 'a' HTML element */ 
echo sprintf( esc_html__( '%1$sShop Women%2$s', 'purple' ), '<a href="' . esc_url( '#' ) . '" data-type="product_cat" data-id="19">', '</a>' ); ?></p>
<!-- /wp:paragraph --></div></div>
<!-- /wp:cover --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"bottom","width":"50%","style":{"spacing":{"padding":{"top":"0","bottom":"0","left":"0","right":"0"},"blockGap":"0"}},"layout":{"type":"default"}} -->
<div class="wp-block-column is-vertically-aligned-bottom" style="padding-top:0;padding-right:0;padding-bottom:0;padding-left:0;flex-basis:50%"><!-- wp:cover {"url":"<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/product-view2.webp","alt":"<?php esc_html_e('Illustration of a stack of folded shirts', 'purple');?>","dimRatio":20,"overlayColor":"theme-2","isUserOverlayColor":true,"isDark":false,"sizeSlug":"full","style":{"dimensions":{"aspectRatio":"1"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-cover is-light"><img class="wp-block-cover__image-background  size-full" alt="<?php esc_attr_e('Illustration of a stack of folded shirts', 'purple');?>" src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/product-view2.webp" data-object-fit="cover"/><span aria-hidden="true" class="wp-block-cover__background has-theme-2-background-color has-background-dim-20 has-background-dim"></span><div class="wp-block-cover__inner-container"><!-- wp:paragraph {"align":"center","placeholder":"Write title…"} -->
<p class="has-text-align-center"><?php /* Translators: 1. is the start of a 'a' HTML element, 2. is the end of a 'a' HTML element */ 
echo sprintf( esc_html__( '%1$sShop Men%2$s', 'purple' ), '<a href="' . esc_url( '#' ) . '" data-type="attachment" data-id="160">', '</a>' ); ?></p>
<!-- /wp:paragraph --></div></div>
<!-- /wp:cover --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->