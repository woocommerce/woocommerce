<?php
/**
 * Title: Split layout with image, text on the right
 * Slug: woostarter/split-layout-intro
 * Categories: text, about, media
 * Keywords: about, text, intro
 * Description: An intro section with an image, heading, paragraph, button.
 *

?>
<!-- wp:group {"metadata":{"name":"Split layout with image, text on the right"},"align":"full","style":{"spacing":{"margin":{"top":"0","bottom":"0"}}},"layout":{"type":"default"}} -->
<div class="wp-block-group alignfull" style="margin-top:0;margin-bottom:0"><!-- wp:columns {"align":"wide","className":"is-style-default","style":{"spacing":{"blockGap":{"left":"0"}}}} -->
<div class="wp-block-columns alignwide is-style-default"><!-- wp:column {"verticalAlignment":"center","width":"60%"} -->
<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:60%"><!-- wp:cover {"url":"<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/about-business-image.webp","id":331,"alt":"Antique bottles in a dimly lit arched alcove with a textured wall.","dimRatio":0,"customOverlayColor":"#775d4a","isUserOverlayColor":false,"sizeSlug":"full","style":{"dimensions":{"aspectRatio":"1"}},"layout":{"type":"default"}} -->
<div class="wp-block-cover"><img class="wp-block-cover__image-background wp-image-331 size-full" alt="Antique bottles in a dimly lit arched alcove with a textured wall." src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/about-business-image.webp" data-object-fit="cover"/><span aria-hidden="true" class="wp-block-cover__background has-background-dim-0 has-background-dim" style="background-color:#775d4a"></span><div class="wp-block-cover__inner-container"><!-- wp:paragraph {"align":"center","placeholder":"Write title…","fontSize":"large"} -->
<p class="has-text-align-center has-large-font-size"></p>
<!-- /wp:paragraph --></div></div>
<!-- /wp:cover --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"bottom","width":"40%","style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50","left":"var:preset|spacing|50","right":"var:preset|spacing|50"}}},"layout":{"type":"default"}} -->
<div class="wp-block-column is-vertically-aligned-bottom" style="padding-top:var(--wp--preset--spacing--50);padding-right:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50);padding-left:var(--wp--preset--spacing--50);flex-basis:40%"><!-- wp:heading {"fontSize":"xx-large"} -->
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
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->
