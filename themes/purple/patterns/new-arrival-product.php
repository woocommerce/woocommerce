<?php
/**
 * Title: New arrival product
 * Slug: purple/new-arrival-product
 * Categories: woo-commerce, purple
 * Keywords: woo-commerce, new arrival, product, featured
 * Description: A new arrival product with a featured image, title, description and button.
 * Viewport width: 1440
 *
 * @package purple
 */

?>
<!-- wp:group {"metadata":{"name":"New arrival product","description":"A new arrival product with a featured image, title, description and button.","categories":["purple"]},"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|90","bottom":"var:preset|spacing|90"},"margin":{"top":"0","bottom":"0"}}},"backgroundColor":"theme-5","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull has-theme-5-background-color has-background" style="margin-top:0;margin-bottom:0;padding-top:var(--wp--preset--spacing--90);padding-bottom:var(--wp--preset--spacing--90)"><!-- wp:image {"url":"<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/product-socks-yellow-purple-theme-1.webp","width":"220px","aspectRatio":"1","scale":"cover","sizeSlug":"full","linkDestination":"none","align":"center"} -->
<figure class="wp-block-image aligncenter size-full is-resized"><img src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/product-socks-yellow-purple-theme-1.webp" alt="<?php esc_attr_e( 'Mustard-yellow chunky cable-knit crew socks.', 'purple' );?>" style="aspect-ratio:1;object-fit:cover;width:220px"/></figure>
<!-- /wp:image -->

<!-- wp:heading {"style":{"typography":{"textAlign":"center"},"spacing":{"margin":{"top":"var:preset|spacing|50"}}}} -->
<h2 class="wp-block-heading has-text-align-center" style="margin-top:var(--wp--preset--spacing--50)"><?php esc_html_e( 'New arrival: socks', 'purple' );?></h2>
<!-- /wp:heading -->

<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons"><!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button"><?php esc_html_e( 'Shop now', 'purple' );?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group -->
