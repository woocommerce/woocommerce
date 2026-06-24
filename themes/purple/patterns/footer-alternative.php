<?php
/**
 * Title: Footer alternative
 * Slug: purple/footer-alternative
 * Categories: footer
 * Keywords: footer, contact, social, links
 * Block Types: core/template-part/footer
 * Description: A three-column store footer with site title and address, contact info, social links, and a bottom row with legal links and copyright.
 * Viewport width: 1440
 */
?>

<!-- wp:group {"metadata":{"name":"Store Footer","categories":["footer"],"patternName":"purple/footer-alternative"},"align":"full","style":{"spacing":{"margin":{"top":"0","bottom":"0"},"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull" style="margin-top:0;margin-bottom:0;padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)"><!-- wp:columns {"align":"wide","style":{"spacing":{"blockGap":{"top":"var:preset|spacing|20","left":"var:preset|spacing|20"}}}} -->
<div class="wp-block-columns alignwide"><!-- wp:column {"width":"50%"} -->
<div class="wp-block-column" style="flex-basis:50%"><!-- wp:site-title {"level":0} /-->

<!-- wp:group {"style":{"spacing":{"padding":{"right":"0","left":"0"},"margin":{"top":"var:preset|spacing|20"}}},"layout":{"type":"constrained","justifyContent":"left","contentSize":"260px"}} -->
<div class="wp-block-group" style="margin-top:var(--wp--preset--spacing--20);padding-right:0;padding-left:0"><!-- wp:paragraph {"style":{"spacing":{"margin":{"top":"0.625rem"}}}} -->
<p style="margin-top:0.625rem"><?php esc_html_e( '123 Example St.', 'purple' ); ?><br><?php esc_html_e( 'San Francisco, CA', 'purple' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:column -->

<!-- wp:column {"width":"25%","layout":{"type":"default"}} -->
<div class="wp-block-column" style="flex-basis:25%"><!-- wp:paragraph {"style":{"typography":{"fontStyle":"normal","fontWeight":"500"},"elements":{"link":{"color":{"text":"var:preset|color|theme-2"}}}},"textColor":"theme-2"} -->
<p class="has-theme-2-color has-text-color has-link-color" style="font-style:normal;font-weight:500"><?php esc_html_e( 'Contact', 'purple' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:group {"style":{"spacing":{"blockGap":"0","margin":{"top":"0.625rem"}}},"layout":{"type":"flex","orientation":"vertical"}} -->
<div class="wp-block-group" style="margin-top:0.625rem"><!-- wp:paragraph -->
<p><a href="tel:"><?php esc_html_e( '123 456 7890', 'purple' ); ?></a></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p><a href="mailto:"><?php esc_html_e( 'contact@example.com', 'purple' ); ?></a></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:column -->

<!-- wp:column {"width":"25%"} -->
<div class="wp-block-column" style="flex-basis:25%"><!-- wp:paragraph {"style":{"typography":{"fontStyle":"normal","fontWeight":"500"},"elements":{"link":{"color":{"text":"var:preset|color|theme-2"}}}},"textColor":"theme-2"} -->
<p class="has-theme-2-color has-text-color has-link-color" style="font-style:normal;font-weight:500"><?php esc_html_e( 'Follow us', 'purple' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:social-links {"iconColor":"theme-2","iconColorValue":"#111111","size":"has-normal-icon-size","className":"is-style-logos-only","style":{"spacing":{"blockGap":{"left":"var:preset|spacing|30"},"margin":{"top":"0.625rem"}}}} -->
<ul class="wp-block-social-links has-normal-icon-size has-icon-color is-style-logos-only" style="margin-top:0.625rem"><!-- wp:social-link {"url":"#","service":"instagram"} /-->

<!-- wp:social-link {"url":"#","service":"x"} /-->

<!-- wp:social-link {"url":"#","service":"tiktok"} /--></ul>
<!-- /wp:social-links --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->

<!-- wp:group {"align":"wide","style":{"spacing":{"margin":{"top":"var:preset|spacing|30"},"blockGap":"0.63rem"}},"layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between"}} -->
<div class="wp-block-group alignwide" style="margin-top:var(--wp--preset--spacing--30)"><!-- wp:group {"fontSize":"small","layout":{"type":"flex","flexWrap":"wrap"}} -->
<div class="wp-block-group has-small-font-size"><!-- wp:paragraph -->
<p><a href="#"><?php esc_html_e( 'Shipping & returns', 'purple' ); ?></a></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p><a href="/privacy-policy"><?php esc_html_e( 'Privacy policy', 'purple' ); ?></a></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p><a href="#"><?php esc_html_e( 'Terms & conditions', 'purple' ); ?></a></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:paragraph {"className":"underline-link","style":{"typography":{"textAlign":"left"},"elements":{"link":{"color":{"text":"var:preset|color|theme-4"}}}},"textColor":"theme-4","fontSize":"small"} -->
<p class="has-text-align-left underline-link has-theme-4-color has-text-color has-link-color has-small-font-size">&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php
	/* Translators: WooCommerce link. */
	$woocommerce_link = '<a href="' . esc_url( __( 'https://woocommerce.com/', 'purple' ) ) . '" rel="nofollow">WooCommerce</a>';
	echo sprintf(
		/* Translators: Designed with WooCommerce */
		esc_html__( 'All rights reserved. Designed with %1$s.', 'purple' ),
		$woocommerce_link
	);
	?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
