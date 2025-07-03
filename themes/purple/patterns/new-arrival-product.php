<?php
/**
 * Title: New arrival product
 * Slug: woostarter/new-arrival-product
 * Categories: woo-commerce, featured, featured-selling, call-to-action
 * Keywords: woo-commerce, featured, featured-selling, call-to-action
 * Description: A new arrival product with a featured image, title, description and button.
 */
?>
<!-- wp:group {"metadata":{"name":"New arrival product"},"align":"full","backgroundColor":"theme-5","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull has-theme-5-background-color has-background"><!-- wp:spacer {"height":"var:preset|spacing|50"} -->
<div style="height:var(--wp--preset--spacing--50)" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:group {"align":"wide","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignwide"><!-- wp:image {"width":"220px","aspectRatio":"3/4","scale":"cover","sizeSlug":"full","linkDestination":"none","align":"center"} -->
<figure class="wp-block-image aligncenter size-full is-resized"><img src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/socks.webp" alt="<?php esc_attr_e('Placeholder image', 'woostarter');?>" style="aspect-ratio:3/4;object-fit:cover;width:220px"/></figure>
<!-- /wp:image -->

<!-- wp:spacer {"height":"var:preset|spacing|40"} -->
<div style="height:var(--wp--preset--spacing--40)" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:heading {"textAlign":"center","fontSize":"xx-large"} -->
<h2 class="wp-block-heading has-text-align-center has-xx-large-font-size"><?php esc_html_e('New arrival: Socks', 'woostarter');?></h2>
<!-- /wp:heading -->

<!-- wp:spacer {"height":"var:preset|spacing|20"} -->
<div style="height:var(--wp--preset--spacing--20)" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons"><!-- wp:button {"fontSize":"medium"} -->
<div class="wp-block-button"><a class="wp-block-button__link has-medium-font-size has-custom-font-size wp-element-button"><?php esc_html_e('Shop now', 'woostarter');?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group -->

<!-- wp:spacer {"height":"var:preset|spacing|50"} -->
<div style="height:var(--wp--preset--spacing--50)" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer --></div>
<!-- /wp:group -->