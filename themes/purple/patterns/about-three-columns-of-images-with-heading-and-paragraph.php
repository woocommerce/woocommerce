<?php
/**
 * Title: Three columns of images with heading and paragraph
 * Slug: purple/about-three-columns-of-images-with-heading-and-paragraph
 * Categories: about
 * Keywords: about, intro, columns, heading
 * Description: A section with a heading and short paragraph above a row of three image placeholders.
 */
?>

<!-- wp:group {"metadata":{"name":"Three columns of images with heading and paragraph","categories":["about"],"patternName":"purple/about-three-columns-of-images-with-heading-and-paragraph"},"align":"full","style":{"spacing":{"margin":{"top":"0","bottom":"0"},"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"},"blockGap":"var:preset|spacing|40"}},"layout":{"type":"constrained","justifyContent":"center"}} -->
<div class="wp-block-group alignfull" style="margin-top:0;margin-bottom:0;padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50)"><!-- wp:group {"align":"wide","style":{"spacing":{"blockGap":"var:preset|spacing|40"}},"layout":{"type":"grid","columnCount":3,"minimumColumnWidth":"16rem"}} -->
<div class="wp-block-group alignwide"><!-- wp:heading {"align":"wide","style":{"layout":{"columnSpan":2}}} -->
<h2 class="wp-block-heading alignwide"><?php esc_html_e( 'A world filled with boundless potential', 'purple' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"style":{"layout":{"selfStretch":"fixed","flexSize":"70%"},"typography":{"textAlign":"left"}}} -->
<p class="has-text-align-left"><?php esc_html_e( 'Our mission is to challenge the status quo and create meaningful, lasting impact through everything we do. Driven by a vision to inspire change.', 'purple' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:image {"aspectRatio":"3/4","scale":"cover","sizeSlug":"full","linkDestination":"none"} -->
<figure class="wp-block-image size-full"><img src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/pattern-placeholder.webp" alt="<?php esc_attr_e( 'Placeholder image', 'purple' ); ?>" style="aspect-ratio:3/4;object-fit:cover"/></figure>
<!-- /wp:image -->

<!-- wp:image {"aspectRatio":"3/4","scale":"cover","sizeSlug":"full","linkDestination":"none"} -->
<figure class="wp-block-image size-full"><img src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/pattern-placeholder.webp" alt="<?php esc_attr_e( 'Placeholder image', 'purple' ); ?>" style="aspect-ratio:3/4;object-fit:cover"/></figure>
<!-- /wp:image -->

<!-- wp:image {"aspectRatio":"3/4","scale":"cover","sizeSlug":"full","linkDestination":"none"} -->
<figure class="wp-block-image size-full"><img src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/pattern-placeholder.webp" alt="<?php esc_attr_e( 'Placeholder image', 'purple' ); ?>" style="aspect-ratio:3/4;object-fit:cover"/></figure>
<!-- /wp:image --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
