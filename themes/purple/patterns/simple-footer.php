<?php
/**
 * Title: Simple footer
 * Slug: woostarter/simple-footer
 * Categories: footer
 * Block Types: core/template-part/footer
 */
?>
<!-- wp:group {"metadata":{"name":"Simple footer","categories":["footer"],"patternName":"woostarter/footer-three-columns"},"align":"full","className":"is-style-default","style":{"spacing":{"padding":{"top":"0","bottom":"0"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull is-style-default" style="padding-top:0;padding-bottom:0"><!-- wp:group {"align":"full","style":{"spacing":{"blockGap":"0"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull"><!-- wp:spacer {"height":"var:preset|spacing|40","style":{"layout":[]}} -->
<div style="height:var(--wp--preset--spacing--40)" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"right":"var:preset|spacing|50","left":"var:preset|spacing|50"}}},"layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between"}} -->
<div class="wp-block-group alignfull" style="padding-right:var(--wp--preset--spacing--50);padding-left:var(--wp--preset--spacing--50)"><!-- wp:paragraph {"align":"left","style":{"elements":{"link":{"color":{"text":"var:preset|color|theme-3"}}}},"textColor":"theme-3","fontSize":"small"} -->
<p class="has-text-align-left has-theme-3-color has-text-color has-link-color has-small-font-size"><?php esc_html_e('© 2025 ELKE. Designed with WordPress', 'woostarter');?></p>
<!-- /wp:paragraph -->

<!-- wp:navigation {"overlayMenu":"never","style":{"typography":{"textDecoration":"underline"}},"fontSize":"small","layout":{"type":"flex","orientation":"horizontal"}} -->
<!-- wp:navigation-link {"label":"<?php esc_html_e('Account', 'woostarter');?>","url":"#"} /-->

<!-- wp:navigation-link {"label":"<?php esc_html_e('Terms & conditions', 'woostarter');?>","url":"#"} /-->

<!-- wp:navigation-link {"label":"<?php esc_html_e('Privacy Policy', 'woostarter');?>","url":"#"} /-->

<!-- wp:navigation-link {"label":"<?php esc_html_e('FAQs', 'woostarter');?>","url":"#"} /-->
<!-- /wp:navigation --></div>
<!-- /wp:group -->

<!-- wp:spacer {"height":"var:preset|spacing|40","style":{"layout":[]}} -->
<div style="height:var(--wp--preset--spacing--40)" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->