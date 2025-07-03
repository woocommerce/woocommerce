<?php
/**
 * Title: Hero with text and two buttons
 * Slug: woostarter/hero-with-text-and-two-buttons
 * Categories: Banners
 */
?>
<!-- wp:group {"metadata":{"name":"Hero with text and two buttons"},"align":"full","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull">
<!-- wp:cover {"url":"<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/pattern-placeholder.webp","alt":"<?php esc_html_e('Placeholder image', 'woostarter');?>","dimRatio":40,"overlayColor":"theme-2","isUserOverlayColor":true,"minHeight":70,"minHeightUnit":"vh","contentPosition":"center center","sizeSlug":"large","align":"full","style":{"spacing":{"padding":{"right":"var:preset|spacing|60","left":"var:preset|spacing|60","top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"}},"elements":{"link":{"color":{"text":"var:preset|color|theme-1"}}}},"textColor":"theme-1","layout":{"type":"constrained"}} -->
<div class="wp-block-cover alignfull has-theme-1-color has-text-color has-link-color" style="padding-top:var(--wp--preset--spacing--60);padding-right:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60);padding-left:var(--wp--preset--spacing--60);min-height:70vh"><img class="wp-block-cover__image-background size-large" alt="<?php esc_html_e('Placeholder image', 'woostarter');?>" src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/pattern-placeholder.webp" data-object-fit="cover"/><span aria-hidden="true" class="wp-block-cover__background has-theme-2-background-color has-background-dim-40 has-background-dim"></span><div class="wp-block-cover__inner-container"><!-- wp:group {"align":"wide","layout":{"type":"constrained","justifyContent":"left"}} -->
<div class="wp-block-group alignwide"><!-- wp:paragraph {"align":"left","placeholder":"Write title…","fontSize":"xx-large"} -->
<p class="has-text-align-left has-xx-large-font-size"><?php esc_html_e('Colorful knits, crafted to last', 'woostarter');?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"fontSize":"medium"} -->
<p class="has-medium-font-size"><?php esc_html_e('Bold knits designed with care—for you and the planet.', 'woostarter');?></p>
<!-- /wp:paragraph -->

<!-- wp:spacer {"height":"var:preset|spacing|20"} -->
<div style="height:var(--wp--preset--spacing--20)" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button"><?php esc_html_e('Shop new arrivals', 'woostarter');?></a></div>
<!-- /wp:button -->

<!-- wp:button {"backgroundColor":"theme-1","textColor":"theme-2","style":{"elements":{"link":{"color":{"text":"var:preset|color|theme-2"}}}}} -->
<div class="wp-block-button"><a class="wp-block-button__link has-theme-2-color has-theme-1-background-color has-text-color has-background has-link-color wp-element-button"><?php esc_html_e('Shop all products', 'woostarter');?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group --></div></div>
<!-- /wp:cover --></div>
<!-- /wp:group -->