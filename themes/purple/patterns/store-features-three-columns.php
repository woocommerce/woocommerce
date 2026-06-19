<?php
/**
 * Title: Three columns of store features with icons
 * Slug: purple/store-features-three-columns
 * Categories: purple
 * Keywords: about, text, media, columns
 * Description: A three-column section with store featured services.
 */
?>
<!-- wp:group {"metadata":{"name":"Small store features row with icons","categories":["purple"],"patternName":"purple/store-features-three-columns"},"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|80","bottom":"var:preset|spacing|80"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--80);padding-bottom:var(--wp--preset--spacing--80)"><!-- wp:columns {"metadata":{"categories":["text","about"],"patternName":"purple/store-features-four-columns"},"align":"wide","style":{"spacing":{"blockGap":{"top":"var:preset|spacing|50","left":"0"}}}} -->
<div class="wp-block-columns alignwide"><!-- wp:column {"width":"50%"} -->
<div class="wp-block-column" style="flex-basis:50%"><!-- wp:group {"style":{"spacing":{"padding":{"right":"0","left":"0"},"blockGap":"var:preset|spacing|20"}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"center","verticalAlignment":"center"}} -->
<div class="wp-block-group" style="padding-right:0;padding-left:0"><!-- wp:group {"style":{"spacing":{"margin":{"bottom":"var:preset|spacing|20"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="margin-bottom:var(--wp--preset--spacing--20)"><!-- wp:image {"width":"32px","sizeSlug":"full","linkDestination":"none"} -->
<figure class="wp-block-image size-full is-resized"><img src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/icon-truck.webp" alt="<?php esc_attr_e('Truck icon', 'purple'); ?>" style="width:32px;height:auto"/></figure>
<!-- /wp:image --></div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"blockGap":"0"}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"center"}} -->
<div class="wp-block-group"><!-- wp:paragraph {"style":{"typography":{"fontStyle":"normal","fontWeight":"600","lineHeight":"1.5"}},"fontSize":"medium"} -->
<p class="has-medium-font-size" style="font-style:normal;font-weight:600;line-height:1.5"><?php esc_html_e('Free Shipping', 'purple');?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"style":{"typography":{"lineHeight":"1.71"}},"fontSize":"small"} -->
<p class="has-small-font-size" style="line-height:1.71"><?php esc_html_e('No taxes or duties (see exceptions).', 'purple'); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:column -->

<!-- wp:column {"width":"50%"} -->
<div class="wp-block-column" style="flex-basis:50%"><!-- wp:group {"style":{"spacing":{"padding":{"right":"0","left":"0"},"blockGap":"var:preset|spacing|20"}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"center","verticalAlignment":"center"}} -->
<div class="wp-block-group" style="padding-right:0;padding-left:0"><!-- wp:group {"style":{"spacing":{"margin":{"bottom":"var:preset|spacing|20"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="margin-bottom:var(--wp--preset--spacing--20)"><!-- wp:image {"width":"32px","sizeSlug":"full","linkDestination":"none"} -->
<figure class="wp-block-image size-full is-resized"><img src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/icon-gift.webp" alt="<?php esc_attr_e('Gift icon', 'purple'); ?>" style="width:32px;height:auto"/></figure>
<!-- /wp:image --></div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"blockGap":"0"}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"center"}} -->
<div class="wp-block-group"><!-- wp:paragraph {"style":{"typography":{"fontStyle":"normal","fontWeight":"600","lineHeight":"1.5"}},"fontSize":"medium"} -->
<p class="has-medium-font-size" style="font-style:normal;font-weight:600;line-height:1.5"><?php esc_html_e('Gift Wrapping', 'purple'); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"style":{"typography":{"lineHeight":"1.71"}},"fontSize":"small"} -->
<p class="has-small-font-size" style="line-height:1.71"><?php esc_html_e('Beautiful premium wrapping paper.', 'purple'); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:column -->

<!-- wp:column {"width":"50%"} -->
<div class="wp-block-column" style="flex-basis:50%"><!-- wp:group {"style":{"spacing":{"padding":{"right":"0","left":"0"},"blockGap":"var:preset|spacing|20"}},"layout":{"type":"flex","orientation":"vertical","verticalAlignment":"center","justifyContent":"center"}} -->
<div class="wp-block-group" style="padding-right:0;padding-left:0"><!-- wp:group {"style":{"spacing":{"margin":{"bottom":"var:preset|spacing|20"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="margin-bottom:var(--wp--preset--spacing--20)"><!-- wp:image {"width":"32px","sizeSlug":"full","linkDestination":"none"} -->
<figure class="wp-block-image size-full is-resized"><img src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/icon-return.webp" alt="<?php esc_attr_e('Return icon', 'purple'); ?>" style="width:32px;height:auto"/></figure>
<!-- /wp:image --></div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"blockGap":"0"}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"center"}} -->
<div class="wp-block-group"><!-- wp:paragraph {"style":{"typography":{"fontStyle":"normal","fontWeight":"600","lineHeight":"1.5"}},"fontSize":"medium"} -->
<p class="has-medium-font-size" style="font-style:normal;font-weight:600;line-height:1.5"><?php esc_html_e('Free Returns', 'purple'); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"style":{"typography":{"lineHeight":"1.71"}},"fontSize":"small"} -->
<p class="has-small-font-size" style="line-height:1.71"><?php esc_html_e('If you don’t love it, you can return it.', 'purple'); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->
