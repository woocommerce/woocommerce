<?php
/**
 * Title: Left-aligned product, text on the right
 * Slug: purple/about-left-aligned-product-right-text
 * Categories: purple
 * Keywords: about, text, intro
 * Description: A section with a product image and a large sized paragraph.
 * Viewport width: 1440
 */
?>

<!-- wp:group {"metadata":{"categories":["purple"],"name":"Left-aligned product, text on the right","description":"A section with a product image and a large sized paragraph.","patternName":"purple/about-left-aligned-product-right-text"},"align":"full","className":"alignfull is-style-default","style":{"spacing":{"margin":{"top":"0","bottom":"0"},"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"},"blockGap":"0"},"dimensions":{"minHeight":""}},"layout":{"type":"constrained","justifyContent":"center"}} -->
<div class="wp-block-group alignfull is-style-default" style="margin-top:0;margin-bottom:0;padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50)"><!-- wp:columns {"align":"wide","style":{"spacing":{"blockGap":{"top":"0","left":"0"},"padding":{"right":"0","left":"0","top":"0","bottom":"0"}}},"backgroundColor":"theme-5"} -->
<div class="wp-block-columns alignwide has-theme-5-background-color has-background" style="padding-top:0;padding-right:0;padding-bottom:0;padding-left:0"><!-- wp:column {"verticalAlignment":"center","width":"50%"} -->
<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:50%"><!-- wp:cover {"url":"<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/product-view1.webp","alt":"<?php esc_attr_e( 'An illustration of folded shirts', 'purple' ); ?>","dimRatio":10,"isDark":false,"style":{"dimensions":{"aspectRatio":"4/3"}},"layout":{"type":"default"}} -->
<div class="wp-block-cover is-light"><img class="wp-block-cover__image-background" alt="<?php esc_attr_e( 'An illustration of folded shirts', 'purple' ); ?>" src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/product-view1.webp" data-object-fit="cover"/><span aria-hidden="true" class="wp-block-cover__background has-background-dim-10 has-background-dim"></span><div class="wp-block-cover__inner-container"><!-- wp:paragraph {"placeholder":"Write title…","style":{"typography":{"textAlign":"center"}}} -->
<p class="has-text-align-center"></p>
<!-- /wp:paragraph --></div></div>
<!-- /wp:cover --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center","style":{"spacing":{"padding":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|50","left":"var:preset|spacing|50","right":"var:preset|spacing|50"}}},"layout":{"type":"default"}} -->
<div class="wp-block-column is-vertically-aligned-center" style="padding-top:var(--wp--preset--spacing--70);padding-right:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50);padding-left:var(--wp--preset--spacing--50)"><!-- wp:group {"style":{"spacing":{"blockGap":"0"}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"stretch","verticalAlignment":"bottom"}} -->
<div class="wp-block-group"><!-- wp:heading {"style":{"typography":{"textAlign":"center"}}} -->
<h2 class="wp-block-heading has-text-align-center"><?php esc_html_e( 'New Collection', 'purple' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"style":{"typography":{"textAlign":"center"},"spacing":{"margin":{"top":"var:preset|spacing|20"}}},"fontSize":"medium"} -->
<p class="has-text-align-center has-medium-font-size" style="margin-top:var(--wp--preset--spacing--20)"><?php esc_html_e( 'Knitted with breathable 100% wool fibers.', 'purple' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:buttons {"style":{"spacing":{"margin":{"top":"var:preset|spacing|30"}}},"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons" style="margin-top:var(--wp--preset--spacing--30)"><!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button"><?php esc_html_e( 'Shop now', 'purple' ); ?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->
