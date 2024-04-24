<?php
/**
 * Title: Coming Soon Store Only
 * Slug: woocommerce/coming-soon-store-only
 * Categories: WooCommerce
 *
 * @package WooCommerce\Blocks
 */

?>

<!-- wp:woocommerce/coming-soon {"storeOnly":true,"fullPageHeading":false} -->
<div class="wp-block-woocommerce-coming-soon">

<?php
if ( wc_current_theme_is_fse_theme() ) {
	echo '<!-- wp:template-part {"slug":"header","tagName":"header"} /-->';
}
?>

<!-- wp:group {"layout":{"type":"constrained"}} -->
<div class="wp-block-group"><!-- wp:spacer -->
<div style="height:100px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:heading {"textAlign":"center","level":1} -->
<h1 class="wp-block-heading has-text-align-center"><?php echo esc_html__( 'Great things are on the horizon', 'woocommerce' ); ?></h1>
<!-- /wp:heading -->

<!-- wp:spacer {"height":"10px"} -->
<div style="height:10px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center"><?php echo esc_html__( 'Something big is brewing! Our store is in the works and will be launching soon!', 'woocommerce' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:spacer -->
<div style="height:100px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer --></div>
<!-- /wp:group -->

<?php
if ( wc_current_theme_is_fse_theme() ) {
	echo '<!-- wp:template-part {"slug":"footer","tagName":"footer"} /-->';
}
?>

<style>.woocommerce-breadcrumb {display: none;}</style></div>
<!-- /wp:woocommerce/coming-soon -->
