<?php
/**
 * Title: Testimonials in two columns
 * Slug: purple/testimonials-two-columns
 * Categories: testimonials, purple
 * Keywords: testimonials, text, media
 * Description: A section with a heading and a 2 column testimonial grid.
 */
?>
<!-- wp:group {"metadata":{"name":"Testimonials in two columns","categories":["testimonials","purple"]},"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|80","bottom":"var:preset|spacing|80"}}},"backgroundColor":"theme-5","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull has-theme-5-background-color has-background" style="padding-top:var(--wp--preset--spacing--80);padding-bottom:var(--wp--preset--spacing--80)"><!-- wp:heading {"style":{"typography":{"textAlign":"center"}}} -->
<h2 class="wp-block-heading has-text-align-center"><?php esc_html_e('What they are saying', 'purple');?></h2>
<!-- /wp:heading -->

<!-- wp:columns {"align":"wide","style":{"spacing":{"blockGap":{"left":"var:preset|spacing|80"},"margin":{"top":"var:preset|spacing|60"}}}} -->
<div class="wp-block-columns alignwide" style="margin-top:var(--wp--preset--spacing--60)"><!-- wp:column {"width":"","style":{"spacing":{"padding":{"left":"var:preset|spacing|80"}}}} -->
<div class="wp-block-column" style="padding-left:var(--wp--preset--spacing--80)"><!-- wp:paragraph {"style":{"typography":{"textAlign":"center"}}} -->
<p class="has-text-align-center"><?php esc_html_e('“With high-quality materials and expert craftsmanship, our products are built to last and exceed your expectations.”', 'purple');?></p>
<!-- /wp:paragraph -->

<!-- wp:image {"width":"163px","sizeSlug":"full","linkDestination":"none","align":"center","style":{"spacing":{"margin":{"top":"var:preset|spacing|50"}}}} -->
<figure class="wp-block-image aligncenter size-full is-resized" style="margin-top:var(--wp--preset--spacing--50)"><img src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/logo-1.webp" alt="<?php esc_attr_e('Brand logo', 'purple');?>" style="width:163px;height:auto"/></figure>
<!-- /wp:image --></div>
<!-- /wp:column -->

<!-- wp:column {"width":"","style":{"spacing":{"padding":{"right":"var:preset|spacing|80"}}}} -->
<div class="wp-block-column" style="padding-right:var(--wp--preset--spacing--80)"><!-- wp:paragraph {"style":{"typography":{"textAlign":"center"}}} -->
<p class="has-text-align-center"><?php esc_html_e('“With high-quality materials and expert craftsmanship, our products are built to last and exceed your expectations.”', 'purple');?></p>
<!-- /wp:paragraph -->

<!-- wp:image {"width":"135px","sizeSlug":"full","linkDestination":"none","align":"center","style":{"spacing":{"margin":{"top":"var:preset|spacing|50"}}}} -->
<figure class="wp-block-image aligncenter size-full is-resized" style="margin-top:var(--wp--preset--spacing--50)"><img src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/logo-2.webp" alt="<?php esc_attr_e('Brand logo', 'purple');?>" style="width:135px;height:auto"/></figure>
<!-- /wp:image --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->