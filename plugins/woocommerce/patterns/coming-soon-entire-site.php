<?php
/**
 * Note: This pattern is deprecated, it will be removed once newsletter feature flag is deployed.
 * If you are updating this pattern, please also update page-coming-soon-default.php.
 */

/**
 * Title: Coming Soon Entire Site
 * Slug: woocommerce/coming-soon-entire-site
 * Categories: WooCommerce
 * Inserter: false
 * Feature Flag: launch-your-store
 *
 * @package WooCommerce\Blocks
 */

use Automattic\WooCommerce\Blocks\Templates\ComingSoonTemplate;

$fonts               = ComingSoonTemplate::get_font_families();
$heading_font_family = $fonts['heading'];
$body_font_family    = $fonts['body'];

?>

<!-- wp:woocommerce/coming-soon {"comingSoonPatternId":"page-coming-soon-default","className":"woocommerce-coming-soon-entire-site","style":{"color":{"background":"#bea0f2","text":"#000000"},"elements":{"link":{"color":{"text":"#000000"}}}}} -->
<div class="wp-block-woocommerce-coming-soon woocommerce-coming-soon-entire-site has-text-color has-background has-link-color" style="color:#000000;background-color:#bea0f2"><!-- wp:cover {"customOverlayColor":"transparent","isUserOverlayColor":true,"minHeight":100,"minHeightUnit":"vh","className":"coming-soon-is-vertically-aligned-center coming-soon-cover","style":{"spacing":{"padding":{"top":"0px","bottom":"0px","left":"24px","right":"24px"}},"color":{"text":"inherit"},"elements":{"link":{"color":{"text":"inherit"}}}},"layout":{"type":"constrained","wideSize":"1280px"}} -->
<div class="wp-block-cover coming-soon-is-vertically-aligned-center coming-soon-cover has-text-color has-link-color" style="color:inherit;padding-top:0px;padding-right:24px;padding-bottom:0px;padding-left:24px;min-height:100vh"><span aria-hidden="true" class="wp-block-cover__background has-background-dim-100 has-background-dim" style="background-color:transparent"></span><div class="wp-block-cover__inner-container"><!-- wp:group {"className":"woocommerce-coming-soon-banner-container","style":{"dimensions":{"minHeight":"100vh"},"spacing":{"blockGap":"0px"}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"stretch"}} -->
<div class="wp-block-group woocommerce-coming-soon-banner-container" style="min-height:100vh"><!-- wp:group {"align":"wide","className":"woocommerce-coming-soon-header has-background","style":{"spacing":{"padding":{"bottom":"14px","top":"26px"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignwide woocommerce-coming-soon-header has-background" style="padding-top:26px;padding-bottom:14px"><!-- wp:group {"align":"wide","layout":{"type":"flex","justifyContent":"space-between","flexWrap":"wrap"}} -->
<div class="wp-block-group alignwide"><!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|20"},"layout":{"selfStretch":"fit","flexSize":null}},"layout":{"type":"flex"}} -->
<div class="wp-block-group"><!-- wp:site-logo {"width":60,"style":{"layout":{"selfStretch":"fit","flexSize":null}}} /-->

<!-- wp:group {"style":{"spacing":{"blockGap":"0px"}}} -->
<div class="wp-block-group"><!-- wp:site-title {"level":0,"style":{"typography":{"fontSize":"20px","letterSpacing":"0px"},"color":{"text":"#000000"},"elements":{"link":{"color":{"text":"#000000"}}}},"fontFamily":"<?php echo esc_html( $body_font_family ); ?>"} /--></div>
<!-- /wp:group --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"woocommerce-coming-soon-social-login","style":{"spacing":{"blockGap":"48px"}},"layout":{"type":"flex","flexWrap":"nowrap"}} -->
<div class="wp-block-group woocommerce-coming-soon-social-login"><!-- wp:template-part {"slug":"coming-soon-social-links","theme":"woocommerce/woocommerce","tagName":"div"} /-->

<!-- wp:loginout {"style":{"elements":{"link":{"color":{"text":"#ffffff"}}},"color":{"background":"#000000"},"spacing":{"padding":{"top":"12px","bottom":"12px","left":"16px","right":"16px"}},"typography":{"fontSize":"14px","lineHeight":"1.2"},"border":{"radius":"6px"}},"fontFamily":"<?php echo esc_html( $body_font_family ); ?>"} /--></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->

<!-- wp:group {"style":{"layout":{"selfStretch":"fill","flexSize":null}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"center","verticalAlignment":"center"}} -->
<div class="wp-block-group"><!-- wp:heading {"textAlign":"center","level":1,"align":"wide","className":"woocommerce-coming-soon-banner","style":{"typography":{"fontSize":"48px","fontStyle":"normal","fontWeight":"400","lineHeight":"1.2"}},"fontFamily":"<?php echo esc_html( $heading_font_family ); ?>"} -->
<h1 class="wp-block-heading alignwide has-text-align-center woocommerce-coming-soon-banner has-<?php echo esc_html( $heading_font_family ); ?>-font-family" style="font-size:48px;font-style:normal;font-weight:400;line-height:1.2"><?php echo esc_html__( "Pardon our dust! We're working on something amazing — check back soon!", 'woocommerce' ); ?></h1>
<!-- /wp:heading --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"woocommerce-coming-soon-powered-by-woo","style":{"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|10"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group woocommerce-coming-soon-powered-by-woo" style="padding-top:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--10)"><!-- wp:group {"align":"wide","style":{"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"0"}}}} -->
<div class="wp-block-group alignwide" style="padding-top:var(--wp--preset--spacing--30);padding-bottom:0"><!-- wp:paragraph {"align":"center","style":{"elements":{"link":{"color":{"text":"var:preset|color|contrast"}}}},"textColor":"contrast-2","fontSize":"small"} -->
<p class="has-text-align-center has-contrast-2-color has-text-color has-link-color has-small-font-size">&nbsp;</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div></div>
<!-- /wp:cover -->
</div>
<!-- /wp:woocommerce/coming-soon -->
