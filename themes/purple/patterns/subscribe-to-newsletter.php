<?php
/**
 * Title: Subscribe to newsletter section
 * Slug: purple/subscribe-to-newsletter
 * Categories: purple
 * Keywords: text, call-to-action, columns
 * Description: A section with a heading and a newsletter subscription form.
 */
?>

<!-- wp:group {"metadata":{"name":"Subscribe to newsletter section","categories":["purple"],"patternName":"purple/subscribe-to-newsletter","description":"A section with a heading and a newsletter subscription form."},"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"},"margin":{"top":"0","bottom":"0"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull" style="margin-top:0;margin-bottom:0;padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)"><!-- wp:columns {"verticalAlignment":"center","align":"wide"} -->
<div class="wp-block-columns alignwide are-vertically-aligned-center"><!-- wp:column {"verticalAlignment":"center"} -->
<div class="wp-block-column is-vertically-aligned-center"><!-- wp:heading -->
<h2 class="wp-block-heading"><?php esc_html_e('Subscribe to our newsletter', 'purple');?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p><?php esc_html_e('Be the first to know about exclusive deals, and special offers.', 'purple');?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center"} -->
<div class="wp-block-column is-vertically-aligned-center"><!-- wp:jetpack/contact-form {"jetpackCRM":false,"variationName":"default","salesforceData":{"organizationId":"","sendToSalesforce":false},"mailpoet":{"listId":null,"listName":null,"enabledForForm":false},"layout":{"type":"flex","flexWrap":"nowrap","orientation":"horizontal","justifyContent":"left","verticalAlignment":"bottom"}} /--></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->
