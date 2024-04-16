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
<div class="wp-block-group alignwide has-background" style="background-color:#bea0f2;"><!-- wp:group {"align":"wide","layout":{"type":"flex","justifyContent":"space-between","flexWrap":"wrap"}} -->
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
	@font-face {
		font-family: 'Inter';
		src: url( <?php echo esc_url( WC()->plugin_url() . '/assets/fonts/Inter-VariableFont_slnt,wght.woff2' ); ?>) format('woff2');
		font-weight: 300 900;
		font-style: normal;
	}

	@font-face {
		font-family: 'Cardo';
		src: url( <?php echo esc_url( WC()->plugin_url() . '/assets/fonts/cardo_normal_400.woff2' ); ?>) format('woff2');
		font-weight: 400;
		font-style: normal;
	}
	/* Reset */
	h1, p, a {
		margin: 0;
		padding: 0;
		border: 0;
		vertical-align: baseline;
	}
	ol, ul {
		list-style: none;
	}
	a {
		text-decoration: none;
	}
	body,
	body.custom-background {
		margin: 0;
		background-color: #bea0f2;
		font-family: 'Inter', sans-serif;
		--wp--preset--color--contrast: #111111;
		--wp--style--global--wide-size: 1280px;
	}
	body .is-layout-constrained > .alignwide {
		margin: 0 auto;
	}
	.wp-container-core-group-is-layout-4.wp-container-core-group-is-layout-4 {
		justify-content: space-between;
	}
	.is-layout-flex {
		display: flex;
		flex-wrap: wrap;
		align-items: center;
		margin: 0;
	}
	.wp-site-blocks > .wp-block-group {
		padding: 20px min(6.5rem, 8vw);
	}
	.wp-block-site-title p {
		line-height: normal;
	}
	.wp-block-site-title a {
		font-weight: 600;
		font-size: 20px;
		font-style: normal;
		line-height: normal;
		letter-spacing: -0.4px;
		color: var(--wp--preset--color--contrast);
		text-decoration: none;
	}
	.wp-block-social-links {
		gap: 0.5em 18px;
	}
	.woocommerce-coming-soon-social-login {
		gap: 48px;
	}
	.wp-block-loginout {
		background-color: #000000;
		border-radius: 6px;
		display: flex;
		height: 40px;
		width: 74px;
		padding: 17px 16px;
		justify-content: center;
		align-items: center;
		gap: 10px;
		box-sizing: border-box;
	}
	.wp-block-loginout a {
		color: #ffffff;
		text-decoration: none;
		line-height: 17px;
		font-size: 14px;
		font-weight: 500;
	}
	.wp-block-spacer {
		margin: 0;
	}
	.woocommerce-coming-soon-banner-container {
		width: 100%;
		position: absolute;
		top: 50%;
		transform: translateY(-50%);
		padding-inline: min(6.5rem, 8vw);
		margin: 0;
	}
	.woocommerce-coming-soon-banner {
		font-size: 48px;
		font-weight: 400;
		line-height: 58px;
		font-family: 'Cardo', serif;
		letter-spacing: normal;
		text-align: center;
		font-style: normal;
		max-width: 820px;
		color: var(--wp--preset--color--contrast);
	}
	.woocommerce-coming-soon-powered-by-woo {
		position: fixed;
		bottom: 0;
		left: 0;
		width: 100%;
		--wp--preset--spacing--30: 0;
		--wp--preset--spacing--10: 35px;
	}
	.woocommerce-coming-soon-powered-by-woo p {
		font-style: normal;
		font-weight: 400;
		line-height: 160%; /* 19.2px */
		letter-spacing: -0.12px;
		color: #3C434A;
		font-size: 12px;
		font-family: Inter;
	}
	.woocommerce-coming-soon-powered-by-woo a {
		font-family: Inter;
	}
</style>

