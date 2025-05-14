<?php
/**
 * Title: Newsletter form with image on the left
 * Slug: woostarter/newsletter-form-with-image-on-the-left
 * Categories: 
 */
?>
<!-- wp:group {"metadata":{"categories":["intro"],"name":"Newsletter form with image on the left","patternName":"a8c/intro-two-column-with-image-and-content"},"align":"full","className":"alignfull is-style-default","style":{"spacing":{"margin":{"top":"0","bottom":"0"},"padding":{"top":"0","bottom":"0"}}},"layout":{"type":"constrained","justifyContent":"center"}} -->
<div class="wp-block-group alignfull is-style-default" style="margin-top:0;margin-bottom:0;padding-top:0;padding-bottom:0"><!-- wp:spacer {"height":"var:preset|spacing|50"} -->
<div style="height:var(--wp--preset--spacing--50)" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:columns {"verticalAlignment":"center","align":"wide","style":{"spacing":{"blockGap":{"top":"var:preset|spacing|50","left":"var:preset|spacing|50"}}}} -->
<div class="wp-block-columns alignwide are-vertically-aligned-center"><!-- wp:column {"verticalAlignment":"center"} -->
<div class="wp-block-column is-vertically-aligned-center"><!-- wp:image {"aspectRatio":"3/4","scale":"cover","sizeSlug":"full","linkDestination":"none"} -->
<figure class="wp-block-image size-full"><img src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/comingsoon-bg-image.webp" alt="<?php esc_attr_e('A striped antique chair in a softly lit, elegant room with green and gold walls.', 'woostarter');?>" class="" style="aspect-ratio:3/4;object-fit:cover"/></figure>
<!-- /wp:image --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center","layout":{"type":"default"}} -->
<div class="wp-block-column is-vertically-aligned-center"><!-- wp:heading {"fontSize":"xx-large"} -->
<h2 class="wp-block-heading has-xx-large-font-size"><?php esc_html_e('Subscribe to our newsletter', 'woostarter');?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p><?php esc_html_e('Stay tuned for exclusive deals and special offers.', 'woostarter');?></p>
<!-- /wp:paragraph -->

<!-- wp:jetpack/contact-form {"className":"is-style-default","style":{"spacing":{"margin":{"top":"0","bottom":"0"},"padding":{"top":"0","bottom":"0"}}}} -->
<div class="wp-block-jetpack-contact-form is-style-default" style="margin-top:0;margin-bottom:0;padding-top:0;padding-bottom:0">
<!-- wp:jetpack/field-email {"label":"","required":true,"requiredText":"","placeholder":"Your email","borderRadius":100,"borderWidth":1,"labelFontSize":"14px"} /-->
<!-- wp:jetpack/button {"element":"button","text":"Subscribe","width":"150px","lock":{"move":true,"remove":true}} /-->
</div>
<!-- /wp:jetpack/contact-form --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->

<!-- wp:spacer {"height":"var:preset|spacing|50"} -->
<div style="height:var(--wp--preset--spacing--50)" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer --></div>
<!-- /wp:group -->