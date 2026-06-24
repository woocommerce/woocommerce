<?php
/**
 * Title: Image on the left, description on the right
 * Slug: purple/about-left-aligned-image-description-button
 * Categories: purple
 * Keywords: about, text, media, image
 * Description: A section with an image on the left, description and button on the right.
 */
?>

<!-- wp:group {"metadata":{"name":"Image on the left, description on the right","categories":["purple"],"patternName":"purple/about-left-aligned-image-description-button","description":"A section with an image on the left, description and button on the right."},"align":"full","className":"is-style-section-2","style":{"spacing":{"margin":{"top":"0","bottom":"0"}}},"layout":{"type":"default"}} -->
<div class="wp-block-group alignfull is-style-section-2" style="margin-top:0;margin-bottom:0"><!-- wp:columns {"align":"wide","className":"is-style-default","style":{"spacing":{"blockGap":{"top":"0","left":"0"}}}} -->
<div class="wp-block-columns alignwide is-style-default"><!-- wp:column {"verticalAlignment":"center","width":"50%"} -->
<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:50%"><!-- wp:cover {"url":"<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/pattern-placeholder.webp","alt":"<?php esc_attr_e('Placeholder image', 'purple');?>","dimRatio":0,"overlayColor":"theme-2","isUserOverlayColor":true,"sizeSlug":"large","style":{"dimensions":{"aspectRatio":"4/3"}},"layout":{"type":"default"}} -->
<div class="wp-block-cover"><img class="wp-block-cover__image-background size-large" alt="<?php esc_attr_e('Placeholder image', 'purple');?>" src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/pattern-placeholder.webp" data-object-fit="cover"/><span aria-hidden="true" class="wp-block-cover__background has-theme-2-background-color has-background-dim-0 has-background-dim"></span><div class="wp-block-cover__inner-container"><!-- wp:paragraph {"placeholder":"Write title…","style":{"typography":{"textAlign":"center"}}} -->
<p class="has-text-align-center"></p>
<!-- /wp:paragraph --></div></div>
<!-- /wp:cover --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center","width":"50%","layout":{"type":"default"}} -->
<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:50%"><!-- wp:group {"style":{"spacing":{"padding":{"right":"var:preset|spacing|50","left":"var:preset|spacing|60","top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"},"blockGap":"var:preset|spacing|30"}},"layout":{"type":"constrained","justifyContent":"left"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--50);padding-right:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50);padding-left:var(--wp--preset--spacing--60)"><!-- wp:heading {"style":{"spacing":{"margin":{"top":"0"}}},"fontSize":"xx-large"} -->
<h2 class="wp-block-heading has-xx-large-font-size" style="margin-top:0"><?php esc_html_e('Honest materials', 'purple');?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p><?php esc_html_e('We use only responsibly sourced, natural fibers that are as kind to the environment as they are to your skin.', 'purple');?></p>
<!-- /wp:paragraph -->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button {"textColor":"theme-1","className":"is-style-outline","style":{"elements":{"link":{"color":{"text":"var:preset|color|theme-1"}}}}} -->
<div class="wp-block-button is-style-outline"><a class="wp-block-button__link has-theme-1-color has-text-color has-link-color wp-element-button"><?php esc_html_e('Shop now', 'purple');?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->
