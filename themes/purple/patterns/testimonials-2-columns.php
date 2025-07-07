<?php
/**
 * Title: Testimonials in two columns
 * Slug: woostarter/testimonials-2-columns
 * Categories: testimonials, text, media, columns
 * Keywords: testimonials, text, media
 * Description: A section with a heading and a 2 column testimonial grid.
 */
?>
<!-- wp:group {"metadata":{"name":"Testimonials in two columns"},"align":"full","backgroundColor":"theme-5","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull has-theme-5-background-color has-background"><!-- wp:spacer {"height":"var:preset|spacing|80"} -->
<div style="height:var(--wp--preset--spacing--80)" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:heading {"textAlign":"center"} -->
<h2 class="wp-block-heading has-text-align-center"><?php esc_html_e('What they are saying', 'woostarter');?></h2>
<!-- /wp:heading -->

<!-- wp:spacer {"height":"var:preset|spacing|50"} -->
<div style="height:var(--wp--preset--spacing--50)" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:columns {"align":"wide","style":{"spacing":{"blockGap":{"left":"var:preset|spacing|80"}}}} -->
<div class="wp-block-columns alignwide"><!-- wp:column -->
<div class="wp-block-column"><!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center"><?php esc_html_e('“With high-quality materials and expert craftsmanship, our products are built to last and exceed your expectations.”', 'woostarter');?></p>
<!-- /wp:paragraph -->
<!-- wp:spacer {"height":"var:preset|spacing|30"} -->
<div style="height:var(--wp--preset--spacing--30)" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->
<!-- wp:image {"width":"163px","sizeSlug":"full","linkDestination":"none","align":"center"} -->
<figure class="wp-block-image aligncenter size-full is-resized"><img src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/logo-1.webp" alt="<?php esc_html_e('Brand logo', 'woostarter');?>" style="width:163px"/></figure>
<!-- /wp:image --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center"><?php esc_html_e('“With high-quality materials and expert craftsmanship, our products are built to last and exceed your expectations.”', 'woostarter');?></p>
<!-- /wp:paragraph -->
 <!-- wp:spacer {"height":"var:preset|spacing|30"} -->
<div style="height:var(--wp--preset--spacing--30)" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->
<!-- wp:image {"width":"135px","sizeSlug":"full","linkDestination":"none","align":"center"} -->
<figure class="wp-block-image aligncenter size-full is-resized"><img src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/logo-2.webp" alt="<?php esc_html_e('Brand logo', 'woostarter');?>" style="width:135px"/></figure>
<!-- /wp:image -->
</div>
<!-- /wp:column --></div>
<!-- /wp:columns -->

<!-- wp:spacer {"height":"var:preset|spacing|80"} -->
<div style="height:var(--wp--preset--spacing--80)" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer --></div>
<!-- /wp:group -->
