<?php
/**
 * Title: Left-aligned image, text on the right
 * Slug: purple/left-aligned-image-right-text
 * Categories: text, about, media
 * Keywords: about, text, intro
 * Description: An intro section with a heading, paragraph, button and image.
 */
?>
<!-- wp:group {"metadata":{"categories":["intro"],"name":"Left-aligned image, text on the right","patternName":"purple/left-aligned-image-text-right"},"align":"full","className":"alignfull","style":{"spacing":{"margin":{"top":"0","bottom":"0"},"padding":{"top":"0","bottom":"0"}}},"backgroundColor":"theme-5","layout":{"type":"constrained","justifyContent":"center"}} -->
<div class="wp-block-group alignfull has-theme-5-background-color has-background" style="margin-top:0;margin-bottom:0;padding-top:0;padding-bottom:0"><!-- wp:spacer {"height":"var:preset|spacing|50"} -->
<div style="height:var(--wp--preset--spacing--50)" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:columns {"verticalAlignment":"center","align":"wide"} -->
<div class="wp-block-columns alignwide are-vertically-aligned-center">
	
<!-- wp:column {"verticalAlignment":"center"} -->

<div class="wp-block-column is-vertically-aligned-center">
	
<!-- wp:cover {"url":"<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/pattern-placeholder.webp","alt":"<?php esc_html_e('Placeholder image', 'purple');?>","dimRatio":0,"overlayColor":"theme-2","isUserOverlayColor":true,"minHeight":25,"minHeightUnit":"rem"} -->
<div class="wp-block-cover" style="min-height:25rem"><img class="wp-block-cover__image-background" alt="<?php esc_attr_e('Placeholder image', 'purple');?>" src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/pattern-placeholder.webp" data-object-fit="cover"/><span aria-hidden="true" class="wp-block-cover__background has-theme-2-background-color has-background-dim-0 has-background-dim"></span><div class="wp-block-cover__inner-container"><!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center"></p>
<!-- /wp:paragraph --></div></div>
<!-- /wp:cover -->

</div>
<!-- /wp:column -->
 
<!-- wp:column {"verticalAlignment":"center","layout":{"type":"constrained","justifyContent":"left","contentSize":"460px"}} -->
<div class="wp-block-column is-vertically-aligned-center">

<!-- wp:paragraph {"style":{"typography":{"textTransform":"uppercase","letterSpacing":"1.12px"}},"fontSize":"small"} -->
<p class="has-small-font-size" style="letter-spacing:1.12px;text-transform:uppercase"><?php esc_html_e('Production', 'purple');?></p>
<!-- /wp:paragraph -->

<!-- wp:heading {"style":{"spacing":{"margin":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30"}}}} -->
<h2 class="wp-block-heading" style="margin-top:var(--wp--preset--spacing--30);margin-bottom:var(--wp--preset--spacing--30)"><?php esc_html_e('Our factories', 'purple');?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p><?php esc_html_e('We celebrate the charm of the imperfect, inviting you to see the artistry in forms that are raw, organic, and unique.', 'purple');?></p>
<!-- /wp:paragraph -->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button"><?php esc_html_e('See list of factories', 'purple');?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:column -->

</div>
<!-- /wp:columns -->

<!-- wp:spacer {"height":"var:preset|spacing|50"} -->
<div style="height:var(--wp--preset--spacing--50)" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer --></div>
<!-- /wp:group -->