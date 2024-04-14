<?php
/**
 * Title: Coming Soon Entire Site
 * Slug: woocommerce/coming-soon-entire-site
 * Categories: WooCommerce
 *
 * @package WooCommerce\Blocks
 */

?>

<!-- wp:group {"align":"wide","style":{"spacing":{"padding":{"top":"20px","bottom":"20px"}},"color":{"background":"#bea0f2"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignwide has-background" style="background-color:#bea0f2;padding-top:20px;padding-bottom:20px"><!-- wp:group {"align":"wide","layout":{"type":"flex","justifyContent":"space-between","flexWrap":"wrap"}} -->
<div class="wp-block-group alignwide"><!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|20"},"layout":{"selfStretch":"fit","flexSize":null}},"layout":{"type":"flex"}} -->
<div class="wp-block-group"><!-- wp:site-logo {"width":60} /-->

<!-- wp:group {"style":{"spacing":{"blockGap":"0px"}}} -->
<div class="wp-block-group"><!-- wp:site-title {"level":0} /--></div>
<!-- /wp:group --></div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"blockGap":"48px"}},"className":"woocommerce-coming-soon-social-login","layout":{"type":"flex","flexWrap":"nowrap"}} -->
<div class="wp-block-group woocommerce-coming-soon-social-login"><!-- wp:social-links {"iconColor":"contrast","iconColorValue":"#111111","style":{"layout":{"selfStretch":"fit","flexSize":null},"spacing":{"blockGap":{"left":"18px"}}},"className":"is-style-logos-only"} -->
<ul class="wp-block-social-links has-icon-color is-style-logos-only"><!-- wp:social-link {"url":"https://www.linkedin.com/","service":"linkedin"} /-->

<!-- wp:social-link {"url":"https://www.instagram.com","service":"instagram"} /-->

<!-- wp:social-link {"url":"https://www.facebook.com","service":"facebook"} /--></ul>
<!-- /wp:social-links -->

<!-- wp:loginout /--></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
<!-- wp:spacer {"height":"25px"} -->
<div style="height:25px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->
<!-- wp:group {"className":"woocommerce-coming-soon-banner-container","layout":{"type":"constrained"}} -->
<div class="wp-block-group woocommerce-coming-soon-banner-container"><!-- wp:heading {"textAlign":"center","level":1,"align":"wide","className":"woocommerce-coming-soon-banner"} -->
<h1 class="wp-block-heading alignwide has-text-align-center woocommerce-coming-soon-banner"><?php echo esc_html__( 'Pardon our dust! We\'re working on something amazing -- check back soon!', 'woocommerce' ); ?></h1>
<!-- /wp:heading --></div>
<!-- /wp:group -->
<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|10"}}},"className":"woocommerce-coming-soon-powered-by-woo","layout":{"type":"constrained"}} -->
<div class="wp-block-group woocommerce-coming-soon-powered-by-woo" style="padding-top:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--10)"><!-- wp:group {"align":"wide","style":{"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"0"}}}} -->
<div class="wp-block-group alignwide" style="padding-top:var(--wp--preset--spacing--30);padding-bottom:0"><!-- wp:paragraph {"align":"center","style":{"elements":{"link":{"color":{"text":"var:preset|color|contrast"}}}},"textColor":"contrast-2","fontSize":"small"} -->
<p class="has-text-align-center has-contrast-2-color has-text-color has-link-color has-small-font-size">
Powered by
<a style="text-decoration: none;" href="https://woocommerce.com" rel="nofollow">WooCommerce</a>
</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->

<style>
	body {
		background-color: #bea0f2;
	}
	.wp-block-loginout {
		background-color: #000000;
		padding: 7px 17px;
		border-radius: 6px;
	}
	.wp-block-loginout a {
		color: #ffffff;
		text-decoration: none;
		line-height: 17px;
		font-size: 14px;
		font-weight: 500;
	}
	.woocommerce-coming-soon-powered-by-woo {
		position: fixed;
		bottom: 0;
		left: 0;
		width: 100%;
	}
	body .is-layout-constrained > .woocommerce-coming-soon-banner.alignwide {
		max-width: 820px;
	}
	.woocommerce-coming-soon-banner-container {
		width: 100%;
		position: absolute;
		top: 50%;
		transform: translateY(-50%);
	}
	.woocommerce-coming-soon-banner {
		font-size: 48px;
		font-weight: 400;
		line-height: 58px;
	}
</style>

