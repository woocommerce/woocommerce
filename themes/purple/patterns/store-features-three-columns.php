<?php
/**
 * Title: Three columns of store features with icons
 * Slug: purple/store-features-three-columns
 * Categories: text, about, media, columns
 * Keywords: about, text, media, columns
 * Description: A three-column section with store featured services.
 */
?>
<!-- wp:group {"metadata":{"name":"Small store features row with icons","categories":["text","about"],"patternName":"purple/store-features-four-columns"},"align":"full","style":{"spacing":{"padding":{"top":"0","bottom":"0"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull" style="padding-top:0;padding-bottom:0"><!-- wp:spacer {"height":"var:preset|spacing|50"} -->
<div style="height:var(--wp--preset--spacing--50)" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->

<!-- wp:columns {"metadata":{"categories":["text","about"],"patternName":"purple/store-features-four-columns"},"align":"wide","style":{"spacing":{"padding":{"top":"0","bottom":"0","left":"0","right":"0"},"blockGap":{"top":"var:preset|spacing|60","left":"0"}}}} -->
<div class="wp-block-columns alignwide" style="padding-top:0;padding-right:0;padding-bottom:0;padding-left:0"><!-- wp:column {"width":"50%","style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}}} -->
<div class="wp-block-column" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50);flex-basis:50%"><!-- wp:group {"style":{"spacing":{"padding":{"right":"0","left":"0"}}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"center","verticalAlignment":"center"}} -->
<div class="wp-block-group" style="padding-right:0;padding-left:0"><!-- wp:group {"style":{"spacing":{"margin":{"bottom":"var:preset|spacing|20"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="margin-bottom:var(--wp--preset--spacing--20)"><!-- wp:image {"width":"32px","sizeSlug":"full","linkDestination":"none"} -->
<figure class="wp-block-image size-full is-resized"><img src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/icon-truck.webp" alt="<?php esc_attr_e('Truck icon', 'purple');?>" style="width:32px"/></figure>
<!-- /wp:image --></div>
<!-- /wp:group -->

<!-- wp:paragraph {"style":{"typography":{"fontStyle":"normal","fontWeight":"400","lineHeight":"1.5"}}} -->
<p style="font-style:normal;font-weight:400;line-height:1.5"><?php esc_html_e('Free Shipping', 'purple');?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"style":{"typography":{"lineHeight":"1"}}} -->
<p style="line-height:1"><?php /* Translators: 1. is the start of a 'a' HTML element, 2. is the end of a 'a' HTML element */ 
echo sprintf( esc_html__( 'No taxes or duties (see %1$sexceptions%2$s).', 'purple' ), '<a href="' . esc_url( '#' ) . '">', '</a>' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:column -->

<!-- wp:column {"width":"50%","style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}}} -->
<div class="wp-block-column" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50);flex-basis:50%"><!-- wp:group {"style":{"spacing":{"padding":{"right":"0","left":"0"}}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"center","verticalAlignment":"center"}} -->
<div class="wp-block-group" style="padding-right:0;padding-left:0"><!-- wp:group {"style":{"spacing":{"margin":{"bottom":"var:preset|spacing|20"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="margin-bottom:var(--wp--preset--spacing--20)"><!-- wp:image {"width":"32px","sizeSlug":"full","linkDestination":"none"} -->
<figure class="wp-block-image size-full is-resized"><img src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/icon-gift.webp" alt="<?php esc_attr_e('Gift icon', 'purple');?>" style="width:32px"/></figure>
<!-- /wp:image --></div>
<!-- /wp:group -->

<!-- wp:paragraph {"style":{"typography":{"fontStyle":"normal","fontWeight":"400","lineHeight":"1.5"}}} -->
<p style="font-style:normal;font-weight:400;line-height:1.5"><?php esc_html_e('Gift Wrapping', 'purple');?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"style":{"typography":{"lineHeight":"1"}}} -->
<p style="line-height:1"><?php esc_html_e('Beautiful premium wrapping paper.', 'purple');?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:column -->

<!-- wp:column {"width":"50%","style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}}} -->
<div class="wp-block-column" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50);flex-basis:50%"><!-- wp:group {"style":{"spacing":{"padding":{"right":"0","left":"0"}}},"layout":{"type":"flex","orientation":"vertical","verticalAlignment":"center","justifyContent":"center"}} -->
<div class="wp-block-group" style="padding-right:0;padding-left:0"><!-- wp:group {"style":{"spacing":{"margin":{"bottom":"var:preset|spacing|20"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="margin-bottom:var(--wp--preset--spacing--20)"><!-- wp:image {"width":"32px","sizeSlug":"full","linkDestination":"none"} -->
<figure class="wp-block-image size-full is-resized"><img src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/icon-return.webp" alt="<?php esc_attr_e('Return icon', 'purple');?>" style="width:32px"/></figure>
<!-- /wp:image --></div>
<!-- /wp:group -->

<!-- wp:paragraph {"style":{"typography":{"fontStyle":"normal","fontWeight":"400","lineHeight":"1.5"}}} -->
<p style="font-style:normal;font-weight:400;line-height:1.5"><?php esc_html_e('Free Returns', 'purple');?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"style":{"typography":{"lineHeight":"1"}}} -->
<p style="line-height:1"><?php esc_html_e('If you don’t love it, you can return it.', 'purple');?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->

<!-- wp:spacer {"height":"var:preset|spacing|50"} -->
<div style="height:var(--wp--preset--spacing--50)" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer --></div>
<!-- /wp:group -->