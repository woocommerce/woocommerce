<?php
/**
 * Title: About 2 columns with heading
 * Slug: purple/about-2-columns-with-heading
 * Categories: about, purple
 * Keywords: about, text, image, media
 * Description: A section with two columns of text, heading and an image.
 * Viewport width: 1440
 */
?>

<!-- wp:group {"metadata":{"name":"About 2 columns with heading","patternName":"purple/about-2-columns-with-heading","description":"A section with two columns of text, heading and an image.","categories":["about","purple"]},"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"},"margin":{"top":"0","bottom":"0"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull" style="margin-top:0;margin-bottom:0;padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50)"><!-- wp:columns {"align":"wide"} -->
<div class="wp-block-columns alignwide"><!-- wp:column {"width":"40%"} -->
<div class="wp-block-column" style="flex-basis:40%"><!-- wp:heading {"fontSize":"xx-large"} -->
<h2 class="wp-block-heading has-xx-large-font-size"><?php esc_html_e( 'About us', 'purple' ); ?></h2>
<!-- /wp:heading --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:paragraph -->
<p><?php esc_html_e( 'We create bold, high-quality pieces designed to bring joy to everyday life, blending playful design with honest craftsmanship.', 'purple' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:paragraph -->
<p><?php esc_html_e( 'We believe that clothing should feel good, look good, and do good—both for the people who wear it and the planet we share.', 'purple' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->

<!-- wp:image {"sizeSlug":"full","linkDestination":"none","align":"wide","style":{"spacing":{"margin":{"top":"var:preset|spacing|70"}}}} -->
<figure class="wp-block-image alignwide size-full" style="margin-top:var(--wp--preset--spacing--70)"><img src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/pattern-placeholder.webp" alt="<?php esc_attr_e( 'Placeholder image', 'purple' ); ?>"/></figure>
<!-- /wp:image --></div>
<!-- /wp:group -->
