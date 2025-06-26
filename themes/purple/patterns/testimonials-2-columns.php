<?php
/**
 * Title: Testimonials in two columns
 * Slug: woostarter/testimonials-2-columns
 * Categories: testimonials
 *
 */
?>

<!-- wp:group {"metadata":{"name":"Testimonials in two columns"},"align":"full","backgroundColor":"theme-5","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull has-theme-5-background-color has-background"><!-- wp:spacer {"height":"var:preset|spacing|70"} -->
<div style="height:var(--wp--preset--spacing--70)" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:heading {"textAlign":"center"} -->
<h2 class="wp-block-heading has-text-align-center">What they are saying</h2>
<!-- /wp:heading -->

<!-- wp:spacer {"height":"var:preset|spacing|50"} -->
<div style="height:var(--wp--preset--spacing--50)" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:columns {"align":"wide","style":{"spacing":{"blockGap":{"left":"var:preset|spacing|80"}}}} -->
<div class="wp-block-columns alignwide"><!-- wp:column -->
<div class="wp-block-column"><!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">“With high-quality materials and expert craftsmanship, our products are built to last and exceed your expectations.”</p>
<!-- /wp:paragraph -->

<!-- wp:image {"sizeSlug":"full","linkDestination":"none"} -->
<figure class="wp-block-image size-full"><img src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/logo-8.webp" alt=""/></figure>
<!-- /wp:image --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">“With high-quality materials and expert craftsmanship, our products are built to last and exceed your expectations.”</p>
<!-- /wp:paragraph -->
<!-- wp:image {"sizeSlug":"full","linkDestination":"none"} -->
<figure class="wp-block-image size-full"><img src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/logo-4.webp" alt=""/></figure>
<!-- /wp:image -->
</div>
<!-- /wp:column --></div>
<!-- /wp:columns -->

<!-- wp:spacer {"height":"var:preset|spacing|70"} -->
<div style="height:var(--wp--preset--spacing--70)" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer --></div>
<!-- /wp:group -->
