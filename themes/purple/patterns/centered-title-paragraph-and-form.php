<?php
/**
 * Title: Centered title, paragraph and form
 * Slug: woostarter/centered-title-paragraph-and-form
 * Categories: 
 */
?>
<!-- wp:group {"metadata":{"name":"Centered title, paragraph and form"},"align":"full","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull"><!-- wp:spacer {"height":"var:preset|spacing|50"} -->
<div style="height:var(--wp--preset--spacing--50)" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:heading {"textAlign":"center"} -->
<h2 class="wp-block-heading has-text-align-center"><?php esc_html_e('Subscribe to our newsletter', 'woostarter');?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center"><?php esc_html_e('Be the first to know about exclusive deals, and special offers.', 'woostarter');?></p>
<!-- /wp:paragraph -->

<!-- wp:jetpack/contact-form {"className":"is-style-default","style":{"spacing":{"margin":{"top":"0","bottom":"0"},"padding":{"top":"0","bottom":"0"}}}} -->
<div class="wp-block-jetpack-contact-form is-style-default" style="margin-top:0;margin-bottom:0;padding-top:0;padding-bottom:0">
<!-- wp:jetpack/field-email {"label":"","required":true,"requiredText":"","placeholder":"Your email","borderRadius":100,"borderWidth":1,"labelFontSize":"14px"} /-->
<!-- wp:jetpack/button {"element":"button","text":"Subscribe","width":"100%","lock":{"move":true,"remove":true},"fontFamily":"manrope"} /-->
</div>
<!-- /wp:jetpack/contact-form -->

<!-- wp:spacer {"height":"var:preset|spacing|50"} -->
<div style="height:var(--wp--preset--spacing--50)" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer --></div>
<!-- /wp:group -->