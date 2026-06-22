<?php
/**
 * Title: Hero with text and two buttons
 * Slug: purple/hero-with-text-and-two-buttons
 * Categories: purple
 * Keywords: banner, media, call-to-action
 * Description: A hero section with text and two buttons.
 */
?>

<!-- wp:group {"metadata":{"name":"Hero with text and two buttons","patternName":"purple/hero-with-text-and-two-buttons","description":"A hero section with text and two buttons.","categories":["purple"]},"align":"full","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull"><!-- wp:cover {"url":"<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/pattern-placeholder.webp","alt":"<?php esc_attr_e( 'Placeholder image', 'purple' ); ?>","dimRatio":40,"overlayColor":"theme-2","isUserOverlayColor":true,"minHeight":70,"minHeightUnit":"vh","contentPosition":"center left","sizeSlug":"large","align":"full","style":{"spacing":{"padding":{"right":"var:preset|spacing|50","left":"var:preset|spacing|50","top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-cover alignfull has-custom-content-position is-position-center-left" style="padding-top:var(--wp--preset--spacing--50);padding-right:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50);padding-left:var(--wp--preset--spacing--50);min-height:70vh"><img class="wp-block-cover__image-background size-large" alt="<?php esc_attr_e( 'Placeholder image', 'purple' ); ?>" src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/pattern-placeholder.webp" data-object-fit="cover"/><span aria-hidden="true" class="wp-block-cover__background has-theme-2-background-color has-background-dim-40 has-background-dim"></span><div class="wp-block-cover__inner-container"><!-- wp:heading {"fontSize":"xx-large"} -->
<h2 class="wp-block-heading has-xx-large-font-size"><?php esc_html_e( 'Colorful knits, crafted to last', 'purple' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p><?php esc_html_e( 'Bold knits designed with care—for you and the planet.', 'purple' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button"><?php esc_html_e( 'Shop new arrivals', 'purple' ); ?></a></div>
<!-- /wp:button -->

<!-- wp:button {"className":"is-style-button-1"} -->
<div class="wp-block-button is-style-button-1"><a class="wp-block-button__link wp-element-button"><?php esc_html_e( 'Shop all products', 'purple' ); ?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div></div>
<!-- /wp:cover --></div>
<!-- /wp:group -->
