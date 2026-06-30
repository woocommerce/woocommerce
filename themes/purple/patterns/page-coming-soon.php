<?php
/**
 * Title: Coming soon
 * Slug: purple/coming-soon
 * Categories: purple
 * Keywords: coming soon, starter, landing
 * Description: A coming soon page pattern.
 * Block Types: core/post-content
 * Post Types: page, wp_template
 * Viewport width: 1440
 */
?>

<!-- wp:columns {"lock":{"move":false,"remove":false},"metadata":{"name":"Coming soon: Split layout with image","categories":["purple"],"patternName":"purple/coming-soon"},"align":"full","className":"is-style-default","style":{"spacing":{"blockGap":{"top":"0","left":"0"},"margin":{"top":"0","bottom":"0"}}}} -->
<div class="wp-block-columns alignfull is-style-default" style="margin-top:0;margin-bottom:0"><!-- wp:column {"verticalAlignment":"center","className":"is-style-default","style":{"spacing":{"padding":{"right":"var:preset|spacing|50","left":"var:preset|spacing|50","top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}}} -->
<div class="wp-block-column is-vertically-aligned-center is-style-default" style="padding-top:var(--wp--preset--spacing--50);padding-right:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50);padding-left:var(--wp--preset--spacing--50)"><!-- wp:group {"layout":{"type":"constrained","contentSize":"520px","justifyContent":"left"}} -->
<div class="wp-block-group"><!-- wp:heading {"level":1} -->
<h1 class="wp-block-heading"><?php esc_html_e( 'Coming Soon', 'purple' ); ?></h1>
<!-- /wp:heading -->

<!-- wp:paragraph {"style":{"spacing":{"margin":{"top":"var:preset|spacing|10"}}}} -->
<p style="margin-top:var(--wp--preset--spacing--10)"><?php esc_html_e( 'Our store is in the works and will be launching soon.', 'purple' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:column -->

<!-- wp:column {"width":"50%"} -->
<div class="wp-block-column" style="flex-basis:50%"><!-- wp:cover {"url":"<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/pattern-placeholder.webp","alt":"<?php esc_attr_e( 'Placeholder image', 'purple' ); ?>","dimRatio":40,"isUserOverlayColor":true,"minHeight":100,"minHeightUnit":"vh","contentPosition":"bottom right","style":{"layout":{"selfStretch":"fit","flexSize":null},"spacing":{"padding":{"right":"var:preset|spacing|20","left":"var:preset|spacing|20","top":"var:preset|spacing|20","bottom":"var:preset|spacing|20"}},"color":[]},"layout":{"type":"default"}} -->
<div class="wp-block-cover has-custom-content-position is-position-bottom-right" style="padding-top:var(--wp--preset--spacing--20);padding-right:var(--wp--preset--spacing--20);padding-bottom:var(--wp--preset--spacing--20);padding-left:var(--wp--preset--spacing--20);min-height:100vh"><img class="wp-block-cover__image-background" alt="<?php esc_attr_e( 'Placeholder image', 'purple' ); ?>" src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/pattern-placeholder.webp" data-object-fit="cover"/><span aria-hidden="true" class="wp-block-cover__background has-background-dim-40 has-background-dim"></span><div class="wp-block-cover__inner-container"><!-- wp:paragraph {"className":"underline-link","style":{"elements":{"link":{"color":{"text":"var:preset|color|theme-1"}}},"typography":{"lineHeight":"1.71"}},"fontSize":"small"} -->
<p class="underline-link has-link-color has-small-font-size" style="line-height:1.71"><?php
	printf(
		/* translators: %1$s: opening anchor tag. %2$s: closing anchor tag. */
		esc_html__( 'All rights reserved. Designed with %1$sWooCommerce%2$s.', 'purple' ),
		'<a href="' . esc_url( __( 'https://woocommerce.com/', 'purple' ) ) . '" rel="nofollow">',
		'</a>'
	);
	?></p>
<!-- /wp:paragraph --></div></div>
<!-- /wp:cover --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->
