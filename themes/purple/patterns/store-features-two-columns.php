<?php
/**
 * Title: Two columns of store features with icons
 * Slug: woostarter/store-features-two-columns
 * Categories: text, about, media, columns
 * Keywords: about, text, media, columns
 * Description: A two-column section with store featured services.
 */
?>
<!-- wp:group {"metadata":{"name":"Small store features row with icons","categories":["text","about"],"patternName":"woostarter/store-features-two-columns"},"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|50","right":"var:preset|spacing|50"}}},"backgroundColor":"theme-5","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull has-theme-5-background-color has-background" style="padding-top:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--50);padding-right:var(--wp--preset--spacing--50)">

<!-- wp:columns {"metadata":{"categories":["text","about"],"patternName":"woostarter/store-features-two-columns"},"align":"wide","style":{"spacing":{"padding":{"top":"0","bottom":"0","left":"0","right":"0"},"blockGap":{"top":"var:preset|spacing|60","left":"0"}}}} -->
<div class="wp-block-columns alignwide" style="padding-top:0;padding-right:0;padding-bottom:0;padding-left:0"><!-- wp:column {"width":"50%"} -->
<div class="wp-block-column" style="flex-basis:50%"><!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|30"}},"layout":{"type":"flex","flexWrap":"wrap","justifyContent":"left"}} -->
<div class="wp-block-group"><!-- wp:group {"layout":{"type":"constrained"}} -->
<div class="wp-block-group"><!-- wp:image {"width":"32px","sizeSlug":"full","linkDestination":"none"} -->
<figure class="wp-block-image size-full is-resized"><img src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/icon-truck.webp" alt="<?php esc_attr_e('Truck icon', 'woostarter');?>" style="width:32px"/></figure>
<!-- /wp:image --></div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"padding":{"right":"0","left":"0"}}},"layout":{"type":"flex","orientation":"vertical"}} -->
<div class="wp-block-group" style="padding-right:0;padding-left:0"><!-- wp:paragraph {"style":{"elements":{"link":{"color":{"text":"var:preset|color|theme-2"}}},"typography":{"fontStyle":"normal","fontWeight":"400","lineHeight":"1"}},"textColor":"theme-2"} -->
<p class="has-theme-2-color has-text-color has-link-color" style="font-style:normal;font-weight:400;line-height:1"><?php esc_html_e('Free Shipping', 'woostarter');?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"style":{"typography":{"lineHeight":"1"}},"fontSize":"small"} -->
<p class="has-small-font-size" style="line-height:1"><?php esc_html_e('On all orders above 150€', 'woostarter');?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:column -->

<!-- wp:column {"width":"50%"} -->
<div class="wp-block-column" style="flex-basis:50%"><!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|30"}},"layout":{"type":"flex","flexWrap":"wrap"}} -->
<div class="wp-block-group"><!-- wp:group {"layout":{"type":"constrained"}} -->
<div class="wp-block-group"><!-- wp:image {"width":"32px","sizeSlug":"full","linkDestination":"none"} -->
<figure class="wp-block-image size-full is-resized"><img src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/icon-return.webp" alt="<?php esc_attr_e('Return icon', 'woostarter');?>" style="width:32px"/></figure>
<!-- /wp:image --></div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"padding":{"right":"0","left":"0"}}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"stretch","verticalAlignment":"space-between"}} -->
<div class="wp-block-group" style="padding-right:0;padding-left:0"><!-- wp:paragraph {"style":{"elements":{"link":{"color":{"text":"var:preset|color|theme-2"}}},"typography":{"fontStyle":"normal","fontWeight":"400","lineHeight":"1"}},"textColor":"theme-2"} -->
<p class="has-theme-2-color has-text-color has-link-color" style="font-style:normal;font-weight:400;line-height:1"><?php esc_html_e('Easy Returns', 'woostarter');?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"style":{"typography":{"lineHeight":"1"}},"fontSize":"small"} -->
<p class="has-small-font-size" style="line-height:1"><?php esc_html_e('40-day extended returns', 'woostarter');?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->

</div>
<!-- /wp:group -->