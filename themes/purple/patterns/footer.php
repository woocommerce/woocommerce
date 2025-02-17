<?php
/**
 * Title: footer
 * Slug: woostarter/footer
 * Inserter: no
 */
?>
<!-- wp:group {"metadata":{"name":"Default Footer"},"align":"full","className":"is-style-section-1","style":{"spacing":{"padding":{"top":"0","bottom":"0"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull is-style-section-1" style="padding-top:0;padding-bottom:0"><!-- wp:spacer {"height":"var:preset|spacing|50"} -->
<div style="height:var(--wp--preset--spacing--50)" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:columns {"align":"wide","style":{"spacing":{"blockGap":{"top":"var:preset|spacing|50","left":"var:preset|spacing|50"}}}} -->
<div class="wp-block-columns alignwide"><!-- wp:column {"width":"40%"} -->
<div class="wp-block-column" style="flex-basis:40%"><!-- wp:heading {"textAlign":"left","level":3,"fontSize":"medium"} -->
<h3 class="wp-block-heading has-text-align-left has-medium-font-size"><?php esc_html_e('Subscribe to our newsletter', 'woostarter');?></h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"left"} -->
<p class="has-text-align-left"><?php esc_html_e('[Jetpack form]', 'woostarter');?></p>
<!-- /wp:paragraph -->

<!-- wp:social-links {"className":"is-style-logos-only"} -->
<ul class="wp-block-social-links is-style-logos-only"><!-- wp:social-link {"url":"#","service":"instagram"} /-->

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

<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|20"}},"layout":{"type":"flex","orientation":"vertical"}} -->
<div class="wp-block-group"><!-- wp:paragraph {"align":"left"} -->
<p class="has-text-align-left"><?php esc_html_e('Ceramics', 'woostarter');?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p><?php esc_html_e('Furniture', 'woostarter');?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p><?php esc_html_e('Accessories', 'woostarter');?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p><?php esc_html_e('Clothing', 'woostarter');?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:column -->

<!-- wp:column {"width":"15%","layout":{"type":"default"}} -->
<div class="wp-block-column" style="flex-basis:15%"><!-- wp:heading {"textAlign":"left","level":3,"fontSize":"medium"} -->
<h3 class="wp-block-heading has-text-align-left has-medium-font-size"><?php esc_html_e('Info', 'woostarter');?></h3>
<!-- /wp:heading -->

<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|20"}},"layout":{"type":"flex","orientation":"vertical"}} -->
<div class="wp-block-group"><!-- wp:paragraph {"align":"left"} -->
<p class="has-text-align-left"><?php esc_html_e('Ceramics', 'woostarter');?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p><?php esc_html_e('Furniture', 'woostarter');?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p><?php esc_html_e('Accessories', 'woostarter');?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p><?php esc_html_e('Clothing', 'woostarter');?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->

<!-- wp:spacer {"height":"var:preset|spacing|50"} -->
<div style="height:var(--wp--preset--spacing--50)" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:group {"align":"wide","layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"space-between"}} -->
<div class="wp-block-group alignwide"><!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button {"className":"is-style-outline"} -->
<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button"><?php esc_html_e('United Kingdom (GBP £)', 'woostarter');?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->

<!-- wp:paragraph -->
<p><?php esc_html_e('[Shopping icons]', 'woostarter');?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:spacer {"height":"var:preset|spacing|20"} -->
<div style="height:var(--wp--preset--spacing--20)" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:group {"align":"full","style":{"spacing":{"blockGap":"0"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull"><!-- wp:separator {"align":"full"} -->
<hr class="wp-block-separator alignfull has-alpha-channel-opacity"/>
<!-- /wp:separator -->

<!-- wp:spacer {"height":"var:preset|spacing|40","style":{"layout":[]}} -->
<div style="height:var(--wp--preset--spacing--40)" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"right":"var:preset|spacing|50","left":"var:preset|spacing|50"}}},"layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between"}} -->
<div class="wp-block-group alignfull" style="padding-right:var(--wp--preset--spacing--50);padding-left:var(--wp--preset--spacing--50)"><!-- wp:paragraph {"align":"left","fontSize":"small"} -->
<p class="has-text-align-left has-small-font-size"><?php /* Translators: 1. is the start of a 'a' HTML element, 2. is the end of a 'a' HTML element */ 
echo sprintf( esc_html__( 'Designed with %1$sWooCommerce%2$s', 'woostarter' ), '<a href="' . esc_url( 'https://woocommerce.com' ) . '">', '</a>' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:navigation {"overlayMenu":"never","layout":{"type":"flex","flexWrap":"wrap","justifyContent":"left"}} /--></div>
<!-- /wp:group -->

<!-- wp:spacer {"height":"var:preset|spacing|40","style":{"layout":[]}} -->
<div style="height:var(--wp--preset--spacing--40)" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->