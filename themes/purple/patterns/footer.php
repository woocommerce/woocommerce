<?php
/**
 * Title: Footer
 * Slug: purple/footer
 * Categories: footer, purple
 * Keywords: footer, contact, social, navigation, links
 * Description: A footer pattern with social links and navigation.
 * Block Types: core/template-part/footer
 * Viewport width: 1440
 */
?>

<!-- wp:group {"metadata":{"name":"Footer","patternName":"purple/footer","description":"A footer pattern with social links and navigation.","categories":["footer","purple"]},"align":"full","className":"is-style-default","style":{"spacing":{"margin":{"top":"0","bottom":"0"},"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull is-style-default" style="margin-top:0;margin-bottom:0;padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--50)"><!-- wp:columns {"align":"wide","style":{"spacing":{"blockGap":{"top":"var:preset|spacing|40","left":"var:preset|spacing|50"}}}} -->
<div class="wp-block-columns alignwide"><!-- wp:column {"width":"40%","layout":{"type":"default"}} -->
<div class="wp-block-column" style="flex-basis:40%"><!-- wp:site-title {"level":0} /-->

<!-- wp:group {"layout":{"type":"constrained","contentSize":"260px","justifyContent":"left"}} -->
<div class="wp-block-group"><!-- wp:site-tagline {"style":{"spacing":{"margin":{"top":"var:preset|spacing|20"}}}} /--></div>
<!-- /wp:group -->

<!-- wp:social-links {"iconColor":"theme-2","iconColorValue":"#111111","size":"has-normal-icon-size","className":"is-style-logos-only","style":{"spacing":{"blockGap":{"left":"var:preset|spacing|30"},"margin":{"top":"var:preset|spacing|50"}}}} -->
<ul class="wp-block-social-links has-normal-icon-size has-icon-color is-style-logos-only" style="margin-top:var(--wp--preset--spacing--50)"><!-- wp:social-link {"url":"#","service":"instagram"} /-->

<!-- wp:social-link {"url":"#","service":"x"} /-->

<!-- wp:social-link {"url":"#","service":"whatsapp"} /--></ul>
<!-- /wp:social-links --></div>
<!-- /wp:column -->

<!-- wp:column {"width":"30%"} -->
<div class="wp-block-column" style="flex-basis:30%"></div>
<!-- /wp:column -->

<!-- wp:column {"width":"15%","layout":{"type":"default"}} -->
<div class="wp-block-column" style="flex-basis:15%"><!-- wp:heading {"level":4,"style":{"typography":{"textAlign":"left"}}} -->
<h4 class="wp-block-heading has-text-align-left"><?php esc_html_e( 'Shop', 'purple' ); ?></h4>
<!-- /wp:heading -->

<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|20"}},"layout":{"type":"flex","orientation":"vertical"}} -->
<div class="wp-block-group"><!-- wp:navigation {"overlayMenu":"never","style":{"spacing":{"blockGap":"var:preset|spacing|20"}},"layout":{"type":"flex","orientation":"vertical"}} -->
<!-- wp:navigation-link {"label":"<?php esc_html_e( 'Ceramics', 'purple' ); ?>","url":"#"} /-->

<!-- wp:navigation-link {"label":"<?php esc_html_e( 'Furniture', 'purple' ); ?>","url":"#"} /-->

<!-- wp:navigation-link {"label":"<?php esc_html_e( 'Accessories', 'purple' ); ?>","url":"#"} /-->

<!-- wp:navigation-link {"label":"<?php esc_html_e( 'Clothing', 'purple' ); ?>","url":"#"} /-->
<!-- /wp:navigation --></div>
<!-- /wp:group --></div>
<!-- /wp:column -->

<!-- wp:column {"width":"15%","layout":{"type":"default"}} -->
<div class="wp-block-column" style="flex-basis:15%"><!-- wp:heading {"level":4,"style":{"typography":{"textAlign":"left"}}} -->
<h4 class="wp-block-heading has-text-align-left"><?php esc_html_e( 'Info', 'purple' ); ?></h4>
<!-- /wp:heading -->

<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|20"}},"layout":{"type":"flex","orientation":"vertical"}} -->
<div class="wp-block-group"><!-- wp:navigation {"overlayMenu":"never","style":{"spacing":{"blockGap":"var:preset|spacing|20"}},"layout":{"type":"flex","orientation":"vertical"}} -->
<!-- wp:navigation-link {"label":"<?php esc_html_e( 'About', 'purple' ); ?>","url":"#"} /-->

<!-- wp:navigation-link {"label":"<?php esc_html_e( 'Contact us', 'purple' ); ?>","url":"#"} /-->

<!-- wp:navigation-link {"label":"<?php esc_html_e( 'FAQs', 'purple' ); ?>","url":"#"} /-->

<!-- wp:navigation-link {"label":"<?php esc_html_e( 'Blog', 'purple' ); ?>","url":"#"} /-->
<!-- /wp:navigation --></div>
<!-- /wp:group --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->

<!-- wp:group {"align":"wide","style":{"spacing":{"blockGap":"var:preset|spacing|30","margin":{"top":"var:preset|spacing|50"},"padding":{"top":"var:preset|spacing|40"}}},"layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between"}} -->
<div class="wp-block-group alignwide" style="margin-top:var(--wp--preset--spacing--50);padding-top:var(--wp--preset--spacing--40)"><!-- wp:group {"align":"full","style":{"spacing":{"padding":{"right":"0","left":"0"},"blockGap":"6px"},"typography":{"lineHeight":"1.71"}},"layout":{"type":"flex","flexWrap":"wrap","justifyContent":"left"}} -->
<div class="wp-block-group alignfull" style="padding-right:0;padding-left:0;line-height:1.71"><!-- wp:paragraph {"style":{"typography":{"textTransform":"uppercase","textAlign":"left"}},"fontSize":"small"} -->
<p class="has-text-align-left has-small-font-size" style="text-transform:uppercase">&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:site-title {"level":0,"style":{"typography":{"fontStyle":"normal","fontWeight":"300","textTransform":"none","letterSpacing":"0px"},"elements":{"link":{"color":{"text":"var:preset|color|theme-4"}}}},"textColor":"theme-4","fontSize":"small"} /-->

<!-- wp:paragraph {"className":"underline-link","style":{"typography":{"textAlign":"left"},"elements":{"link":{"color":{"text":"var:preset|color|theme-4"}}}},"textColor":"theme-4","fontSize":"small"} -->
<p class="has-text-align-left underline-link has-theme-4-color has-text-color has-link-color has-small-font-size"><?php
	/* Translators: WooCommerce link. */
	$woocommerce_link = '<a href="' . esc_url( __( 'https://woocommerce.com/', 'purple' ) ) . '" rel="nofollow">WooCommerce</a>';
	echo sprintf(
		/* Translators: Designed with WooCommerce */
		esc_html__( 'All rights reserved. Designed with %1$s.', 'purple' ),
		$woocommerce_link
	);
	?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|40"},"typography":{"lineHeight":"1.71"}},"layout":{"type":"flex","flexWrap":"nowrap"}} -->
<div class="wp-block-group" style="line-height:1.71"><!-- wp:paragraph {"fontSize":"small"} -->
<p class="has-small-font-size"><a href="#"><?php esc_html_e( 'Shipping & returns', 'purple' ); ?></a></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"fontSize":"small"} -->
<p class="has-small-font-size"><a href="#"><?php esc_html_e( 'Privacy Policy', 'purple' ); ?></a></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"fontSize":"small"} -->
<p class="has-small-font-size"><a href="#"><?php esc_html_e( 'Terms & Conditions', 'purple' ); ?></a></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
