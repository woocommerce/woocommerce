<?php
/**
 * Title: Contact form with address and image
 * Slug: purple/contact-form-with-address-and-image
 * Categories: about, contact
 * Keywords: contact, form, contact-form, address
 * Description: A contact section with heading, address, contact form, and image placeholder.
 */
?>
<!-- wp:group {"metadata":{"name":"Contact","categories":["about","contact"],"patternName":"purple/contact-form-with-address-and-image"},"align":"full","style":{"spacing":{"margin":{"top":"0","bottom":"0"},"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40"}}},"layout":{"type":"constrained","justifyContent":"center"}} -->
<div class="wp-block-group alignfull" style="margin-top:0;margin-bottom:0;padding-top:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--40)"><!-- wp:columns {"align":"wide"} -->
<div class="wp-block-columns alignwide"><!-- wp:column {"verticalAlignment":"center","width":""} -->
<div class="wp-block-column is-vertically-aligned-center"><!-- wp:heading -->
<h2 class="wp-block-heading"><?php esc_html_e( 'Contact', 'purple' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"is-contact-description"} -->
<p class="is-contact-description"><?php esc_html_e( 'Navigating life\'s intricate fabric, choices unfold paths to the extraordinary, demanding creativity, curiosity, and courage for a truly fulfilling journey.', 'purple' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p><?php esc_html_e( '123 Example St.', 'purple' ); ?><br><?php esc_html_e( 'San Francisco, CA', 'purple' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:jetpack/contact-form {"subject":"","to":"","jetpackCRM":true,"salesforceData":{"organizationId":"","sendToSalesforce":false},"mailpoet":{"listId":null,"listName":null,"enabledForForm":false},"className":"is-style-default"} -->
<div class="wp-block-jetpack-contact-form is-style-default"><!-- wp:jetpack/field-name {"id":"","required":true,"fieldVariant":"name"} -->
<div><!-- wp:jetpack/label {"label":"<?php echo esc_html_x( 'Name', 'Contact form field label.', 'purple' ); ?>","requiredText":"<?php echo esc_attr_x( '(required)', 'Contact form required field indicator.', 'purple' ); ?>","fontSize":"small"} /-->

<!-- wp:jetpack/input {"borderColor":"theme-6","style":{"border":{"width":"1px"}}} /--></div>
<!-- /wp:jetpack/field-name -->

<!-- wp:jetpack/field-email {"id":"","required":true} -->
<div><!-- wp:jetpack/label {"label":"<?php echo esc_html_x( 'Email', 'Contact form field label.', 'purple' ); ?>","requiredText":"<?php echo esc_attr_x( '(required)', 'Contact form required field indicator.', 'purple' ); ?>","fontSize":"small"} /-->

<!-- wp:jetpack/input {"borderColor":"theme-6","style":{"border":{"width":"1px"}}} /--></div>
<!-- /wp:jetpack/field-email -->

<!-- wp:jetpack/field-textarea {"id":""} -->
<div><!-- wp:jetpack/label {"label":"<?php echo esc_html_x( 'Message', 'Contact form field label.', 'purple' ); ?>","requiredText":"<?php echo esc_attr_x( '(required)', 'Contact form required field indicator.', 'purple' ); ?>","fontSize":"small"} /-->

<!-- wp:jetpack/input {"type":"textarea","borderColor":"theme-6","style":{"border":{"width":"1px"}}} /--></div>
<!-- /wp:jetpack/field-textarea -->

<!-- wp:jetpack/button {"element":"button","text":"<?php echo esc_attr_x( 'Submit', 'Contact form submit button text.', 'purple' ); ?>","width":"","lock":{"remove":true}} /--></div>
<!-- /wp:jetpack/contact-form --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:image {"lightbox":{"enabled":false},"aspectRatio":"3/4","scale":"cover","sizeSlug":"full","linkDestination":"none"} -->
<figure class="wp-block-image size-full"><img src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/pattern-placeholder.webp" alt="<?php esc_attr_e( 'Placeholder image', 'purple' ); ?>" style="aspect-ratio:3/4;object-fit:cover"/></figure>
<!-- /wp:image --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->
