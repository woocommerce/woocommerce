<?php
/**
 * Title: 404
 * Slug: woocommerce/coming-soon-store-only
 * Categories: WooCommerce
 */
?>

<!-- wp:template-part {"slug":"header","tagName":"header"} /-->
<!-- wp:group {"layout":{"type":"constrained"}} -->
<div class="wp-block-group"><!-- wp:spacer -->
<div style="height:100px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:heading {"textAlign":"center","level":1} -->
<h1 class="wp-block-heading has-text-align-center"><?php echo esc_html_x( 'Great things coming soon', 'woocommerce' ); ?></h1>
<!-- /wp:heading -->

<!-- wp:spacer {"height":"10px"} -->
<div style="height:10px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center"><?php echo esc_html_x( 'Something big is brewing! Our store is in the works - Launching shortly!', 'woocommerce' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:spacer -->
<div style="height:100px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer --></div>
<!-- /wp:group -->
<!-- wp:template-part {"slug":"footer","tagName":"footer"} /-->

