<?php
/**
 * Title: Default Coming Soon
 * Slug: woocommerce/page-coming-soon-default
 * Categories: WooCommerce
 * Template Types: coming-soon
 * Inserter: false
 */

?>

<!-- wp:woocommerce/coming-soon {"comingSoonPatternId":"page-coming-soon-default","className":"woocommerce-coming-soon-default"} -->
<div class="wp-block-woocommerce-coming-soon woocommerce-coming-soon-default"><!-- wp:cover {"customOverlayColor":"transparent","isUserOverlayColor":true,"minHeight":100,"minHeightUnit":"vh","className":"coming-soon-is-vertically-aligned-center coming-soon-cover","style":{"spacing":{"padding":{"top":"0px","bottom":"0px","left":"24px","right":"24px"}},"color":{"text":"inherit"},"elements":{"link":{"color":{"text":"inherit"}}}},"layout":{"type":"constrained","wideSize":"1280px"}} -->
<div class="wp-block-cover coming-soon-is-vertically-aligned-center coming-soon-cover has-text-color has-link-color" style="color:inherit;padding-top:0px;padding-right:24px;padding-bottom:0px;padding-left:24px;min-height:100vh"><span aria-hidden="true" class="wp-block-cover__background has-background-dim-100 has-background-dim" style="background-color:transparent"></span><div class="wp-block-cover__inner-container"><!-- wp:group {"className":"woocommerce-coming-soon-banner-container","style":{"dimensions":{"minHeight":"100vh"},"spacing":{"blockGap":"0px"}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"stretch"}} -->
<div class="wp-block-group woocommerce-coming-soon-banner-container" style="min-height:100vh"><!-- wp:group {"align":"wide","className":"woocommerce-coming-soon-header has-background","style":{"spacing":{"padding":{"bottom":"14px","top":"26px"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignwide woocommerce-coming-soon-header has-background" style="padding-top:26px;padding-bottom:14px"><!-- wp:group {"align":"wide","layout":{"type":"flex","justifyContent":"space-between","flexWrap":"nowrap"}} -->
<div class="wp-block-group alignwide"><!-- wp:group {"className":"woocommerce-coming-soon-header-logo-title","style":{"spacing":{"blockGap":"var:preset|spacing|20"},"layout":{"selfStretch":"fit","flexSize":null}},"layout":{"type":"flex"}} -->
<div class="wp-block-group woocommerce-coming-soon-header-logo-title"><!-- wp:site-logo {"width":60,"style":{"layout":{"selfStretch":"fit","flexSize":null}}} /-->

<!-- wp:group {"style":{"spacing":{"blockGap":"0px"}}} -->
<div class="wp-block-group"><!-- wp:site-title {"level":0} /--></div>
<!-- /wp:group --></div>
<!-- /wp:group -->

<!-- wp:buttons {"className":"woocommerce-coming-soon-header-login"} -->
<div class="wp-block-buttons woocommerce-coming-soon-header-login"><!-- wp:button {"className":"is-style-outline"} -->
<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( wp_login_url() ); ?>"><?php esc_html_e( 'Log in', 'woocommerce' ); ?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->

<!-- wp:group {"style":{"layout":{"selfStretch":"fill","flexSize":null}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"center","verticalAlignment":"center"}} -->
<div class="wp-block-group"><!-- wp:heading {"textAlign":"center","level":1,"align":"wide","className":"woocommerce-coming-soon-banner"} -->
<h1 class="wp-block-heading alignwide has-text-align-center woocommerce-coming-soon-banner"><?php echo esc_html( sprintf( /* translators: %s is the site name */ __( '%s is coming soon', 'woocommerce' ), get_bloginfo( 'name' ) ) ); ?></h1>
<!-- /wp:heading -->

<?php if ( get_bloginfo( 'description' ) ) : ?>
<!-- wp:paragraph {"align":"center","fontSize":"medium"} -->
<p class="has-text-align-center has-medium-font-size"><?php echo esc_html( get_bloginfo( 'description' ) ); ?></p>
<!-- /wp:paragraph -->
<?php endif; ?>
</div>
<!-- /wp:group -->

<!-- wp:group {"className":"woocommerce-coming-soon-powered-by-woo","style":{"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|10"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group woocommerce-coming-soon-powered-by-woo" style="padding-top:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--10)"><!-- wp:group {"style":{"spacing":{"padding":{"bottom":"var:preset|spacing|30"}}},"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-group" style="padding-bottom:var(--wp--preset--spacing--30)"><!-- wp:template-part {"slug":"coming-soon-social-links","theme":"woocommerce/woocommerce","tagName":"div"} /--></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div></div>
<!-- /wp:cover -->
</div>
<!-- /wp:woocommerce/coming-soon -->
