<?php
/**
 * Title: Testimonials in two columns
 * Slug: purple/testimonials-in-two-columns
 * Categories: testimonials, purple
 * Keywords: testimonials, text, media
 * Description: A section with a heading and a 2 column testimonial grid.
 */
?>

<!-- wp:group {"metadata":{"name":"Testimonials in two columns","categories":["testimonials","purple"],"patternName":"purple/testimonials-in-two-columns","description":"A section with a heading and a 2 column testimonial grid."},"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|80","bottom":"var:preset|spacing|80"},"margin":{"top":"0","bottom":"0"}}},"backgroundColor":"theme-5","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull has-theme-5-background-color has-background" style="margin-top:0;margin-bottom:0;padding-top:var(--wp--preset--spacing--80);padding-bottom:var(--wp--preset--spacing--80)"><!-- wp:heading {"style":{"typography":{"textAlign":"center"}}} -->
<h2 class="wp-block-heading has-text-align-center"><?php esc_html_e( 'What they are saying', 'purple' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:group {"align":"wide","layout":{"type":"constrained","contentSize":"1160px"}} -->
<div class="wp-block-group alignwide"><!-- wp:columns {"align":"wide","style":{"spacing":{"blockGap":{"left":"var:preset|spacing|80"},"margin":{"top":"var:preset|spacing|60"}}}} -->
<div class="wp-block-columns alignwide" style="margin-top:var(--wp--preset--spacing--60)"><!-- wp:column {"width":""} -->
<div class="wp-block-column"><!-- wp:paragraph {"style":{"typography":{"textAlign":"center"},"elements":{"link":{"color":{"text":"var:preset|color|theme-2"}}}},"textColor":"theme-2"} -->
<p class="has-text-align-center has-theme-2-color has-text-color has-link-color"><?php esc_html_e( '“With high-quality materials and expert craftsmanship, our products are built to last and exceed your expectations.”', 'purple' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:image {"width":"163px","sizeSlug":"full","linkDestination":"none","align":"center","style":{"spacing":{"margin":{"top":"var:preset|spacing|50"}}}} -->
<figure class="wp-block-image aligncenter size-full is-resized" style="margin-top:var(--wp--preset--spacing--50)"><img src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/logo-1.webp" alt="<?php esc_attr_e( 'Brand logo', 'purple' ); ?>" style="width:163px;height:auto"/></figure>
<!-- /wp:image --></div>
<!-- /wp:column -->

<!-- wp:column {"width":""} -->
<div class="wp-block-column"><!-- wp:paragraph {"style":{"typography":{"textAlign":"center"},"elements":{"link":{"color":{"text":"var:preset|color|theme-2"}}}},"textColor":"theme-2"} -->
<p class="has-text-align-center has-theme-2-color has-text-color has-link-color"><?php esc_html_e( '“With high-quality materials and expert craftsmanship, our products are built to last and exceed your expectations.”', 'purple' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:image {"width":"135px","sizeSlug":"full","linkDestination":"none","align":"center","style":{"spacing":{"margin":{"top":"var:preset|spacing|50"}}}} -->
<figure class="wp-block-image aligncenter size-full is-resized" style="margin-top:var(--wp--preset--spacing--50)"><img src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/logo-2.webp" alt="<?php esc_attr_e( 'Brand logo', 'purple' ); ?>" style="width:135px;height:auto"/></figure>
<!-- /wp:image --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
