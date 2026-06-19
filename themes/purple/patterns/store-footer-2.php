<?php
/**
 * Title: Store footer 2
 * Slug: purple/store-footer-2
 * Categories: footer
 * Keywords: footer, contact, social, links
 * Description: A three-column store footer with site title and address, contact info, social links, and a bottom row with legal links and copyright.
 */
?>
<!-- wp:group {"metadata":{"name":"Store Footer"},"align":"full","style":{"spacing":{"margin":{"top":"0","bottom":"0"},"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull" style="margin-top:0;margin-bottom:0;padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)"><!-- wp:columns {"align":"wide","style":{"spacing":{"blockGap":{"top":"var:preset|spacing|20","left":"var:preset|spacing|20"}}}} -->
<div class="wp-block-columns alignwide"><!-- wp:column {"width":"50%"} -->
<div class="wp-block-column" style="flex-basis:50%"><!-- wp:group {"style":{"spacing":{"blockGap":"10px","padding":{"right":"0","left":"0"}}},"layout":{"type":"constrained","justifyContent":"left"}} -->
<div class="wp-block-group" style="padding-right:0;padding-left:0"><!-- wp:site-title {"level":0} /-->

<!-- wp:paragraph {"style":{"spacing":{"margin":{"top":"0.625rem"}}}} -->
<p style="margin-top:0.625rem"><?php esc_html_e( '123 Example St.', 'purple' ); ?><br><?php esc_html_e( 'San Francisco, CA', 'purple' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:column -->

<!-- wp:column {"width":"25%","layout":{"type":"default"}} -->
<div class="wp-block-column" style="flex-basis:25%"><!-- wp:paragraph {"style":{"typography":{"fontStyle":"normal","fontWeight":"500"}}} -->
<p style="font-style:normal;font-weight:500"><?php esc_html_e( 'Contact', 'purple' ); ?></p>
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
<div class="wp-block-column" style="flex-basis:25%"><!-- wp:paragraph {"style":{"typography":{"fontStyle":"normal","fontWeight":"500"}}} -->
<p style="font-style:normal;font-weight:500"><?php esc_html_e( 'Follow us', 'purple' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:social-links {"size":"has-normal-icon-size","className":"is-style-logos-only","style":{"spacing":{"blockGap":{"left":"var:preset|spacing|20"},"margin":{"top":"0.625rem"}}}} -->
<ul class="wp-block-social-links has-normal-icon-size is-style-logos-only" style="margin-top:0.625rem"><!-- wp:social-link {"url":"#","service":"instagram"} /-->

<!-- wp:social-link {"url":"#","service":"x"} /-->

<!-- wp:social-link {"url":"#","service":"tiktok"} /--></ul>
<!-- /wp:social-links --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->

<!-- wp:group {"align":"wide","style":{"spacing":{"margin":{"top":"var:preset|spacing|30"},"blockGap":"0.63rem"}},"layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between"}} -->
<div class="wp-block-group alignwide" style="margin-top:var(--wp--preset--spacing--30)"><!-- wp:group {"fontSize":"small","layout":{"type":"flex","flexWrap":"wrap"}} -->
<div class="wp-block-group has-small-font-size"><!-- wp:paragraph -->
<p><a href="#"><?php esc_html_e( 'Shipping &amp; returns', 'purple' ); ?></a></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p><a href="/privacy-policy"><?php esc_html_e( 'Privacy policy', 'purple' ); ?></a></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p><a href="/your-privacy-choices"><?php esc_html_e( 'Privacy choices', 'purple' ); ?></a></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p><a href="#"><?php esc_html_e( 'Terms &amp; conditions', 'purple' ); ?></a></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:paragraph {"style":{"typography":{"textAlign":"left"}}} -->
<p class="has-text-align-left">&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php esc_html_e( 'all rights reserved. Designed with', 'purple' ); ?> <a href="https://wordpress.org">WordPress</a>.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
