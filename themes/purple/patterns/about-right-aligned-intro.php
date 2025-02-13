<?php
/**
 * Title: Right-aligned image, text on the left
 * Slug: woostarter/right-aligned-image-text-left
 * Categories: text, about, media
 * Keywords: about, text, intro
 * Description: An intro section with a heading, paragraph, button and image.
 *

?>
<!-- wp:group {"metadata":{"categories":["intro"],"name":"Right-aligned image, text on the left","patternName":"a8c/intro-two-column-with-image-and-content"},"align":"full","className":"alignfull is-style-default","style":{"spacing":{"margin":{"top":"0","bottom":"0"},"padding":{"top":"0","bottom":"0"}}},"layout":{"type":"constrained","justifyContent":"center"}} -->
<div class="wp-block-group alignfull is-style-default" style="margin-top:0;margin-bottom:0;padding-top:0;padding-bottom:0"><!-- wp:spacer {"height":"var:preset|spacing|50"} -->
<div style="height:var(--wp--preset--spacing--50)" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:columns {"verticalAlignment":"center","align":"wide"} -->
<div class="wp-block-columns alignwide are-vertically-aligned-center"><!-- wp:column {"verticalAlignment":"center","layout":{"type":"constrained","justifyContent":"left","contentSize":"460px"}} -->
<div class="wp-block-column is-vertically-aligned-center"><!-- wp:heading {"fontSize":"xx-large"} -->
<h2 class="wp-block-heading has-xx-large-font-size">Curating artistry</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>We celebrate the charm of the imperfect, inviting you to see the artistry in forms that are raw, organic, and unique.</p>
<!-- /wp:paragraph -->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button">Learn more</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center"} -->
<div class="wp-block-column is-vertically-aligned-center"><!-- wp:image {"id":331,"aspectRatio":"3/4","scale":"cover","sizeSlug":"full","linkDestination":"none"} -->
<figure class="wp-block-image size-full"><img src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/about-business-image.webp" alt="Antique bottles in a dimly lit arched alcove with a textured wall." class="wp-image-331" style="aspect-ratio:3/4;object-fit:cover"/></figure>
<!-- /wp:image --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->

<!-- wp:spacer {"height":"var:preset|spacing|50"} -->
<div style="height:var(--wp--preset--spacing--50)" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer --></div>
<!-- /wp:group -->