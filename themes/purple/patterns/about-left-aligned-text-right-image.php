<?php
/**
 * Title: Left-aligned text, image on the right
 * Slug: woostarter/left-aligned-text-right-image
 * Categories: text, about, media
 * Keywords: about, text, intro
 * Description: A section with an image on the right, description on the left.
 */
?>
<!-- wp:group {"metadata":{"name":"Left-aligned text, image on the right","categories":["about"],"patternName":"woostarter/left-aligned-text-right-image"},"align":"full","style":{"spacing":{"margin":{"top":"0","bottom":"0"}}},"backgroundColor":"theme-5","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull has-theme-5-background-color has-background" style="margin-top:0;margin-bottom:0"><!-- wp:columns {"align":"wide","className":"is-style-default","style":{"spacing":{"blockGap":{"left":"0"}}}} -->
<div class="wp-block-columns alignwide is-style-default">
<!-- wp:column {"verticalAlignment":"center","width":"50%","layout":{"type":"default"}} -->
<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:50%"><!-- wp:group {"style":{"spacing":{"padding":{"right":"var:preset|spacing|50","left":"var:preset|spacing|60"},"blockGap":"var:preset|spacing|30"}},"layout":{"type":"constrained","justifyContent":"left"}} -->
<div class="wp-block-group" style="padding-right:var(--wp--preset--spacing--50);padding-left:var(--wp--preset--spacing--60)">
<!-- wp:spacer {"height":"var:preset|spacing|50"} -->
<div style="height:var(--wp--preset--spacing--50)" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->
<!-- wp:paragraph {"style":{"elements":{"link":{"color":{"text":"var:preset|color|theme-2"}}},"typography":{"textTransform":"uppercase","letterSpacing":"1.12px"}},"textColor":"theme-2","fontSize":"small"} -->
<p class="has-theme-2-color has-text-color has-link-color has-small-font-size" style="letter-spacing:1.12px;text-transform:uppercase"><?php esc_html_e('Sourcing', 'woostarter');?></p>
<!-- /wp:paragraph -->
<!-- wp:heading {"style":{"spacing":{"margin":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30"}},"elements":{"link":{"color":{"text":"var:preset|color|theme-2"}}}},"textColor":"theme-2","fontSize":"x-large"} -->
<h2 class="wp-block-heading has-theme-2-color has-text-color has-link-color has-x-large-font-size" style="margin-top:var(--wp--preset--spacing--30);margin-bottom:var(--wp--preset--spacing--30)"><?php esc_html_e('Our materials', 'woostarter');?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p><?php esc_html_e('Our products are crafted with meticulous attention to quality, using only the finest materials and ingredients. Designed to deliver exceptional performance, each item undergoes rigorous testing to ensure it meets the highest standards.', 'woostarter');?></p>
<!-- /wp:paragraph -->

<!-- wp:spacer {"height":"var:preset|spacing|50"} -->
<div style="height:var(--wp--preset--spacing--50)" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->
</div>
<!-- /wp:group --></div>
<!-- /wp:column -->
<!-- wp:column {"verticalAlignment":"center","width":"50%"} -->
<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:50%"><!-- wp:cover {"url":"<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/Pattern-placeholder.webp","dimRatio":0,"overlayColor":"theme-2","isUserOverlayColor":true,"sizeSlug":"large","style":{"dimensions":{"aspectRatio":"4/3"}},"layout":{"type":"default"}} -->
<div class="wp-block-cover"><img class="wp-block-cover__image-background size-large" alt="<?php esc_html_e('Placeholder image', 'woostarter');?>" src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/Pattern-placeholder.webp" data-object-fit="cover"/><span aria-hidden="true" class="wp-block-cover__background has-theme-2-background-color has-background-dim-0 has-background-dim"></span><div class="wp-block-cover__inner-container"><!-- wp:paragraph {"align":"center","placeholder":"Write title…","fontSize":"large"} -->
<p class="has-text-align-center has-large-font-size"></p>
<!-- /wp:paragraph --></div></div>
<!-- /wp:cover --></div>
<!-- /wp:column -->
</div>
<!-- /wp:columns --></div>
<!-- /wp:group -->