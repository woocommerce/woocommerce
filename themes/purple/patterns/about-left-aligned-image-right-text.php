<?php
/**
 * Title: Left-aligned image, text on the right
 * Slug: purple/about-left-aligned-image-right-text
 * Categories: purple
 * Keywords: about, text, intro
 * Description: An intro section with a heading, paragraph, button and image.
 * Viewport width: 1440
 */
?>

<!-- wp:group {"metadata":{"categories":["purple"],"name":"Left-aligned image, text on the right","patternName":"purple/about-left-aligned-image-right-text","description":"An intro section with a heading, paragraph, button and image."},"align":"full","className":"alignfull","style":{"spacing":{"margin":{"top":"0","bottom":"0"},"padding":{"top":"var:preset|spacing|80","bottom":"var:preset|spacing|80"}}},"backgroundColor":"theme-5","layout":{"type":"constrained","justifyContent":"center"}} -->
<div class="wp-block-group alignfull has-theme-5-background-color has-background" style="margin-top:0;margin-bottom:0;padding-top:var(--wp--preset--spacing--80);padding-bottom:var(--wp--preset--spacing--80)"><!-- wp:columns {"verticalAlignment":"center","align":"wide","style":{"spacing":{"blockGap":{"top":"0","left":"var:preset|spacing|50"}}}} -->
<div class="wp-block-columns alignwide are-vertically-aligned-center"><!-- wp:column {"verticalAlignment":"center","width":"50%"} -->
<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:50%"><!-- wp:cover {"url":"<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/pattern-placeholder.webp","alt":"<?php esc_attr_e('Placeholder image', 'purple');?>","dimRatio":0,"overlayColor":"theme-2","isUserOverlayColor":true,"minHeight":25,"minHeightUnit":"rem"} -->
<div class="wp-block-cover" style="min-height:25rem"><img class="wp-block-cover__image-background" alt="<?php esc_attr_e('Placeholder image', 'purple');?>" src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/pattern-placeholder.webp" data-object-fit="cover"/><span aria-hidden="true" class="wp-block-cover__background has-theme-2-background-color has-background-dim-0 has-background-dim"></span><div class="wp-block-cover__inner-container"><!-- wp:paragraph {"style":{"typography":{"textAlign":"center"}}} -->
<p class="has-text-align-center"></p>
<!-- /wp:paragraph --></div></div>
<!-- /wp:cover --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center","width":"50%","style":{"spacing":{"padding":{"left":"0","top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"}}},"layout":{"type":"constrained","justifyContent":"center","contentSize":"460px"}} -->
<div class="wp-block-column is-vertically-aligned-center" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60);padding-left:0;flex-basis:50%"><!-- wp:paragraph {"style":{"typography":{"textTransform":"uppercase","letterSpacing":"0.08em","lineHeight":"1.28"},"elements":{"link":{"color":{"text":"var:preset|color|theme-2"}}}},"textColor":"theme-2","fontSize":"small"} -->
<p class="has-theme-2-color has-text-color has-link-color has-small-font-size" style="letter-spacing:0.08em;line-height:1.28;text-transform:uppercase"><?php esc_html_e('Production', 'purple');?></p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading"><?php esc_html_e('Our factories', 'purple');?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p><?php esc_html_e('We celebrate the charm of the imperfect, inviting you to see the artistry in forms that are raw, organic, and unique.', 'purple');?></p>
<!-- /wp:paragraph -->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button"><?php esc_html_e('See list of factories', 'purple');?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->
