<?php
/**
 * Title: About 2 columns with heading
 * Slug: woostarter/about-2-columns-with-heading
 * Categories: text, about, media
 * Keywords: about, text
 * Description: A section with two columns of text, heading and an image.
 */
?>
<!-- wp:group {"metadata":{"name":"About 2 columns with heading"},"align":"full","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull"><!-- wp:spacer {"height":"var:preset|spacing|50"} -->
<div style="height:var(--wp--preset--spacing--50)" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:columns {"align":"wide"} -->
<div class="wp-block-columns alignwide"><!-- wp:column {"width":"40%"} -->
<div class="wp-block-column" style="flex-basis:40%"><!-- wp:heading -->
<h2 class="wp-block-heading"><?php esc_html_e('About us', 'woostarter');?></h2>
<!-- /wp:heading --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:paragraph {"style":{"elements":{"link":{"color":{"text":"var:preset|color|theme-4"}}}},"textColor":"theme-4"} -->
<p class="has-theme-4-color has-text-color has-link-color"><?php esc_html_e('We create bold, high-quality pieces designed to bring joy to everyday life, blending playful design with honest craftsmanship.', 'woostarter');?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:paragraph {"style":{"elements":{"link":{"color":{"text":"var:preset|color|theme-4"}}}},"textColor":"theme-4"} -->
<p class="has-theme-4-color has-text-color has-link-color"><?php esc_html_e('We believe that clothing should feel good, look good, and do good—both for the people who wear it and the planet we share.', 'woostarter');?>	</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->

<!-- wp:spacer {"height":"var:preset|spacing|60"} -->
<div style="height:var(--wp--preset--spacing--60)" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:image {"sizeSlug":"full","linkDestination":"none","align":"wide"} -->
<figure class="wp-block-image alignwide size-full"><img src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/pattern-placeholder.webp" alt="<?php esc_html_e('Placeholder image', 'woostarter');?>"/></figure>
<!-- /wp:image -->

<!-- wp:spacer {"height":"var:preset|spacing|50"} -->
<div style="height:var(--wp--preset--spacing--50)" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer --></div>
<!-- /wp:group -->