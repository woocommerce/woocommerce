<?php
/**
 * Title: Newsletter form with background image
 * Slug: woostarter/newsletter-form-with-background-image
 * Categories: 
 */
?>
<!-- wp:cover {"url":"<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/comingsoon-bg-image.webp","alt":"A striped antique chair in a softly lit, elegant room with green and gold walls.","dimRatio":20,"overlayColor":"theme-5","isUserOverlayColor":true,"minHeight":760,"minHeightUnit":"px","sizeSlug":"full","metadata":{"name":"Newsletter form with background image"},"align":"full","style":{"spacing":{"padding":{"right":"var:preset|spacing|50","left":"var:preset|spacing|50","top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}}} -->
<div class="wp-block-cover alignfull" style="padding-top:var(--wp--preset--spacing--50);padding-right:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50);padding-left:var(--wp--preset--spacing--50);min-height:760px"><img class="wp-block-cover__image-background  size-full" alt="<?php esc_attr_e('A striped antique chair in a softly lit, elegant room with green and gold walls.', 'woostarter');?>" src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/comingsoon-bg-image.webp" data-object-fit="cover"/><span aria-hidden="true" class="wp-block-cover__background has-theme-5-background-color has-background-dim-20 has-background-dim"></span><div class="wp-block-cover__inner-container"><!-- wp:heading {"textAlign":"center","fontSize":"xx-large"} -->
<h2 class="wp-block-heading has-text-align-center has-xx-large-font-size"><?php esc_html_e('Subscribe to our newsletter', 'woostarter');?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center"><?php esc_html_e('Be the first to know about exclusive deals, and special offers.', 'woostarter');?></p>
<!-- /wp:paragraph -->

<!-- wp:group {"layout":{"type":"constrained"}} -->
<div class="wp-block-group"><!-- wp:jetpack/contact-form {"className":"is-style-default","style":{"spacing":{"margin":{"top":"0","bottom":"0"},"padding":{"top":"0","bottom":"0"}}}} -->
<div class="wp-block-jetpack-contact-form is-style-default" style="margin-top:0;margin-bottom:0;padding-top:0;padding-bottom:0">
<!-- wp:jetpack/field-email {"label":"","required":true,"requiredText":"","placeholder":"Your email","borderRadius":100,"borderWidth":1,"labelFontSize":"14px"} /-->
<!-- wp:jetpack/button {"element":"button","text":"Subscribe","textColor":"theme-5","backgroundColor":"theme-1","width":"100%","lock":{"move":true,"remove":true},"className":"is-style-fill","fontFamily":"manrope"} /-->
</div>
<!-- /wp:jetpack/contact-form --></div>
<!-- /wp:group --></div></div>
<!-- /wp:cover -->