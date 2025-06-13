<?php
/**
 * Title: Coming Soon With Header and Footer
 * Slug: woocommerce/page-coming-soon-with-header-footer
 * Categories: WooCommerce
 * Template Types: coming-soon
 * Inserter: false
 */

use Automattic\WooCommerce\Blocks\Templates\ComingSoonTemplate;

$fonts               = ComingSoonTemplate::get_font_families();
$heading_font_family = $fonts['heading'];
$body_font_family    = $fonts['body'];


?>

<!-- wp:woocommerce/coming-soon {"comingSoonPatternId":"page-coming-soon-with-header-footer","className":"woocommerce-coming-soon-store-only"} -->
<div class="wp-block-woocommerce-coming-soon woocommerce-coming-soon-store-only">

<?php
if ( wp_is_block_theme() ) {
	echo '<!-- wp:template-part {"slug":"header","tagName":"header"} /-->';
}
?>

<!-- wp:group {"layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"center","orientation":"vertical"}} -->
<div class="wp-block-group"><!-- wp:spacer -->
<div style="height:100px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:heading {"textAlign":"center","level":1,"fontFamily":"<?php echo esc_html( $heading_font_family ); ?>"} -->
<h1 class="wp-block-heading has-text-align-center has-<?php echo esc_html( $heading_font_family ); ?>-font-family"><?php echo esc_html__( 'Great things are on the horizon', 'woocommerce' ); ?></h1>
<!-- /wp:heading -->

<!-- wp:spacer {"height":"10px"} -->
<div style="height:10px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:paragraph {"align":"center","fontFamily":"<?php echo esc_html( $body_font_family ); ?>"} -->
<p class="has-text-align-center has-<?php echo esc_html( $body_font_family ); ?>-font-family"><?php echo esc_html__( 'Something big is brewing! Our store is in the works and will be launching soon!', 'woocommerce' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:spacer -->
<div style="height:100px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer --></div>
<!-- /wp:group -->

<?php
if ( wp_is_block_theme() ) {
	echo '<!-- wp:template-part {"slug":"footer","tagName":"footer"} /-->';
}
?>
</div>
<!-- /wp:woocommerce/coming-soon -->
