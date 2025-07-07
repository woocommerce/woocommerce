<?php
/**
 * Title: Hero with text and one button
 * Slug: purple/hero-with-text-and-one-button
 * Categories: banner, media, call-to-action
 * Keywords: banner, media, call-to-action
 * Description: A hero section with text and one button.
 */
?>
<!-- wp:group {"metadata":{"name":"Hero with text and two buttons"},"align":"full","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull">
<!-- wp:cover {"url":"<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/pattern-placeholder.webp","alt":"<?php esc_attr_e('Placeholder image', 'purple');?>","dimRatio":40,"overlayColor":"theme-2","isUserOverlayColor":true,"minHeight":70,"minHeightUnit":"vh","contentPosition":"center center","sizeSlug":"large","align":"full","style":{"spacing":{"padding":{"right":"var:preset|spacing|60","left":"var:preset|spacing|60","top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-cover alignfull" style="padding-top:var(--wp--preset--spacing--60);padding-right:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60);padding-left:var(--wp--preset--spacing--60);min-height:70vh"><img class="wp-block-cover__image-background size-large" alt="<?php esc_attr_e('Placeholder image', 'purple');?>" src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/pattern-placeholder.webp" data-object-fit="cover"/><span aria-hidden="true" class="wp-block-cover__background has-theme-2-background-color has-background-dim-40 has-background-dim"></span><div class="wp-block-cover__inner-container"><!-- wp:group {"align":"wide","layout":{"type":"constrained","justifyContent":"center"}} -->
<div class="wp-block-group alignwide"><!-- wp:paragraph {"align":"center","placeholder":"Write title…","fontSize":"xx-large"} -->
<p class="has-text-align-center has-xx-large-font-size"><?php esc_html_e('Just landed', 'purple');?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"align":"center","fontSize":"medium"} -->
<p class="has-text-align-center has-medium-font-size"><?php esc_html_e('Bold knits designed with care—for you and the planet.', 'purple');?></p>
<!-- /wp:paragraph -->

<!-- wp:spacer {"height":"var:preset|spacing|20"} -->
<div style="height:var(--wp--preset--spacing--20)" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons"><!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button"><?php esc_html_e('Shop new in', 'purple');?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group --></div></div>
<!-- /wp:cover --></div>
<!-- /wp:group -->