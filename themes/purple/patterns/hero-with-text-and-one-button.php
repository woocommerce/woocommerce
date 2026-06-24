<?php
/**
 * Title: Hero with text and one button
 * Slug: purple/hero-with-text-and-one-button
 * Categories: woo-commerce, purple
 * Keywords: banner, media, call-to-action
 * Description: A hero section with text and one button.
 * Viewport width: 1440
 */
?>

<!-- wp:group {"metadata":{"name":"Hero with text and one button","categories":["woo-commerce","purple"],"patternName":"purple/hero-with-text-and-one-button"},"align":"full","style":{"spacing":{"margin":{"top":"0","bottom":"0"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull" style="margin-top:0;margin-bottom:0">
<!-- wp:cover {"url":"<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/pattern-placeholder.webp","alt":"<?php esc_attr_e('Placeholder image', 'purple');?>","dimRatio":40,"overlayColor":"theme-2","isUserOverlayColor":true,"minHeight":60,"minHeightUnit":"vh","contentPosition":"center center","sizeSlug":"large","align":"full","style":{"spacing":{"padding":{"right":"var:preset|spacing|50","left":"var:preset|spacing|50","top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-cover alignfull" style="padding-top:var(--wp--preset--spacing--50);padding-right:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50);padding-left:var(--wp--preset--spacing--50);min-height:60vh"><img class="wp-block-cover__image-background size-large" alt="<?php esc_attr_e('Placeholder image', 'purple');?>" src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/pattern-placeholder.webp" data-object-fit="cover"/><span aria-hidden="true" class="wp-block-cover__background has-theme-2-background-color has-background-dim-40 has-background-dim"></span><div class="wp-block-cover__inner-container"><!-- wp:group {"align":"wide","layout":{"type":"constrained","justifyContent":"center"}} -->
<div class="wp-block-group alignwide"><!-- wp:heading {"style":{"typography":{"textAlign":"center"}},"fontSize":"xx-large"} -->
<h2 class="wp-block-heading has-text-align-center has-xx-large-font-size"><?php esc_html_e('Just landed', 'purple');?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"style":{"typography":{"textAlign":"center"},"spacing":{"margin":{"top":"var:preset|spacing|20"}}}} -->
<p class="has-text-align-center" style="margin-top:var(--wp--preset--spacing--20)"><?php esc_html_e('Bold knits designed with care—for you and the planet.', 'purple');?></p>
<!-- /wp:paragraph -->

<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons"><!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button"><?php esc_html_e('Shop new in', 'purple');?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group --></div></div>
<!-- /wp:cover --></div>
<!-- /wp:group -->
