<?php
/**
 * Title: Testimonial with logo
 * Slug: purple/testimonial-with-logo
 * Categories: testimonials, purple
 * Keywords: testimonials, text, media
 * Description: A testimonial with centered text and a logo.
 * Viewport width: 1440
 */
?>

<!-- wp:group {"metadata":{"name":"Testimonial with logo","categories":["testimonials","purple"],"patternName":"purple/testimonial-with-logo"},"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|140","bottom":"var:preset|spacing|140"},"margin":{"top":"0","bottom":"0"}}},"backgroundColor":"theme-5","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull has-theme-5-background-color has-background" style="margin-top:0;margin-bottom:0;padding-top:var(--wp--preset--spacing--140);padding-bottom:var(--wp--preset--spacing--140)"><!-- wp:heading {"style":{"typography":{"textTransform":"uppercase","letterSpacing":"0.08rem","textAlign":"center","fontStyle":"normal","lineHeight":"1.28"}},"fontSize":"small"} -->
<h2 class="wp-block-heading has-text-align-center has-small-font-size" style="font-style:normal;letter-spacing:0.08rem;line-height:1.28;text-transform:uppercase"><?php esc_html_e('In the press', 'purple');?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"style":{"typography":{"textAlign":"center"},"spacing":{"margin":{"top":"var:preset|spacing|70"}},"elements":{"link":{"color":{"text":"var:preset|color|theme-2"}}}},"textColor":"theme-2","fontSize":"large"} -->
<p class="has-text-align-center has-theme-2-color has-text-color has-link-color has-large-font-size" style="margin-top:var(--wp--preset--spacing--70)"><?php esc_html_e('“With high-quality materials and expert craftsmanship, our products are built to last and exceed your expectations.”', 'purple');?></p>
<!-- /wp:paragraph -->

<!-- wp:image {"width":"130px","sizeSlug":"full","linkDestination":"none","align":"center","style":{"spacing":{"margin":{"top":"var:preset|spacing|50"}}}} -->
<figure class="wp-block-image aligncenter size-full is-resized" style="margin-top:var(--wp--preset--spacing--50)"><img src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/logo-3.webp" alt="<?php esc_attr_e('Brand logo', 'purple');?>" style="width:130px;height:auto"/></figure>
<!-- /wp:image --></div>
<!-- /wp:group -->
