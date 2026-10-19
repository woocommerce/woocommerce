<?php
/**
 * Title: Three columns of images with heading and paragraph
 * Slug: purple/about-three-columns-of-images-with-heading-and-paragraph
 * Categories: about, purple
 * Keywords: about, intro, columns, heading
 * Description: A section with a heading and short paragraph above a row of three images.
 * Viewport width: 1440
 *
 * @package purple
 */

?>

<!-- wp:group {"metadata":{"name":"Three columns of images with heading and paragraph","categories":["about","purple"],"patternName":"purple/about-three-columns-of-images-with-heading-and-paragraph"},"align":"full","style":{"spacing":{"margin":{"top":"0","bottom":"0"},"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"},"blockGap":"var:preset|spacing|30"}},"layout":{"type":"constrained","justifyContent":"center"}} -->
<div class="wp-block-group alignfull" style="margin-top:0;margin-bottom:0;padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50)"><!-- wp:group {"align":"wide","style":{"spacing":{"blockGap":"var:preset|spacing|30"}},"layout":{"type":"grid","columnCount":3,"minimumColumnWidth":"16rem"}} -->
<div class="wp-block-group alignwide"><!-- wp:heading {"align":"wide","style":{"layout":{"columnSpan":2}}} -->
<h2 class="wp-block-heading alignwide"><?php esc_html_e( 'A world filled with boundless potential', 'purple' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"style":{"layout":{"selfStretch":"fixed","flexSize":"70%"},"typography":{"textAlign":"left"}}} -->
<p class="has-text-align-left"><?php esc_html_e( 'Our mission is to challenge the status quo and create meaningful, lasting impact through everything we do. Driven by a vision to inspire change.', 'purple' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:image {"aspectRatio":"3/4","scale":"cover","sizeSlug":"full","linkDestination":"none"} -->
<figure class="wp-block-image size-full"><img src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/storytelling-wool.webp" alt="<?php esc_attr_e( 'Macro photo of fluffy white wool fibers.', 'purple' ); ?>" style="aspect-ratio:3/4;object-fit:cover"/></figure>
<!-- /wp:image -->

<!-- wp:image {"aspectRatio":"3/4","scale":"cover","sizeSlug":"full","linkDestination":"none"} -->
<figure class="wp-block-image size-full"><img src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/green-sweater-hands.webp" alt="<?php esc_attr_e( 'Hands touching a soft green knit sweater sleeve.', 'purple' ); ?>" style="aspect-ratio:3/4;object-fit:cover"/></figure>
<!-- /wp:image -->

<!-- wp:image {"aspectRatio":"3/4","scale":"cover","focalPoint":{"x":0.74,"y":0.54},"sizeSlug":"full","linkDestination":"none"} -->
<figure class="wp-block-image size-full"><img src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/orange-sweater.webp" alt="<?php esc_attr_e( 'Model wearing a colourful knit sweater', 'purple' ); ?>" style="aspect-ratio:3/4;object-fit:cover;object-position:74% 54%"/></figure>
<!-- /wp:image --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
