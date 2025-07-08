<?php
/**
 * Title: Testimonial with logo
 * Slug: purple/testimonial-with-logo
 * Categories: testimonials, text, media
 * Keywords: testimonials, text, media
 * Description: A testimonial with centered text and a logo.
 */
?>
<!-- wp:group {"metadata":{"name":"Testimonial with logo"},"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|80","bottom":"var:preset|spacing|80","left":"var:preset|spacing|50","right":"var:preset|spacing|50"}}},"backgroundColor":"theme-5","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull has-theme-5-background-color has-background" style="padding-top:var(--wp--preset--spacing--80);padding-right:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--80);padding-left:var(--wp--preset--spacing--50)"><!-- wp:heading {"textAlign":"center","style":{"typography":{"textTransform":"uppercase","letterSpacing":"0.07rem","fontStyle":"normal","fontWeight":"400"}}} -->
<h2 class="wp-block-heading has-text-align-center" style="font-style:normal;font-weight:400;letter-spacing:0.07rem;text-transform:uppercase"><?php esc_html_e('In the press', 'purple');?></h2>
<!-- /wp:heading -->

<!-- wp:spacer {"height":"var:preset|spacing|40"} -->
<div style="height:var(--wp--preset--spacing--40)" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center"><?php esc_html_e('“With high-quality materials and expert craftsmanship, our products are built to last and exceed your expectations.”', 'purple');?></p>
<!-- /wp:paragraph -->

<!-- wp:spacer {"height":"var:preset|spacing|30"} -->
<div style="height:var(--wp--preset--spacing--30)" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:image {"width":"130px","sizeSlug":"full","linkDestination":"none","align":"center"} -->
<figure class="wp-block-image aligncenter size-full is-resized"><img src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/logo-3.webp" alt="<?php esc_attr_e('Brand logo', 'purple');?>" class="" style="width:130px"/></figure>
<!-- /wp:image --></div>
<!-- /wp:group -->