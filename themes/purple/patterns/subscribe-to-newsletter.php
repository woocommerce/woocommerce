<?php
/**
 * Title: Subscribe to newsletter section
 * Slug: purple/subscribe-to-newsletter
 * Categories: text, call-to-action, columns
 * Keywords: text, call-to-action, columns
 * Description: A section with a heading and a newsletter subscription form.
 */
?>
<!-- wp:group {"metadata":{"name":"Subscribe to newsletter section"},"align":"full","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull"><!-- wp:spacer {"height":"var:preset|spacing|50"} -->
<div style="height:var(--wp--preset--spacing--50)" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:columns {"align":"wide"} -->
<div class="wp-block-columns alignwide"><!-- wp:column -->
<div class="wp-block-column"><!-- wp:heading -->
<h2 class="wp-block-heading"><?php esc_html_e('Subscribe to our newsletter', 'purple');?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p><?php esc_html_e('Be the first to know about exclusive deals, and special offers.', 'purple');?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:jetpack/contact-form {"className":"is-style-default","style":{"spacing":{"margin":{"top":"0","bottom":"0"},"padding":{"top":"0","bottom":"0"}}}} -->
<div class="wp-block-jetpack-contact-form is-style-default" style="margin-top:0;margin-bottom:0;padding-top:0;padding-bottom:0"><!-- wp:jetpack/field-email {"label":"","required":true,"requiredText":"","placeholder":"<?php esc_html_e('Your email', 'purple');?>","width":75,"borderRadius":0,"borderWidth":1,"labelFontSize":"14px"} /-->

<!-- wp:jetpack/button {"element":"button","text":"<?php esc_html_e('Subscribe', 'purple');?>","borderRadius":0,"width":"150px","lock":{"move":true,"remove":true}} /--></div>
<!-- /wp:jetpack/contact-form --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->

<!-- wp:spacer {"height":"var:preset|spacing|50"} -->
<div style="height:var(--wp--preset--spacing--50)" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer --></div>
<!-- /wp:group -->