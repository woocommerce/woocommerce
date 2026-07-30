<?php
/**
 * Title: Hero with text and two buttons
 * Slug: purple/hero-with-text-and-two-buttons
 * Categories: purple
 * Keywords: banner, media, call-to-action
 * Description: A hero section with text and two buttons.
 * Viewport width: 1440
 *
 * @package purple
 */

?>

<!-- wp:group {"metadata":{"name":"Hero with text and two buttons","patternName":"purple/hero-with-text-and-two-buttons","description":"A hero section with text and two buttons.","categories":["purple"]},"align":"full","style":{"spacing":{"margin":{"top":"0","bottom":"0"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull" style="margin-top:0;margin-bottom:0"><!-- wp:cover {"url":"<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/orange-sweater.webp","alt":"<?php esc_attr_e( 'Model wearing a colourful knit sweater', 'purple' ); ?>","dimRatio":0,"isUserOverlayColor":true,"focalPoint":{"x":0.48,"y":0.15},"minHeight":60,"minHeightUnit":"vh","contentPosition":"center center","isDark":false,"sizeSlug":"full","align":"full","style":{"spacing":{"padding":{"right":"var:preset|spacing|50","left":"var:preset|spacing|50","top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-cover alignfull is-light" style="padding-top:var(--wp--preset--spacing--50);padding-right:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50);padding-left:var(--wp--preset--spacing--50);min-height:60vh"><img class="wp-block-cover__image-background size-full" alt="<?php esc_attr_e( 'Model wearing a colourful knit sweater', 'purple' ); ?>" src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/orange-sweater.webp" style="object-position:48% 15%" data-object-fit="cover" data-object-position="48% 15%"/><span aria-hidden="true" class="wp-block-cover__background has-background-dim-0 has-background-dim"></span><div class="wp-block-cover__inner-container"><!-- wp:group {"align":"wide","layout":{"type":"default"}} -->
<div class="wp-block-group alignwide"><!-- wp:heading {"level":1,"style":{"elements":{"link":{"color":{"text":"#111111"}}},"color":{"text":"#111111"}}} -->
<h1 class="wp-block-heading has-text-color has-link-color" style="color:#111111"><?php esc_html_e( 'Colorful knits, crafted to last', 'purple' ); ?></h1>
<!-- /wp:heading -->

<!-- wp:paragraph {"style":{"spacing":{"margin":{"top":"var:preset|spacing|10"}},"elements":{"link":{"color":{"text":"#111111"}}},"color":{"text":"#111111"}}} -->
<p class="has-text-color has-link-color" style="color:#111111;margin-top:var(--wp--preset--spacing--10)"><?php esc_html_e( 'Bold knits designed with care—for you and the planet.', 'purple' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( add_query_arg( 'orderby', 'date', function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' ) ) ); ?>"><?php esc_html_e( 'Shop new arrivals', 'purple' ); ?></a></div>
<!-- /wp:button -->

<!-- wp:button {"className":"is-style-button-contrast"} -->
<div class="wp-block-button is-style-button-contrast"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' ) ); ?>"><?php esc_html_e( 'Shop all products', 'purple' ); ?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group --></div></div>
<!-- /wp:cover --></div>
<!-- /wp:group -->
