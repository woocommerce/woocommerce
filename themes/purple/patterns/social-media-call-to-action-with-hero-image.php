<?php
/**
 * Title: Social media call to action with hero image
 * Slug: purple/social-media-call-to-action-with-hero-image
 * Categories: purple
 * Keywords: banner, call-to-action, media
 * Description: A social media call to action with a hero image.
 * Viewport width: 1440
 *
 * @package purple
 */

?>

<!-- wp:group {"metadata":{"name":"Social media call to action with hero image","patternName":"purple/social-media-call-to-action-with-hero-image","description":"A social media call to action with a hero image.","categories":["purple"]},"align":"full","style":{"spacing":{"margin":{"top":"0","bottom":"0"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull" style="margin-top:0;margin-bottom:0"><!-- wp:cover {"url":"<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/blue-sweater-cut.webp","alt":"<?php esc_attr_e( 'Model wearing a blue knit sweater', 'purple' ); ?>","dimRatio":20,"isUserOverlayColor":true,"focalPoint":{"x":0.5,"y":0.28},"minHeight":60,"minHeightUnit":"vh","sizeSlug":"full","align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50","left":"var:preset|spacing|50","right":"var:preset|spacing|50"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-cover alignfull" style="padding-top:var(--wp--preset--spacing--50);padding-right:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50);padding-left:var(--wp--preset--spacing--50);min-height:60vh"><img class="wp-block-cover__image-background size-full" alt="<?php esc_attr_e( 'Model wearing a blue knit sweater', 'purple' ); ?>" src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/blue-sweater-cut.webp" style="object-position:50% 28%" data-object-fit="cover" data-object-position="50% 28%"/><span aria-hidden="true" class="wp-block-cover__background has-background-dim-20 has-background-dim"></span><div class="wp-block-cover__inner-container"><!-- wp:heading {"style":{"typography":{"textAlign":"center"}}} -->
<h2 class="wp-block-heading has-text-align-center"><?php esc_html_e( 'Follow us on Instagram', 'purple' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"style":{"typography":{"fontStyle":"normal","textAlign":"center"}}} -->
<p class="has-text-align-center" style="font-style:normal"><?php
	printf(
		/* translators: %1$s: opening anchor tag. %2$s: closing anchor tag. */
		esc_html__( '%1$s@example%2$s', 'purple' ),
		'<a href="#">',
		'</a>'
	);
	?></p>
<!-- /wp:paragraph --></div></div>
<!-- /wp:cover --></div>
<!-- /wp:group -->
