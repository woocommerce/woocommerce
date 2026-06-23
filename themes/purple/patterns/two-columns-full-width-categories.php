<?php
/**
 * Title: Two columns full width categories
 * Slug: purple/two-columns-full-width-categories
 * Categories: woo-commerce, purple
 * Keywords: columns, gallery, media, featured, category
 * Description: A section with two full width Featured Category blocks.
 */
?>

<!-- wp:group {"metadata":{"name":"Two Columns Fullwidth","categories":["woo-commerce","purple"],"patternName":"purple/two-columns-full-width-categories"},"align":"full","style":{"spacing":{"margin":{"top":"0","bottom":"0"}}},"layout":{"type":"default"}} -->
<div class="wp-block-group alignfull" style="margin-top:0;margin-bottom:0"><!-- wp:columns {"align":"wide","style":{"spacing":{"blockGap":{"top":"0","left":"0"}}}} -->
<div class="wp-block-columns alignwide"><!-- wp:column {"verticalAlignment":"center","width":"50%"} -->
<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:50%"><!-- wp:cover {"url":"<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/product-view3.webp","alt":"<?php esc_attr_e( 'Illustration of a folded shirt', 'purple' ); ?>","dimRatio":20,"overlayColor":"theme-2","isUserOverlayColor":true,"isDark":false,"sizeSlug":"full","style":{"elements":{"link":{"color":{"text":"var:preset|color|theme-1"}}},"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50","left":"var:preset|spacing|50","right":"var:preset|spacing|50"}},"dimensions":{"aspectRatio":"1"}},"textColor":"theme-1","layout":{"type":"constrained"}} -->
<div class="wp-block-cover is-light has-theme-1-color has-text-color has-link-color" style="padding-top:var(--wp--preset--spacing--50);padding-right:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50);padding-left:var(--wp--preset--spacing--50)"><img class="wp-block-cover__image-background size-full" alt="<?php esc_attr_e( 'Illustration of a folded shirt', 'purple' ); ?>" src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/product-view3.webp" data-object-fit="cover"/><span aria-hidden="true" class="wp-block-cover__background has-theme-2-background-color has-background-dim-20 has-background-dim"></span><div class="wp-block-cover__inner-container"><!-- wp:paragraph {"placeholder":"Write title…","style":{"typography":{"textAlign":"center"}},"fontSize":"large"} -->
<p class="has-text-align-center has-large-font-size"><a href="<?php echo esc_url( '#' ); ?>"><?php esc_html_e( 'Shop Women', 'purple' ); ?></a></p>
<!-- /wp:paragraph --></div></div>
<!-- /wp:cover --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center","width":"50%"} -->
<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:50%"><!-- wp:cover {"url":"<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/product-view2.webp","alt":"<?php esc_attr_e( 'Illustration of a stack of folded shirts', 'purple' ); ?>","dimRatio":20,"overlayColor":"theme-2","isUserOverlayColor":true,"isDark":false,"sizeSlug":"full","style":{"elements":{"link":{"color":{"text":"var:preset|color|theme-1"}}},"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50","left":"var:preset|spacing|50","right":"var:preset|spacing|50"}},"dimensions":{"aspectRatio":"1"}},"textColor":"theme-1","layout":{"type":"constrained"}} -->
<div class="wp-block-cover is-light has-theme-1-color has-text-color has-link-color" style="padding-top:var(--wp--preset--spacing--50);padding-right:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50);padding-left:var(--wp--preset--spacing--50)"><img class="wp-block-cover__image-background size-full" alt="<?php esc_attr_e( 'Illustration of a stack of folded shirts', 'purple' ); ?>" src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/product-view2.webp" data-object-fit="cover"/><span aria-hidden="true" class="wp-block-cover__background has-theme-2-background-color has-background-dim-20 has-background-dim"></span><div class="wp-block-cover__inner-container"><!-- wp:paragraph {"placeholder":"Write title…","style":{"typography":{"textAlign":"center"}},"fontSize":"large"} -->
<p class="has-text-align-center has-large-font-size"><a href="<?php echo esc_url( '#' ); ?>"><?php esc_html_e( 'Shop Men', 'purple' ); ?></a></p>
<!-- /wp:paragraph --></div></div>
<!-- /wp:cover --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->
