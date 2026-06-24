<?php
/**
 * Title: Two columns of store features with icons
 * Slug: purple/store-features-two-columns
 * Categories: purple
 * Keywords: about, text, media, columns
 * Description: A two-column section with store featured services.
 * Viewport width: 1440
 */
?>

<!-- wp:group {"metadata":{"name":"Small store features row with icons","categories":["purple"],"patternName":"purple/store-features-two-columns"},"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|50","right":"var:preset|spacing|50"}}},"backgroundColor":"theme-5","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull has-theme-5-background-color has-background" style="padding-top:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--50)"><!-- wp:columns {"metadata":{"categories":["text","about"]},"align":"wide","style":{"spacing":{"blockGap":"var:preset|spacing|40"}}} -->
<div class="wp-block-columns alignwide"><!-- wp:column {"width":"50%"} -->
<div class="wp-block-column" style="flex-basis:50%"><!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|30"}},"layout":{"type":"flex","flexWrap":"wrap","justifyContent":"left"}} -->
<div class="wp-block-group"><!-- wp:group {"layout":{"type":"constrained"}} -->
<div class="wp-block-group"><!-- wp:image {"width":"32px","sizeSlug":"full","linkDestination":"none"} -->
<figure class="wp-block-image size-full is-resized"><img src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/icon-truck.webp" alt="<?php esc_attr_e('Truck icon', 'purple');?>" style="width:32px;height:auto"/></figure>
<!-- /wp:image --></div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"padding":{"right":"0","left":"0"},"blockGap":"0"}},"layout":{"type":"flex","orientation":"vertical"}} -->
<div class="wp-block-group" style="padding-right:0;padding-left:0"><!-- wp:paragraph {"style":{"typography":{"fontStyle":"normal","fontWeight":"600","lineHeight":"1.5"},"elements":{"link":{"color":{"text":"var:preset|color|theme-2"}}}},"textColor":"theme-2","fontSize":"medium"} -->
<p class="has-theme-2-color has-text-color has-link-color has-medium-font-size" style="font-style:normal;font-weight:600;line-height:1.5"><?php esc_html_e('Free Shipping', 'purple');?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"style":{"typography":{"lineHeight":"1.71"},"elements":{"link":{"color":{"text":"var:preset|color|theme-2"}}}},"textColor":"theme-2","fontSize":"small"} -->
<p class="has-theme-2-color has-text-color has-link-color has-small-font-size" style="line-height:1.71"><?php esc_html_e('On all orders above 150€', 'purple');?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:column -->

<!-- wp:column {"width":"50%"} -->
<div class="wp-block-column" style="flex-basis:50%"><!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|30"}},"layout":{"type":"flex","flexWrap":"wrap"}} -->
<div class="wp-block-group"><!-- wp:group {"layout":{"type":"constrained"}} -->
<div class="wp-block-group"><!-- wp:image {"width":"32px","sizeSlug":"full","linkDestination":"none"} -->
<figure class="wp-block-image size-full is-resized"><img src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/icon-return.webp" alt="<?php esc_attr_e('Return icon', 'purple');?>" style="width:32px;height:auto"/></figure>
<!-- /wp:image --></div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"padding":{"right":"0","left":"0"},"blockGap":"0"}},"layout":{"type":"flex","orientation":"vertical"}} -->
<div class="wp-block-group" style="padding-right:0;padding-left:0"><!-- wp:paragraph {"style":{"typography":{"fontStyle":"normal","fontWeight":"600","lineHeight":"1.5"},"elements":{"link":{"color":{"text":"var:preset|color|theme-2"}}}},"textColor":"theme-2","fontSize":"medium"} -->
<p class="has-theme-2-color has-text-color has-link-color has-medium-font-size" style="font-style:normal;font-weight:600;line-height:1.5"><?php esc_html_e('Easy Returns', 'purple');?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"style":{"typography":{"lineHeight":"1.71"},"elements":{"link":{"color":{"text":"var:preset|color|theme-2"}}}},"textColor":"theme-2","fontSize":"small"} -->
<p class="has-theme-2-color has-text-color has-link-color has-small-font-size" style="line-height:1.71"><?php esc_html_e('40-day extended returns', 'purple');?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->
