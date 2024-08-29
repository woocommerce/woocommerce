<?php
/**
 * Title: Coming Soon Entire Site
 * Slug: woocommerce/coming-soon-entire-site
 * Categories: WooCommerce
 * Inserter: false
 * Feature Flag: launch-your-store
 *
 * @package WooCommerce\Blocks
 */

$current_theme     = wp_get_theme()->get_stylesheet();
$inter_font_family = 'inter';
$cardo_font_family = 'cardo';

if ( 'twentytwentyfour' === $current_theme ) {
	$inter_font_family = 'body';
	$cardo_font_family = 'heading';
}
?>

<!-- wp:woocommerce/coming-soon {"color":"#bea0f2","storeOnly":false,"className":"woocommerce-coming-soon-entire-site wp-block-woocommerce-background-color"} -->
<div class="woocommerce-coming-soon-entire-site wp-block-woocommerce-coming-soon wp-block-woocommerce-background-color"><!-- wp:cover {"minHeight":100,"minHeightUnit":"vh","isDark":false,"className":"coming-soon-is-vertically-aligned-center coming-soon-cover","layout":{"type":"default"}} -->
<div class="wp-block-cover is-light coming-soon-is-vertically-aligned-center coming-soon-cover" style="min-height:100vh"><span aria-hidden="true" class="wp-block-cover__background has-background-dim-100 has-background-dim" style=""></span><div class="wp-block-cover__inner-container"><!-- wp:group {"className":"woocommerce-coming-soon-banner-container","layout":{"type":"default"}} -->
<div class="wp-block-group woocommerce-coming-soon-banner-container"><!-- wp:group {"align":"wide","style":{"spacing":{"padding":{"top":"3px","bottom":"20px"}}},"className":"woocommerce-coming-soon-header","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignwide woocommerce-coming-soon-header has-background" style="padding-top:3px;padding-bottom:20px"><!-- wp:group {"align":"wide","layout":{"type":"flex","justifyContent":"space-between","flexWrap":"wrap"}} -->
<div class="wp-block-group alignwide"><!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|20"},"layout":{"selfStretch":"fit","flexSize":null}},"layout":{"type":"flex"}} -->
<div class="wp-block-group"><!-- wp:site-logo {"width":60} /-->

<!-- wp:group {"style":{"spacing":{"blockGap":"0px"}}} -->
<div class="wp-block-group"><!-- wp:site-title {"level":0,"fontFamily":"<?php echo esc_html( $inter_font_family ); ?>"} /--></div>
<!-- /wp:group --></div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"blockGap":"48px"}},"className":"woocommerce-coming-soon-social-login","layout":{"type":"flex","flexWrap":"nowrap"}} -->
<div class="wp-block-group woocommerce-coming-soon-social-login"><!-- wp:social-links {"iconColor":"contrast","iconColorValue":"#111111","style":{"layout":{"selfStretch":"fit","flexSize":null},"spacing":{"blockGap":{"left":"18px"}}},"className":"is-style-logos-only"} -->
<ul class="wp-block-social-links has-icon-color is-style-logos-only"><!-- wp:social-link {"url":"https://www.linkedin.com/","service":"linkedin"} /-->

<!-- wp:social-link {"url":"https://www.instagram.com","service":"instagram"} /-->

<!-- wp:social-link {"url":"https://www.facebook.com","service":"facebook"} /--></ul>
<!-- /wp:social-links -->

<!-- wp:loginout {"fontFamily":"<?php echo esc_html( $inter_font_family ); ?>"} /--></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->

<!-- wp:group {"layout":{"type":"flex","orientation":"vertical","justifyContent":"center"}} -->
<div class="wp-block-group"><!-- wp:heading {"textAlign":"center","level":1,"align":"wide","className":"woocommerce-coming-soon-banner","fontFamily":"<?php echo esc_html( $cardo_font_family ); ?>"} -->
<h1 class="wp-block-heading alignwide has-text-align-center woocommerce-coming-soon-banner has-<?php echo esc_html( $cardo_font_family ); ?>-font-family"><?php echo esc_html__( "Pardon our dust! We're working on something amazing — check back soon!", 'woocommerce' ); ?></h1>
<!-- /wp:heading --></div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|10"}}},"className":"woocommerce-coming-soon-powered-by-woo","layout":{"type":"constrained"}} -->
<div class="wp-block-group woocommerce-coming-soon-powered-by-woo" style="padding-top:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--10)"><!-- wp:group {"align":"wide","style":{"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"0"}}}} -->
<div class="wp-block-group alignwide" style="padding-top:var(--wp--preset--spacing--30);padding-bottom:0"><!-- wp:paragraph {"align":"center","style":{"elements":{"link":{"color":{"text":"var:preset|color|contrast"}}}},"textColor":"contrast-2","fontSize":"small"} -->
<p class="has-text-align-center has-contrast-2-color has-text-color has-link-color has-small-font-size">&nbsp;</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div></div>
<!-- /wp:cover --></div>
<!-- /wp:woocommerce/coming-soon -->
