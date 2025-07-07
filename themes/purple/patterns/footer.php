<?php
/**
 * Title: Footer
 * Slug: woostarter/footer
 * Categories: footer
 * Block Types: core/template-part/footer
 * Description: A footer pattern with social links and navigation.
 */
?>
<!-- wp:group {"metadata":{"name":"Default Footer"},"align":"full","className":"is-style-default","style":{"spacing":{"margin":{"top":"0","bottom":"0"},"padding":{"top":"0","bottom":"0"}}},"layout":{"type":"constrained"}} --><div class="wp-block-group alignfull is-style-default" style="margin-top:0;margin-bottom:0;padding-top:0;padding-bottom:0"><!-- wp:spacer {"height":"var:preset|spacing|50"} -->
<div style="height:var(--wp--preset--spacing--50)" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:columns {"align":"wide","style":{"spacing":{"blockGap":{"top":"var:preset|spacing|50","left":"var:preset|spacing|50"}}}} -->
<div class="wp-block-columns alignwide"><!-- wp:column {"width":"40%"} -->
<div class="wp-block-column" style="flex-basis:40%"><!-- wp:site-title {"level":0} /-->

<!-- wp:site-tagline /-->

<!-- wp:spacer {"height":"var:preset|spacing|30"} -->
<div style="height:var(--wp--preset--spacing--30)" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:social-links {"iconColor":"theme-2","iconColorValue":"#111111","size":"has-normal-icon-size","className":"is-style-logos-only","style":{"spacing":{"blockGap":{"left":"var:preset|spacing|30"}}}} -->
<ul class="wp-block-social-links has-normal-icon-size has-icon-color is-style-logos-only"><!-- wp:social-link {"url":"#","service":"instagram"} /-->

<!-- wp:social-link {"url":"#","service":"x"} /-->

<!-- wp:social-link {"url":"#","service":"whatsapp"} /--></ul>
<!-- /wp:social-links --></div>
<!-- /wp:column -->

<!-- wp:column {"width":"30%"} -->
<div class="wp-block-column" style="flex-basis:30%"></div>
<!-- /wp:column -->

<!-- wp:column {"width":"15%","layout":{"type":"default"}} -->
<div class="wp-block-column" style="flex-basis:15%"><!-- wp:heading {"textAlign":"left","level":3,"fontSize":"medium"} -->
<h3 class="wp-block-heading has-text-align-left has-medium-font-size"><?php esc_html_e('Shop', 'woostarter');?></h3>
<!-- /wp:heading -->

<!-- wp:spacer {"height":"var:preset|spacing|20"} -->
<div style="height:var(--wp--preset--spacing--20)" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|20"}},"layout":{"type":"flex","orientation":"vertical"}} -->
<div class="wp-block-group"><!-- wp:navigation {"overlayMenu":"never","style":{"spacing":{"blockGap":"var:preset|spacing|20"}},"layout":{"type":"flex","orientation":"vertical"}} -->
<!-- wp:navigation-link {"label":"<?php esc_html_e('Ceramics', 'woostarter');?>","url":"#"} /-->

<!-- wp:navigation-link {"label":"<?php esc_html_e('Furniture', 'woostarter');?>","url":"#"} /-->

<!-- wp:navigation-link {"label":"<?php esc_html_e('Accessories', 'woostarter');?>","url":"#"} /-->

<!-- wp:navigation-link {"label":"<?php esc_html_e('Clothing', 'woostarter');?>","url":"#"} /-->
<!-- /wp:navigation --></div>
<!-- /wp:group --></div>
<!-- /wp:column -->

<!-- wp:column {"width":"15%","layout":{"type":"default"}} -->
<div class="wp-block-column" style="flex-basis:15%"><!-- wp:heading {"textAlign":"left","level":3,"fontSize":"medium"} -->
<h3 class="wp-block-heading has-text-align-left has-medium-font-size"><?php esc_html_e('Info', 'woostarter');?></h3>
<!-- /wp:heading -->

<!-- wp:spacer {"height":"var:preset|spacing|20"} -->
<div style="height:var(--wp--preset--spacing--20)" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|20"}},"layout":{"type":"flex","orientation":"vertical"}} -->
<div class="wp-block-group"><!-- wp:navigation {"overlayMenu":"never","style":{"spacing":{"blockGap":"var:preset|spacing|20"}},"layout":{"type":"flex","orientation":"vertical"}} -->
<!-- wp:navigation-link {"label":"<?php esc_html_e('About', 'woostarter');?>","url":"#"} /-->

<!-- wp:navigation-link {"label":"<?php esc_html_e('Contact us', 'woostarter');?>","url":"#"} /-->

<!-- wp:navigation-link {"label":"<?php esc_html_e('FAQs', 'woostarter');?>","url":"#"} /-->

<!-- wp:navigation-link {"label":"<?php esc_html_e('Blog', 'woostarter');?>","url":"#"} /-->
<!-- /wp:navigation --></div>
<!-- /wp:group --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->

<!-- wp:spacer {"height":"var:preset|spacing|50"} -->
<div style="height:var(--wp--preset--spacing--50)" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:spacer {"height":"var:preset|spacing|20"} -->
<div style="height:var(--wp--preset--spacing--20)" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:group {"align":"wide","style":{"spacing":{"blockGap":"0"}},"layout":{"type":"default"}} -->
<div class="wp-block-group alignwide"><!-- wp:spacer {"height":"var:preset|spacing|60","style":{"layout":[]}} -->
<div style="height:var(--wp--preset--spacing--60)" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"right":"0","left":"0"}}},"layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between"}} -->
<div class="wp-block-group alignfull" style="padding-right:0;padding-left:0"><!-- wp:paragraph {"align":"left","style":{"typography":{"textTransform":"uppercase"}},"fontSize":"small"} -->
<p class="has-text-align-left has-small-font-size" style="text-transform:uppercase"><?php esc_html_e('© 2025', 'woostarter');?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"align":"left","fontSize":"small"} -->
<p class="has-text-align-left has-small-font-size"><?php esc_html_e('Designed with WordPress', 'woostarter');?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:spacer {"height":"var:preset|spacing|60","style":{"layout":[]}} -->
<div style="height:var(--wp--preset--spacing--60)" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->