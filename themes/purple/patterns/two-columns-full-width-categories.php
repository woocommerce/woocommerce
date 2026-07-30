<?php
/**
 * Title: Two columns full width categories
 * Slug: purple/two-columns-full-width-categories
 * Categories: woo-commerce, purple
 * Keywords: columns, gallery, media, featured, category
 * Description: A section with two full width Featured Category blocks.
 * Viewport width: 1440
 *
 * @package purple
 */

?>

<!-- wp:group {"metadata":{"name":"Two columns full width categories","categories":["woo-commerce","purple"],"patternName":"purple/two-columns-full-width-categories","description":"A section with two full width Featured Category blocks."},"align":"full","style":{"spacing":{"margin":{"top":"0","bottom":"0"}}},"layout":{"type":"default"}} -->
<div class="wp-block-group alignfull" style="margin-top:0;margin-bottom:0"><!-- wp:columns {"align":"wide","style":{"spacing":{"blockGap":{"top":"0","left":"0"}}}} -->
<div class="wp-block-columns alignwide"><!-- wp:column {"verticalAlignment":"center","width":"50%"} -->
<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:50%"><!-- wp:cover {"url":"<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/orange-sweater-woman-cropped.webp","alt":"<?php esc_attr_e( 'Female model wearing a colourful knit sweater in a colourful background', 'purple' ); ?>","dimRatio":20,"isUserOverlayColor":true,"focalPoint":{"x":0.5,"y":0.49},"sizeSlug":"full","style":{"elements":{"link":{"color":{"text":"#FFFFFF"}}},"color":{"text":"#FFFFFF"},"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50","left":"var:preset|spacing|50","right":"var:preset|spacing|50"}},"dimensions":{"aspectRatio":"1"}},"layout":{"type":"constrained"},"cropHeight":52.91005291005291,"cropWidth":52.91005291005291,"cropX":42.85,"cropY":1.62} -->
<div class="wp-block-cover has-text-color has-link-color" style="color:#FFFFFF;padding-top:var(--wp--preset--spacing--50);padding-right:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50);padding-left:var(--wp--preset--spacing--50)"><img class="wp-block-cover__image-background size-full" alt="<?php esc_attr_e( 'Female model wearing a colourful knit sweater in a colourful background', 'purple' ); ?>" src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/orange-sweater-woman-cropped.webp" style="object-position:50% 49%" data-object-fit="cover" data-object-position="50% 49%"/><span aria-hidden="true" class="wp-block-cover__background has-background-dim-20 has-background-dim"></span><div class="wp-block-cover__inner-container"><!-- wp:paragraph {"placeholder":"Write title…","style":{"typography":{"textAlign":"center"}},"fontSize":"large"} -->
<p class="has-text-align-center has-large-font-size"><a href="<?php echo esc_url( function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' ) ); ?>"><?php esc_html_e( 'Shop women', 'purple' ); ?></a></p>
<!-- /wp:paragraph --></div></div>
<!-- /wp:cover --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center","width":"50%"} -->
<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:50%"><!-- wp:cover {"url":"<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/pink-sweater-man-cropped.webp","alt":"<?php esc_attr_e( 'Male model wearing a colourful knit sweater', 'purple' ); ?>","dimRatio":20,"isUserOverlayColor":true,"focalPoint":{"x":0.85,"y":0.5},"isDark":false,"sizeSlug":"full","style":{"elements":{"link":{"color":{"text":"#FFFFFF"}}},"color":{"text":"#FFFFFF"},"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50","left":"var:preset|spacing|50","right":"var:preset|spacing|50"}},"dimensions":{"aspectRatio":"1"}},"layout":{"type":"constrained"},"cropHeight":90.9090909090909,"cropWidth":90.9090909090909,"cropX":4.56,"cropY":4.56} -->
<div class="wp-block-cover is-light has-text-color has-link-color" style="color:#FFFFFF;padding-top:var(--wp--preset--spacing--50);padding-right:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50);padding-left:var(--wp--preset--spacing--50)"><img class="wp-block-cover__image-background size-full" alt="<?php esc_attr_e( 'Male model wearing a colourful knit sweater', 'purple' ); ?>" src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/pink-sweater-man-cropped.webp" style="object-position:85% 50%" data-object-fit="cover" data-object-position="85% 50%"/><span aria-hidden="true" class="wp-block-cover__background has-background-dim-20 has-background-dim"></span><div class="wp-block-cover__inner-container"><!-- wp:paragraph {"placeholder":"Write title…","style":{"typography":{"textAlign":"center"}},"fontSize":"large"} -->
<p class="has-text-align-center has-large-font-size"><a href="<?php echo esc_url( function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' ) ); ?>"><?php esc_html_e( 'Shop men', 'purple' ); ?></a></p>
<!-- /wp:paragraph --></div></div>
<!-- /wp:cover --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->
